<x-layouts.app :title="'Submission #'.$submission->id.' - RKB Dapur'" heading="Detail Submission" :subheading="$submission->form->title">

    <div class="mb-3">
        <a href="{{ route('admin.submissions.index', request()->query()) }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Submission
        </a>
        <form class="float-end" method="POST" action="{{ route('admin.submissions.destroy', array_merge(['submission' => $submission], request()->query())) }}"
              data-confirm-title="Hapus submission?" data-confirm-tone="danger" data-confirm-button="Hapus permanen"
              data-confirm-message="Submission #{{ $submission->id }} beserta jawaban dan baris tabelnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Hapus Submission</button>
        </form>
    </div>

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm small">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="badge bg-light text-dark border mb-2">{{ $submission->form->category->name }}</span>
                    <h1 class="h5 fw-bold mb-1">{{ $submission->form->title }}</h1>
                    <p class="text-muted small mb-0">
                        Kode: <code>{{ $submission->form->code }}</code>
                        &middot; Versi {{ $submission->version->version_number }}
                        &middot; Diisi oleh: <strong>{{ $submission->user->name }}</strong>
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
                Dikirim: {{ $submission->submitted_at?->format('d M Y, H:i') }} WITA
            </p>

            @if ($submission->reviewer)
                <hr>
                <p class="small mb-0">
                    <span class="fw-semibold">Ditinjau oleh:</span> {{ $submission->reviewer->name }}
                    @if ($submission->reviewed_at)
                        &middot; {{ $submission->reviewed_at->format('d M Y, H:i') }} WITA
                    @endif
                </p>
                @if ($submission->review_notes)
                    <p class="text-muted small mt-2 mb-0">{{ $submission->review_notes }}</p>
                @endif
            @endif

            @if (in_array($submission->status, ['submitted', 'reviewed']))
                <div class="mt-3 d-flex flex-wrap gap-2">
                    @if ($submission->status === 'submitted')
                        <form action="{{ route('admin.submissions.review', $submission) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-eye me-1"></i> Tandai Sedang Ditinjau
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.submissions.approve', $submission) }}" method="POST"
                          data-confirm-title="Setujui submission?" data-confirm-tone="success" data-confirm-button="Ya, setujui"
                          data-confirm-message="Submission dari {{ $submission->user->name }} akan ditandai disetujui.">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg me-1"></i> Setujui
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-lg me-1"></i> Tolak
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#revisionModal">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Minta Revisi
                    </button>
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

    {{-- Modal Tolak -- catatan penolakan wajib diisi, divalidasi di SubmissionReviewService. --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.submissions.reject', $submission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-x-octagon text-danger me-2"></i>Tolak Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small fw-semibold">Catatan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="review_notes" rows="4" class="form-control @error('review_notes') is-invalid @enderror"
                                  placeholder="Jelaskan apa yang perlu diperbaiki User..." required>{{ old('review_notes') }}</textarea>
                        @error('review_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm">Tolak Submission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="revisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.submissions.request-revision', $submission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise text-primary me-2"></i>Minta User Merevisi Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Submission akan dikembalikan kepada User. Sertakan bagian yang perlu diperbaiki.</p>
                        <label class="form-label small fw-semibold">Catatan Revisi <span class="text-danger">*</span></label>
                        <textarea name="revision_notes" rows="4" class="form-control @error('revision_notes') is-invalid @enderror" required>{{ old('revision_notes') }}</textarea>
                        @error('revision_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">Kirim Permintaan Revisi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->has('review_notes'))
        @push('scripts')
        <script>
            // Validasi review_notes gagal di server (mis. isinya cuma spasi,
            // lolos dari atribut HTML "required" tapi ditolak SubmissionReviewService)
            // -- modal harus otomatis terbuka lagi supaya errornya kelihatan,
            // bukan tersembunyi di belakang modal yang tertutup.
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('rejectModal')).show();
            });
        </script>
        @endpush
    @endif

    @if ($errors->has('revision_notes'))
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('revisionModal')).show();
            });
        </script>
        @endpush
    @endif

</x-layouts.app>
