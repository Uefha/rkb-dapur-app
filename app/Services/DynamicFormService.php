<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola siklus hidup Form & Form Version: create, publish, unpublish,
 * archive, versioning baru, dan duplicate. Tidak menyentuh isi struktur
 * (section/field) -- itu tanggung jawab FormBuilderService.
 */
class DynamicFormService
{
    public function createForm(array $data, User $creator): Form
    {
        return DB::transaction(function () use ($data, $creator) {
            $form = Form::create([
                'category_id' => $data['category_id'],
                'code' => $data['code'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'status' => Form::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            FormVersion::create([
                'form_id' => $form->id,
                'version_number' => 1,
                'status' => FormVersion::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            return $form;
        });
    }

    public function updateForm(Form $form, array $data): Form
    {
        $form->update([
            'category_id' => $data['category_id'],
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'instructions' => $data['instructions'] ?? null,
        ]);

        return $form->fresh();
    }

    /**
     * Publish form. Ada dua kondisi berbeda yang harus dibedakan:
     *
     * 1) Versi TERBARU sudah berstatus published (form pernah dipublish lalu
     *    di-unpublish, dan belum ada "Buat Versi Baru untuk Edit" sejak itu)
     *    -- unpublishForm() SENGAJA tidak mengubah status versi (supaya versi
     *    yang sudah published tetap terkunci/immutable), jadi di sini cukup
     *    aktifkan kembali status form tanpa memaksa ada versi draft baru.
     * 2) Versi TERBARU masih draft (publish pertama kali, atau publish
     *    setelah "Buat Versi Baru untuk Edit") -- ini jalur lama: kunci
     *    strukturnya jadi published.
     *
     * @throws \RuntimeException jika versi terbaru bukan draft maupun published
     *   (mis. belum ada versi sama sekali), atau strukturnya masih kosong.
     */
    public function publishForm(Form $form): Form
    {
        $latest = $form->latestVersion();

        if ($latest && $latest->status === FormVersion::STATUS_PUBLISHED) {
            $form->update([
                'status' => Form::STATUS_PUBLISHED,
                'current_version_id' => $latest->id,
            ]);

            return $form->fresh();
        }

        if (! $latest || $latest->status !== FormVersion::STATUS_DRAFT) {
            throw new \RuntimeException('Tidak ada versi draft yang bisa dipublish. Buat versi baru terlebih dahulu.');
        }

        $hasField = FormField::whereHas('section', function ($q) use ($latest) {
            $q->where('form_version_id', $latest->id);
        })->exists();

        if (! $hasField) {
            throw new \RuntimeException('Form belum memiliki field sama sekali. Tambahkan minimal 1 section dan 1 field sebelum publish.');
        }

        DB::transaction(function () use ($form, $latest) {
            $latest->update([
                'status' => FormVersion::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            $form->update([
                'current_version_id' => $latest->id,
                'status' => Form::STATUS_PUBLISHED,
            ]);
        });

        return $form->fresh();
    }

    /**
     * Sembunyikan form dari katalog User. Struktur versi yang sudah published
     * TETAP terkunci (tidak dikembalikan ke draft) untuk menjaga histori
     * submission yang mungkin sudah memakainya -- lihat publishForm() untuk
     * bagaimana form yang sama bisa dipublish ulang tanpa versi baru.
     */
    public function unpublishForm(Form $form): Form
    {
        $form->update(['status' => Form::STATUS_DRAFT]);

        return $form->fresh();
    }

    public function archiveForm(Form $form): Form
    {
        $form->update(['status' => Form::STATUS_ARCHIVED]);

        return $form->fresh();
    }

    /**
     * Buat versi draft baru dengan menyalin struktur dari versi terbaru
     * (published), supaya admin bisa lanjut edit tanpa mengubah versi lama.
     *
     * @throws \RuntimeException jika versi terbaru masih berstatus draft.
     */
    public function createNewVersion(Form $form, User $creator): FormVersion
    {
        $latest = $form->latestVersion();

        if (! $latest) {
            throw new \RuntimeException('Form ini belum memiliki versi sama sekali.');
        }

        if ($latest->status === FormVersion::STATUS_DRAFT) {
            throw new \RuntimeException('Masih ada versi draft yang belum dipublish.');
        }

        return DB::transaction(function () use ($form, $latest, $creator) {
            $newVersion = FormVersion::create([
                'form_id' => $form->id,
                'version_number' => $latest->version_number + 1,
                'status' => FormVersion::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            $this->cloneVersionStructure($latest, $newVersion);

            return $newVersion;
        });
    }

    /**
     * Duplicate seluruh form (metadata + struktur versi terbaru) menjadi
     * form baru yang berdiri sendiri, status draft, versi 1.
     */
    public function duplicateForm(Form $original, array $data, User $creator): Form
    {
        return DB::transaction(function () use ($original, $data, $creator) {
            $newForm = Form::create([
                'category_id' => $data['category_id'] ?? $original->category_id,
                'code' => $data['code'],
                'title' => $data['title'],
                'description' => $original->description,
                'purpose' => $original->purpose,
                'instructions' => $original->instructions,
                'status' => Form::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            $newVersion = FormVersion::create([
                'form_id' => $newForm->id,
                'version_number' => 1,
                'status' => FormVersion::STATUS_DRAFT,
                'created_by' => $creator->id,
            ]);

            $sourceVersion = $original->latestVersion();

            if ($sourceVersion) {
                $this->cloneVersionStructure($sourceVersion, $newVersion);
            }

            return $newForm;
        });
    }

    private function cloneVersionStructure(FormVersion $source, FormVersion $target): void
    {
        foreach ($source->sections()->with('fields.options')->get() as $section) {
            $newSection = FormSection::create([
                'form_version_id' => $target->id,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->order,
            ]);

            foreach ($section->fields as $field) {
                $newField = FormField::create([
                    'form_section_id' => $newSection->id,
                    'field_type' => $field->field_type,
                    'name' => $field->name,
                    'label' => $field->label,
                    'placeholder' => $field->placeholder,
                    'help_text' => $field->help_text,
                    'is_required' => $field->is_required,
                    'order' => $field->order,
                    'config' => $field->config,
                ]);

                foreach ($field->options as $option) {
                    FormFieldOption::create([
                        'form_field_id' => $newField->id,
                        'label' => $option->label,
                        'value' => $option->value,
                        'order' => $option->order,
                    ]);
                }
            }
        }
    }
}
