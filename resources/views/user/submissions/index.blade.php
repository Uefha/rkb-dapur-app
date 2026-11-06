<x-layouts.app title="Form Saya - RKB Dapur" heading="Form Saya" subheading="Riwayat draft dan submission yang pernah Anda isi">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-8 col-md-4">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Menunggu Verifikasi</option>
                        <option value="reviewed" @selected(request('status') === 'reviewed')>Ditinjau</option>
                        <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak / Perlu Revisi</option>
                    </select>
                </div>
                <div class="col-4 col-md-auto">
                    <a href="{{ route('user.my-submissions.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if ($submissions->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Belum ada submission. Yuk mulai isi form dari
                <a href="{{ route('user.forms.index') }}">Form Tersedia</a>.
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Form</th>
                            <th>Kategori</th>
                            <th>Tanggal</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td>{{ $submission->form->title }}</td>
                                <td class="text-muted small">{{ $submission->form->category->name }}</td>
                                <td class="text-muted small">{{ $submission->updated_at->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ match ($submission->status) {
                                        'submitted' => 'bg-warning text-dark',
                                        'reviewed' => 'bg-info text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => $submission->isRevisionRequested() ? 'bg-primary' : 'bg-danger',
                                        default => 'bg-secondary',
                                    } }}">
                                        {{ $submission->statusLabel() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if ($submission->isDraft())
                                        <a href="{{ route('user.forms.fill', $submission->form) }}" class="btn btn-sm btn-outline-secondary">
                                            Lanjutkan
                                        </a>
                                        <form action="{{ route('user.my-submissions.destroy', array_merge(['submission' => $submission], request()->query())) }}" method="POST" class="d-inline"
                                              data-confirm-title="Hapus draft?" data-confirm-message="Draft form {{ $submission->form->title }} akan dihapus. Tindakan ini tidak bisa dibatalkan." data-confirm-tone="danger" data-confirm-button="Ya, hapus draft">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @elseif ($submission->isRevisionRequested())
                                        <a href="{{ route('user.forms.fill', $submission->form) }}" class="btn btn-sm btn-primary">Perbaiki</a>
                                        <a href="{{ route('user.my-submissions.show', array_merge(['submission' => $submission], request()->query())) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                    @else
                                        <a href="{{ route('user.my-submissions.show', array_merge(['submission' => $submission], request()->query())) }}" class="btn btn-sm btn-outline-secondary">
                                            Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    @endif

</x-layouts.app>
