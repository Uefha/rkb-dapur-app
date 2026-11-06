<?php

namespace App\Http\Requests\Admin;

use App\Models\Form;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique(Form::class, 'code')->where(fn ($q) => $q->whereNull('deleted_at')),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:form_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode dokumen untuk form hasil duplikat wajib diisi.',
            'code.unique' => 'Kode dokumen sudah dipakai form lain.',
            'title.required' => 'Nama form hasil duplikat wajib diisi.',
        ];
    }
}
