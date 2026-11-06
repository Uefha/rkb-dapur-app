<div class="mb-3 mt-2">
    <h6 class="fw-bold text-uppercase small text-muted mb-1">{{ $field->label }}</h6>
    @if ($field->help_text)
        <p class="text-muted small mb-0">{{ $field->help_text }}</p>
    @endif
    <hr class="mt-2 mb-0">
</div>
