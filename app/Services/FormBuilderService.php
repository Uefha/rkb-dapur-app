<?php

namespace App\Services;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSection;
use App\Models\FormVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mengelola isi struktur satu Form Version: section, field, dan opsi field.
 * Semua method di sini menolak perubahan jika versi sudah published --
 * itulah yang menjaga immutability versi (bagian 12 spesifikasi).
 */
class FormBuilderService
{
    /**
     * @throws \RuntimeException jika versi sudah published (terkunci).
     */
    public function ensureEditable(FormVersion $version): void
    {
        if ($version->status !== FormVersion::STATUS_DRAFT) {
            throw new \RuntimeException('Versi ini sudah dipublish dan terkunci. Buat versi baru untuk melanjutkan edit.');
        }
    }

    public function addSection(FormVersion $version, array $data): FormSection
    {
        $this->ensureEditable($version);

        $nextOrder = (int) $version->sections()->max('order') + 1;

        return FormSection::create([
            'form_version_id' => $version->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $nextOrder,
        ]);
    }

    public function updateSection(FormSection $section, array $data): FormSection
    {
        $this->ensureEditable($section->formVersion);

        $section->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        return $section->fresh();
    }

    public function deleteSection(FormSection $section): void
    {
        $this->ensureEditable($section->formVersion);

        $section->delete(); // cascade: fields & options ikut terhapus (FK cascadeOnDelete)
    }

    public function moveSection(FormSection $section, string $direction): void
    {
        $this->ensureEditable($section->formVersion);

        $sibling = $section->formVersion->sections()
            ->where('order', $direction === 'up' ? '<' : '>', $section->order)
            ->orderBy('order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($sibling) {
            DB::transaction(function () use ($section, $sibling) {
                [$orderA, $orderB] = [$section->order, $sibling->order];
                $section->update(['order' => $orderB]);
                $sibling->update(['order' => $orderA]);
            });
        }
    }

    public function addField(FormSection $section, array $data): FormField
    {
        $this->ensureEditable($section->formVersion);

        $nextOrder = (int) $section->fields()->max('order') + 1;

        $field = DB::transaction(function () use ($section, $data, $nextOrder) {
            $field = FormField::create([
                'form_section_id' => $section->id,
                'field_type' => $data['field_type'],
                'name' => $this->generateUniqueName($section, $data['label']),
                'label' => $data['label'],
                'placeholder' => $data['placeholder'] ?? null,
                'help_text' => $data['help_text'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'order' => $nextOrder,
                'config' => $this->buildFieldConfig($data),
            ]);

            $this->syncOptions($field, $data['options'] ?? []);

            return $field;
        });

        return $field;
    }

    public function updateField(FormField $field, array $data): FormField
    {
        $this->ensureEditable($field->section->formVersion);

        DB::transaction(function () use ($field, $data) {
            $config = $this->buildFieldConfig($data);

            // Builder UI Phase 3 belum mengekspos edit "default_rows" (fitur checklist
            // baris tetap) -- hanya seeder yang mengisinya. Supaya edit kolom lewat UI
            // tidak diam-diam menghapus default_rows yang sudah ada, pertahankan nilainya
            // kecuali payload secara eksplisit membawa kunci "default_rows" sendiri.
            if ($config && ! array_key_exists('default_rows', $data) && ! empty($field->config['default_rows'] ?? null)) {
                $config['default_rows'] = $field->config['default_rows'];
            }

            $field->update([
                'field_type' => $data['field_type'],
                'label' => $data['label'],
                'placeholder' => $data['placeholder'] ?? null,
                'help_text' => $data['help_text'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'config' => $config,
            ]);

            $this->syncOptions($field, $data['options'] ?? []);
        });

        return $field->fresh('options');
    }

    public function deleteField(FormField $field): void
    {
        $this->ensureEditable($field->section->formVersion);

        $field->delete();
    }

    public function moveField(FormField $field, string $direction): void
    {
        $this->ensureEditable($field->section->formVersion);

        $sibling = $field->section->fields()
            ->where('order', $direction === 'up' ? '<' : '>', $field->order)
            ->orderBy('order', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($sibling) {
            DB::transaction(function () use ($field, $sibling) {
                [$orderA, $orderB] = [$field->order, $sibling->order];
                $field->update(['order' => $orderB]);
                $sibling->update(['order' => $orderA]);
            });
        }
    }

    /**
     * Susun kolom "config" JSON untuk field. Hanya field bertipe dynamic_table
     * yang punya config (kolom + baris standar opsional) -- tipe lain null.
     *
     * Menerima 2 bentuk input opsi kolom select, supaya bisa dipakai baik dari
     * form HTTP Builder (comma-separated string di "options_text") maupun dari
     * pemanggilan langsung seperti FormSeeder (array PHP biasa di "options").
     */
    private function buildFieldConfig(array $data): ?array
    {
        if (($data['field_type'] ?? null) !== FormField::TYPE_DYNAMIC_TABLE) {
            return null;
        }

        $columns = [];
        $usedKeys = [];

        foreach (($data['columns'] ?? []) as $column) {
            $label = trim($column['label'] ?? '');

            if ($label === '') {
                continue;
            }

            $baseKey = Str::slug($column['key'] ?? $label, '_') ?: 'kolom';
            $key = $baseKey;
            $i = 2;

            while (in_array($key, $usedKeys, true)) {
                $key = "{$baseKey}_{$i}";
                $i++;
            }

            $usedKeys[] = $key;

            $type = $column['type'] ?? 'text';

            if ($type === 'select') {
                $options = isset($column['options'])
                    ? $column['options']
                    : explode(',', $column['options_text'] ?? '');

                $options = array_values(array_filter(array_map('trim', $options)));
            } else {
                $options = null;
            }

            $columns[] = array_filter([
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'options' => $options,
            ], fn ($v) => $v !== null);
        }

        $config = ['columns' => $columns];

        if (! empty($data['default_rows'])) {
            $config['default_rows'] = $data['default_rows'];
        }

        return $config;
    }

    /**
     * Hapus semua opsi lama lalu buat ulang dari input -- cukup untuk Builder
     * dasar (jumlah opsi per field biasanya kecil, tidak perlu diffing rumit).
     */
    private function syncOptions(FormField $field, array $options): void
    {
        if (! $field->hasOptions()) {
            $field->options()->delete();

            return;
        }

        $field->options()->delete();

        foreach (array_values($options) as $i => $option) {
            $label = trim($option['label'] ?? '');

            if ($label === '') {
                continue;
            }

            FormFieldOption::create([
                'form_field_id' => $field->id,
                'label' => $label,
                'value' => trim($option['value'] ?? '') !== '' ? trim($option['value']) : Str::slug($label, '_'),
                'order' => $i,
            ]);
        }
    }

    private function generateUniqueName(FormSection $section, string $label, ?int $ignoreFieldId = null): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $name = $base;
        $counter = 2;

        $query = fn (string $candidate) => FormField::where('form_section_id', $section->id)
            ->where('name', $candidate)
            ->when($ignoreFieldId, fn ($q) => $q->where('id', '!=', $ignoreFieldId))
            ->exists();

        while ($query($name)) {
            $name = "{$base}_{$counter}";
            $counter++;
        }

        return $name;
    }
}
