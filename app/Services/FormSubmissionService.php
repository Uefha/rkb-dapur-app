<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionRow;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan draft dan submit. Setiap penyimpanan menghapus seluruh answer &
 * row lama milik submission tersebut lalu menulis ulang dari nol -- lebih
 * sederhana dan aman daripada diffing partial update, dan cukup murah untuk
 * ukuran form di aplikasi ini.
 */
class FormSubmissionService
{
    /**
     * Draft AKTIF milik user untuk form ini (kalau ada) supaya "Isi Form"
     * melanjutkan pekerjaan yang belum selesai, bukan mulai dari kosong lagi.
     * Submission yang sudah dikirim hanya dikembalikan jika Admin meminta
     * revisi. Submission selesai lainnya tidak mengganggu pengisian baru.
     */
    public function findEditableSubmission(Form $form, User $user): ?FormSubmission
    {
        return FormSubmission::query()
            ->where('form_id', $form->id)
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status', FormSubmission::STATUS_DRAFT)
                    ->orWhereNotNull('revision_requested_at');
            })
            ->orderByRaw('CASE WHEN revision_requested_at IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();
    }

    public function startOrResumeDraft(Form $form, FormVersion $version, User $user): FormSubmission
    {
        $draft = $this->findEditableSubmission($form, $user);

        if ($draft) {
            // Sinkronkan ke versi TERKINI kalau draft ini dibuat saat form
            // masih di versi lama (mis. Admin sempat publish versi baru
            // sementara draft ini belum diselesaikan) -- save() di bawah
            // selalu mencocokkan jawaban ke field_id versi yang SEDANG
            // dipakai untuk merender, jadi form_version_id pada submission
            // harus ikut konsisten dengan itu, bukan diam-diam jadi usang.
            if (! $draft->isRevisionRequested() && $draft->form_version_id !== $version->id) {
                $draft->update(['form_version_id' => $version->id]);
            }

            return $draft;
        }

        return FormSubmission::create([
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'user_id' => $user->id,
            'status' => FormSubmission::STATUS_DRAFT,
        ]);
    }

    /**
     * @param  'draft'|'submit'  $intent
     *
     * Menggabungkan startOrResumeDraft() + penyimpanan jawaban dalam SATU
     * transaksi dengan lockForUpdate(), supaya dua request yang nyaris
     * bersamaan untuk form+user yang sama (mis. klik ganda pada tombol
     * Submit sebelum request pertama selesai) tidak bisa membuat dua baris
     * FormSubmission draft terpisah -- request kedua menunggu transaksi
     * pertama commit dulu sebelum ikut mengecek draft aktif yang mana.
     * Dipadukan dengan penonaktifan tombol submit di sisi browser
     * (fill.blade.php) yang menangani kasus paling umum (klik ganda tanpa
     * jeda) tanpa perlu menunggu round-trip ke server sama sekali.
     */
    public function saveSubmission(Form $form, FormVersion $version, User $user, array $answersInput, string $intent): FormSubmission
    {
        $submission = DB::transaction(function () use ($form, $version, $user, $answersInput, $intent) {
            $draft = FormSubmission::query()
                ->where('form_id', $form->id)
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', FormSubmission::STATUS_DRAFT)
                        ->orWhereNotNull('revision_requested_at');
                })
                ->orderByRaw('CASE WHEN revision_requested_at IS NULL THEN 1 ELSE 0 END')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($draft) {
                $wasRevisionRequest = $draft->isRevisionRequested();
                if ($draft->form_version_id !== $version->id) {
                    $draft->update(['form_version_id' => $version->id]);
                }
            } else {
                $wasRevisionRequest = false;
                $draft = FormSubmission::create([
                    'form_id' => $form->id,
                    'form_version_id' => $version->id,
                    'user_id' => $user->id,
                    'status' => FormSubmission::STATUS_DRAFT,
                ]);
            }

            $this->writeAnswers($draft, $version, $answersInput);

            $draft->update([
                'status' => $intent === 'submit' ? FormSubmission::STATUS_SUBMITTED : FormSubmission::STATUS_DRAFT,
                'submitted_at' => $intent === 'submit' ? now() : null,
                'revision_requested_at' => null,
                'review_notes' => $wasRevisionRequest && $intent === 'submit' ? null : $draft->review_notes,
                'reviewed_by' => $wasRevisionRequest && $intent === 'submit' ? null : $draft->reviewed_by,
                'reviewed_at' => $wasRevisionRequest && $intent === 'submit' ? null : $draft->reviewed_at,
            ]);

            return $draft;
        });

        return $submission->fresh(['answers', 'rows']);
    }

    /**
     * @param  'draft'|'submit'  $intent
     *
     * @deprecated Dipertahankan sebagai method terpisah untuk kompatibilitas,
     *   tapi FormSubmissionController sekarang memakai saveSubmission() di
     *   atas supaya find-or-create draft ikut terkunci dalam transaksi yang
     *   sama dengan penulisan jawabannya, bukan dua operasi terpisah seperti
     *   sebelumnya (celah race condition-nya ada di antara keduanya).
     */
    public function save(FormSubmission $submission, FormVersion $version, array $answersInput, string $intent): FormSubmission
    {
        DB::transaction(function () use ($submission, $version, $answersInput, $intent) {
            $this->writeAnswers($submission, $version, $answersInput);

            $submission->update([
                'status' => $intent === 'submit' ? FormSubmission::STATUS_SUBMITTED : FormSubmission::STATUS_DRAFT,
                'submitted_at' => $intent === 'submit' ? now() : null,
            ]);
        });

        return $submission->fresh(['answers', 'rows']);
    }

    private function writeAnswers(FormSubmission $submission, FormVersion $version, array $answersInput): void
    {
        $submission->answers()->delete();
        $submission->rows()->delete();

        $fieldsById = $version->sections->flatMap(fn ($section) => $section->fields)->keyBy('id');

        foreach ($answersInput as $fieldId => $value) {
            $field = $fieldsById->get((int) $fieldId);

            // Keamanan: abaikan field_id yang tidak benar-benar milik versi
            // form ini -- mencegah jawaban disisipkan untuk field asing
            // (mis. milik form lain) lewat manipulasi request di devtools.
            if (! $field || $field->isDisplayOnly()) {
                continue;
            }

            if ($field->isDynamicTable()) {
                $this->saveDynamicTableAnswer($submission, $field, is_array($value) ? $value : []);
                continue;
            }

            if (is_array($value)) {
                // Checkbox multi-pilihan -- disimpan sebagai satu baris
                // JSON, bukan banyak baris, karena tidak butuh di-query
                // per-opsi terpisah di fase manapun sejauh ini.
                $value = json_encode(array_values($value));
            }

            if ($value === null || $value === '') {
                continue;
            }

            FormAnswer::create([
                'form_submission_id' => $submission->id,
                'form_field_id' => $field->id,
                'value' => $value,
            ]);
        }
    }

    /**
     * Nilai kolom baris standar (default_rows) TIDAK PERNAH diambil dari
     * request -- selalu dari struktur field itu sendiri (form_fields.config),
     * persis seperti yang sudah didokumentasikan sejak Phase 4: sisi User
     * tidak pernah mengirim nilai baris standar lewat input sama sekali,
     * supaya nilai yang sudah ditentukan Admin tidak bisa diubah lewat
     * devtools sebelum data sampai ke server.
     */
    private function saveDynamicTableAnswer(FormSubmission $submission, FormField $field, array $tableInput): void
    {
        $rowsInput = $tableInput['rows'] ?? [];
        $defaultRows = $field->tableDefaultRows();
        $columns = collect($field->tableColumns())->keyBy('key');

        $submittedIndexes = array_map('intval', array_keys($rowsInput));
        $maxRowIndex = max(array_merge([count($defaultRows) - 1], $submittedIndexes));

        // Batas wajar untuk mencegah request yang sengaja dimanipulasi (mis.
        // "rows[99999][kolom]=x" lewat devtools) memaksa loop ratusan ribu
        // kali. 500 baris jauh di atas kebutuhan form manapun di aplikasi ini.
        $maxRowIndex = min($maxRowIndex, 500);

        for ($rowIndex = 0; $rowIndex <= $maxRowIndex; $rowIndex++) {
            $isFixedRow = array_key_exists($rowIndex, $defaultRows);
            $submittedRow = $rowsInput[$rowIndex] ?? [];

            $rowHasData = $isFixedRow
                || collect($submittedRow)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

            if (! $rowHasData) {
                continue;
            }

            $row = FormSubmissionRow::create([
                'form_submission_id' => $submission->id,
                'form_field_id' => $field->id,
                'row_index' => $rowIndex,
            ]);

            foreach ($columns as $columnKey => $column) {
                $value = ($isFixedRow && array_key_exists($columnKey, $defaultRows[$rowIndex]))
                    ? $defaultRows[$rowIndex][$columnKey]
                    : ($submittedRow[$columnKey] ?? null);

                if ($value === null || $value === '') {
                    continue;
                }

                FormAnswer::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $field->id,
                    'form_submission_row_id' => $row->id,
                    'column_key' => $columnKey,
                    'value' => $value,
                ]);
            }
        }
    }
}
