<x-layouts.app title="Kategori Form - RKB Dapur" heading="Kategori Form" subheading="Kelola kategori untuk mengelompokkan form">

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-brand btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Jumlah Form</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td class="text-muted small">{{ $category->description ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $category->forms_count }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline"
                                      data-confirm-title="Hapus kategori?" data-confirm-message="Kategori {{ $category->name }} akan dihapus." data-confirm-tone="danger" data-confirm-button="Ya, hapus kategori">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada kategori. Tambahkan kategori pertama Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layouts.app>
