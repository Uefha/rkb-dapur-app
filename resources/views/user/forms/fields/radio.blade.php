@php($value = old('answers.'.$field->id, $existingValue ?? ''))
<div class="mb-3">
    <label class="form-label small fw-semibold d-block">
        {{ $field->label }}
        @if ($field->is_required)<span class="text-danger">*</span>@endif
    </label>
    @foreach ($field->options as $option)
        @php($optionValue = $option->value ?? $option->label)
        <div class="form-check">
            <input type="radio" name="answers[{{ $field->id }}]" value="{{ $optionValue }}"
                   class="form-check-input @error('answers.'.$field->id) is-invalid @enderror"
                   id="field{{ $field->id }}-opt{{ $option->id }}"
                   @checked((string) $value === (string) $optionValue) @required($field->is_required)>
            <label class="form-check-label small" for="field{{ $field->id }}-opt{{ $option->id }}">{{ $option->label }}</label>
        </div>
    @endforeach
    @error('answers.'.$field->id)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @if ($field->help_text)
        <div class="form-text">{{ $field->help_text }}</div>
    @endif
</div>
