<?php

namespace App\Http\Requests\Admin;

use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:form_categories,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique(Form::class, 'code')->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'code.required' => 'Kode dokumen wajib diisi.',
            'code.unique' => 'Kode dokumen sudah dipakai form lain.',
            'title.required' => 'Nama form wajib diisi.',
        ];
    }
}
