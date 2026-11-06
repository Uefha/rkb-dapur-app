<x-layouts.app title="Tambah Kategori - RKB Dapur" heading="Tambah Kategori Form" subheading="Kategori baru akan langsung tersedia saat membuat form">

    <div class="card border-0 shadow-sm" style="max-width: 640px;">
        <div class="card-body p-4">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Kategori</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-brand">Simpan Kategori</button>
            </form>
        </div>
    </div>

</x-layouts.app>
