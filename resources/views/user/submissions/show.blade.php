<x-layouts.app :title="$submission->form->title . ' - Form Saya'" heading="Detail Submission" :subheading="$submission->form->title">

    <div class="mb-3">
        <a href="{{ route('user.my-submissions.index', request()->query()) }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Form Saya
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="badge bg-light text-dark border mb-2">{{ $submission->form->category->name }}</span>
                    <h1 class="h5 fw-bold mb-1">{{ $submission->form->title }}</h1>
                    <p class="text-muted small mb-0">
                        Kode: <code>{{ $submission->form->code }}</code>
                        &middot; Versi {{ $submission->version->version_number }}
                    </p>
                </div>
                <span class="badge fs-6 {{ match ($submission->status) {
                    'submitted' => 'bg-warning text-dark',
                    'reviewed' => 'bg-info text-dark',
                    'approved' => 'bg-success',
                    'rejected' => $submission->isRevisionRequested() ? 'bg-primary' : 'bg-danger',
                    default => 'bg-secondary',
                } }}">
                    {{ $submission->statusLabel() }}
                </span>
            </div>

            <p class="text-muted small mb-0">
                @if ($submission->isDraft())
                    Terakhir disimpan: {{ $submission->updated_at->format('d M Y, H:i') }} WITA
                @else
                    Dikirim: {{ $submission->submitted_at?->format('d M Y, H:i') }} WITA
                @endif
            </p>

            @if ($submission->reviewer)
                <hr>
                <p class="small mb-0">
                    <span class="fw-semibold">Ditinjau oleh:</span> {{ $submission->reviewer->name }}
                    @if ($submission->reviewed_at)
                        &middot; {{ $submission->reviewed_at->format('d M Y, H:i') }} WITA
                    @endif
                </p>
                @if ($submission->isRevisionRequested() && $submission->review_notes)
                    <div class="alert alert-warning border-0 mt-2 mb-0 small">
                        <span class="fw-semibold d-block mb-1">Admin meminta revisi:</span>
                        {{ $submission->review_notes }}
                    </div>
                @elseif ($submission->status === 'rejected' && $submission->review_notes)
                    <div class="alert alert-danger border-0 mt-2 mb-0 small">
                        <span class="fw-semibold d-block mb-1">Catatan penolakan:</span>
                        {{ $submission->review_notes }}
                    </div>
                @elseif ($submission->review_notes)
                    <p class="text-muted small mt-2 mb-0">{{ $submission->review_notes }}</p>
                @endif
            @endif

            @if ($submission->isDraft())
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('user.forms.fill', $submission->form) }}" class="btn btn-brand btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Lanjutkan Isi Form
                    </a>
                    <form action="{{ route('user.my-submissions.destroy', array_merge(['submission' => $submission], request()->query())) }}" method="POST"
                          data-confirm-title="Hapus draft?" data-confirm-message="Draft form {{ $submission->form->title }} akan dihapus. Tindakan ini tidak bisa dibatalkan." data-confirm-tone="danger" data-confirm-button="Ya, hapus draft">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Hapus Draft
                        </button>
                    </form>
                </div>
            @elseif ($submission->isRevisionRequested())
                <div class="mt-3">
                    <a href="{{ route('user.forms.fill', $submission->form) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil-square me-1"></i> Perbaiki &amp; Kirim Ulang
                    </a>
                </div>
            @endif
        </div>
    </div>

    @foreach ($submission->version->sections as $section)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">{{ $section->title }}</h2>

                @forelse ($section->fields as $field)
                    @if ($field->isDisplayOnly())
                        {{-- Static Text / Heading: informasi saja, tidak pernah punya jawaban. --}}
                    @elseif ($field->isDynamicTable())
                        @include('submissions.partials.dynamic-table-answer', ['field' => $field, 'rowsByField' => $rowsByField])
                    @else
                        @include('submissions.partials.scalar-answer', ['field' => $field, 'answersByField' => $answersByField])
                    @endif
                @empty
                    <p class="text-muted small mb-0">Belum ada field di bagian ini.</p>
                @endforelse
            </div>
        </div>
    @endforeach

</x-layouts.app>
