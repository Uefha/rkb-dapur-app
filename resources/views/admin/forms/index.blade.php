<x-layouts.app title="Manajemen Form - RKB Dapur" heading="Manajemen Form" subheading="Kelola seluruh form digital dan versinya">

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.forms.create') }}" class="btn btn-brand btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Buat Form Baru
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Judul Form</th>
                        <th>Kategori</th>
                        <th class="text-center">Versi</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        @php($latest = $form->versions->first())
                        <tr>
                            <td><code class="small">{{ $form->code }}</code></td>
                            <td class="fw-semibold">{{ $form->title }}</td>
                            <td class="text-muted small">{{ $form->category->name }}</td>
                            <td class="text-center">
                                @if ($latest)
                                    <span class="badge {{ $latest->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                                        v{{ $latest->version_number }} · {{ $latest->status === 'published' ? 'Terkunci' : 'Draft' }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Sebelumnya @class([...]) dipakai di sini SETELAH class="badge sudah
                                     dibuka duluan -- @class() sendiri menghasilkan atribut class="..."
                                     baru, jadi hasil render-nya jadi DUA atribut class pada satu tag
                                     (class="badge" ganda dengan class="bg-success" dst.). Browser hanya
                                     memakai atribut class PERTAMA (cuma "badge", tanpa warna latar sama
                                     sekali), sehingga teks memakai warna putih bawaan .badge di atas latar
                                     putih/terang tabel -- persis kenapa tulisan status jadi tidak
                                     terlihat. Diganti jadi satu atribut class biasa, pola yang sama
                                     seperti badge "Versi" di atas. --}}
                                <span class="badge {{ match ($form->status) {
                                    'published' => 'bg-success',
                                    'draft' => 'bg-warning text-dark',
                                    'archived' => 'bg-dark',
                                    default => 'bg-secondary',
                                } }}">
                                    {{ $form->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.forms.access', $form) }}" class="btn btn-sm btn-outline-primary" title="Atur akses pengguna"><i class="bi bi-people"></i> Akses</a>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.forms.builder.index', $form) }}" class="btn btn-outline-success" title="Form Builder">
                                        <i class="bi bi-diagram-3"></i>
                                    </a>
                                    <a href="{{ route('admin.forms.edit', $form) }}" class="btn btn-outline-secondary" title="Edit Metadata">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                        <span class="visually-hidden">Aksi lain</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if ($form->status === 'published')
                                            <li>
                                                <form action="{{ route('admin.forms.unpublish', $form) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Unpublish (sembunyikan)</button>
                                                </form>
                                            </li>
                                        @else
                                            <li>
                                                <form action="{{ route('admin.forms.publish', $form) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Publish</button>
                                                </form>
                                            </li>
                                        @endif
                                        @if ($latest && $latest->status === 'published')
                                            <li>
                                                <form action="{{ route('admin.forms.new-version', $form) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Buat Versi Baru</button>
                                                </form>
                                            </li>
                                        @endif
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#duplicateModal{{ $form->id }}">
                                                Duplicate
                                            </button>
                                        </li>
                                        @if ($form->status !== 'archived')
                                            <li>
                                                <form action="{{ route('admin.forms.archive', $form) }}" method="POST"
                                                      data-confirm-title="Arsipkan form?" data-confirm-message="Form {{ $form->title }} akan disembunyikan dari katalog User." data-confirm-tone="warning" data-confirm-button="Ya, arsipkan">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">Archive</button>
                                                </form>
                                            </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.forms.destroy', $form) }}" method="POST"
                                                  data-confirm-title="Hapus form?" data-confirm-message="Form {{ $form->title }} beserta seluruh strukturnya akan dihapus." data-confirm-tone="danger" data-confirm-button="Ya, hapus form">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="dropdown-item text-danger">Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada form. Buat form pertama Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Duplicate diletakkan di luar <table> (bukan di dalam <tbody>) --}}
    {{-- karena browser akan "membuang keluar" elemen non-<tr> dari tbody,   --}}
    {{-- yang membuat posisi modal tidak terduga saat dibuka.                --}}
    @foreach ($forms as $form)
        <div class="modal fade" id="duplicateModal{{ $form->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.forms.duplicate', $form) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Duplicate "{{ $form->title }}"</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Kode Dokumen Baru</label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Judul Form Baru</label>
                                <input type="text" name="title" class="form-control"
                                       value="{{ $form->title }} (Copy)" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-brand">Duplicate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

</x-layouts.app>
