@php($value = old('answers.'.$field->id, $existingValue ?? ''))
<div class="mb-3">
    <label class="form-label small fw-semibold d-block">
        {{ $field->label }}
        @if ($field->is_required)<span class="text-danger">*</span>@endif
    </label>
    <div class="form-check form-check-inline">
        <input type="radio" name="answers[{{ $field->id }}]" value="1" class="form-check-input"
               id="field{{ $field->id }}-ya" @checked((string) $value === '1') @required($field->is_required)>
        <label class="form-check-label small" for="field{{ $field->id }}-ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="radio" name="answers[{{ $field->id }}]" value="0" class="form-check-input"
               id="field{{ $field->id }}-tidak" @checked((string) $value === '0')>
        <label class="form-check-label small" for="field{{ $field->id }}-tidak">Tidak</label>
    </div>
    @error('answers.'.$field->id)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @if ($field->help_text)
        <div class="form-text">{{ $field->help_text }}</div>
    @endif
</div>
