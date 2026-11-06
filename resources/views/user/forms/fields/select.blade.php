@php($value = old('answers.'.$field->id, $existingValue ?? ''))
<div class="mb-3">
    <label class="form-label small fw-semibold">
        {{ $field->label }}
        @if ($field->is_required)<span class="text-danger">*</span>@endif
    </label>
    <select name="answers[{{ $field->id }}]" class="form-select @error('answers.'.$field->id) is-invalid @enderror" @required($field->is_required)>
        <option value="">— Pilih —</option>
        @foreach ($field->options as $option)
            @php($optionValue = $option->value ?? $option->label)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $option->label }}</option>
        @endforeach
    </select>
    @error('answers.'.$field->id)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($field->help_text)
        <div class="form-text">{{ $field->help_text }}</div>
    @endif
</div>
