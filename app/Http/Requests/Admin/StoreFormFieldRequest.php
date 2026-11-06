<?php

namespace App\Http\Requests\Admin;

use App\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field_type' => ['required', Rule::in(array_keys(FormField::PHASE3_TYPES))],
            'label' => ['required', 'string', 'max:2000'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['required_if:field_type,select,radio,checkbox', 'array', 'min:1'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.value' => ['nullable', 'string', 'max:255'],

            'columns' => ['required_if:field_type,dynamic_table', 'array', 'min:1'],
            'columns.*.label' => ['required_with:columns', 'string', 'max:255'],
            'columns.*.type' => ['required_with:columns', Rule::in(array_keys(FormField::TABLE_COLUMN_TYPES))],
            'columns.*.options_text' => ['required_if:columns.*.type,select', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_type.required' => 'Tipe field wajib dipilih.',
            'label.required' => 'Label field wajib diisi.',
            'options.required_if' => 'Tipe field ini butuh minimal 1 pilihan opsi.',
            'options.*.label.required_with' => 'Label opsi wajib diisi.',
            'columns.required_if' => 'Dynamic Table butuh minimal 1 kolom.',
            'columns.*.label.required_with' => 'Label kolom wajib diisi.',
            'columns.*.options_text.required_if' => 'Kolom bertipe Select butuh minimal 1 pilihan (pisahkan dengan koma).',
        ];
    }

    /**
     * Pengaman tambahan di luar rule wildcard di atas: Laravel mencocokkan
     * required_if pada wildcard berdasarkan index yang sama, tapi kita tetap
     * verifikasi manual supaya kolom Select yang lolos dengan opsi kosong
     * (mis. hanya berisi koma/spasi) tetap tertangkap sebagai error.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('columns', []) as $i => $column) {
                if (($column['type'] ?? null) === 'select') {
                    $options = array_filter(array_map('trim', explode(',', $column['options_text'] ?? '')));

                    if (empty($options)) {
                        $validator->errors()->add("columns.$i.options_text", 'Kolom Select butuh minimal 1 pilihan yang valid.');
                    }
                }
            }
        });
    }
}
