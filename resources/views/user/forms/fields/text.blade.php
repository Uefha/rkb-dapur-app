@php($value = old('answers.'.$field->id, $existingValue ?? ''))
<div class="mb-3">
    <label class="form-label small fw-semibold">
        {{ $field->label }}
        @if ($field->is_required)<span class="text-danger">*</span>@endif
    </label>
    <input type="text" name="answers[{{ $field->id }}]" value="{{ $value }}" placeholder="{{ $field->placeholder }}"
           class="form-control @error('answers.'.$field->id) is-invalid @enderror" @required($field->is_required)>
    @error('answers.'.$field->id)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($field->help_text)
        <div class="form-text">{{ $field->help_text }}</div>
    @endif
</div>
