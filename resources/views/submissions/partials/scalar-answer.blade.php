@php
    $answer = $answersByField->get($field->id);
    $rawValue = $answer->value ?? null;
    $selected = $field->field_type === 'checkbox' && $rawValue
        ? (json_decode($rawValue, true) ?? [])
        : [];
@endphp

<div class="mb-3">
    <label class="form-label small fw-semibold d-block">{{ $field->label }}</label>

    @if ($field->field_type === 'checkbox')
        <p class="mb-0">{{ empty($selected) ? '—' : implode(', ', $selected) }}</p>
    @elseif ($field->field_type === 'yesno')
        <p class="mb-0">{{ $rawValue === '1' ? 'Ya' : ($rawValue === '0' ? 'Tidak' : '—') }}</p>
    @else
        <p class="mb-0" style="white-space: pre-line;">{{ $rawValue !== null && $rawValue !== '' ? $rawValue : '—' }}</p>
    @endif
</div>
