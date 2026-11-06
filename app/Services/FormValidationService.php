<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Membangun aturan validasi Laravel secara dinamis dari struktur field suatu
 * form_version. Tidak ada satupun rule yang di-hardcode per form -- semuanya
 * diturunkan dari field_type, is_required, dan opsi field di database, sesuai
 * prinsip "satu mesin generik" yang dipakai di seluruh platform ini.
 *
 * Validasi Dynamic Table SENGAJA belum divalidasi per-kolom di Phase 5 (mis.
 * kolom Select di dalam tabel belum dicek terhadap daftar pilihannya) --
 * dicatat sebagai keterbatasan yang diketahui di README, bukan lupa.
 */
class FormValidationService
{
    /**
     * @param  'draft'|'submit'  $intent  Saat draft, seluruh field bersifat
     *   opsional (boleh kosong) terlepas dari is_required -- supaya User bisa
     *   menyimpan progres yang belum lengkap. Validasi penuh hanya berlaku
     *   saat submit.
     * @return array data yang sudah divalidasi (subset dari $data)
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(FormVersion $version, array $data, string $intent): array
    {
        [$rules, $attributes] = $this->buildRulesAndAttributes($version, $intent);

        return Validator::make($data, $rules, [], $attributes)->validate();
    }

    /**
     * Field yang benar-benar mengumpulkan jawaban -- mengecualikan
     * static_text/heading (display-only, tidak pernah punya nilai) dan
     * dynamic_table (lihat catatan class di atas).
     *
     * @return Collection<int, FormField>
     */
    private function answerableFields(FormVersion $version): Collection
    {
        return $version->sections
            ->flatMap(fn ($section) => $section->fields)
            ->reject(fn (FormField $field) => $field->isDisplayOnly() || $field->isDynamicTable());
    }

    private function buildRulesAndAttributes(FormVersion $version, string $intent): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->answerableFields($version) as $field) {
            $key = "answers.{$field->id}";
            $attributes[$key] = $field->label;
            $rules[$key] = $this->rulesForField($field, $intent);

            if ($field->field_type === FormField::TYPE_CHECKBOX) {
                $attributes["{$key}.*"] = $field->label;
                $rules["{$key}.*"] = [$this->inRuleForOptions($field)];
            }
        }

        return [$rules, $attributes];
    }

    private function rulesForField(FormField $field, string $intent): array
    {
        $required = $intent === 'submit' && $field->is_required;
        $rules = [$required ? 'required' : 'nullable'];

        $rules[] = match ($field->field_type) {
            FormField::TYPE_NUMBER => 'numeric',
            FormField::TYPE_EMAIL => 'email',
            FormField::TYPE_DATE => 'date',
            FormField::TYPE_TIME => 'date_format:H:i',
            FormField::TYPE_DATETIME => 'date',
            FormField::TYPE_YESNO => Rule::in(['0', '1']),
            FormField::TYPE_SELECT, FormField::TYPE_RADIO => $this->inRuleForOptions($field),
            FormField::TYPE_CHECKBOX => 'array',
            default => 'string',
        };

        return $rules;
    }

    /**
     * Daftar value yang sah untuk field Select/Radio/Checkbox, HARUS persis
     * sama dengan yang dirender di <option>/<input> (value="{{ $option->value
     * ?? $option->label }}" -- fallback per-opsi, bukan borongan). Kalau
     * sebagian opsi punya `value` diisi dan sebagian lagi kosong (admin bisa
     * mengosongkan value per-baris di Builder), fallback borongan akan
     * membuang opsi yang value-nya kosong dari daftar yang diizinkan --
     * padahal di form yang dirender opsi itu tetap terkirim (pakai labelnya).
     * Submission yang sah pun jadi ditolak validasi. Fallback per-opsi di sini
     * menghindari itu.
     *
     * Return type SENGAJA `mixed`, bukan nama interface Rule tertentu.
     * Dua percobaan sebelumnya (`Illuminate\Validation\Rule`, lalu
     * `Illuminate\Contracts\Validation\Rule`) sama-sama salah tebak --
     * ternyata objek yang dikembalikan `Rule::in()` di versi Laravel
     * terpasang saat ini (12.69) adalah instance dari
     * `Illuminate\Contracts\Validation\ValidationRule` (kontrak BARU dengan
     * method validate(), bukan passes()/message() dari kontrak lama), dan
     * Laravel bisa mengubah kelas konkret yang dipakai `Rule::in()` lintas
     * versi tanpa itu dianggap breaking change -- karena semua interface
     * rule itu sama-sama diterima Validator, tinggal PHP-nya yang butuh tipe
     * pasti untuk return type method ini. Daripada menebak nama interface
     * yang benar untuk kali ketiga (dan berisiko salah lagi di update
     * Laravel berikutnya), `mixed` diterima Validator apa pun bentuk
     * konkretnya -- aman terhadap perubahan versi Laravel ke depan.
     */
    private function inRuleForOptions(FormField $field): mixed
    {
        $values = $field->options->map(fn ($option) => $option->value ?? $option->label)->all();

        return Rule::in($values);
    }
}
