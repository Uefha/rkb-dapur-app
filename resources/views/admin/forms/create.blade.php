<x-layouts.app title="Buat Form Baru - RKB Dapur" heading="Buat Form Baru" subheading="Setelah disimpan, Anda akan diarahkan ke Form Builder">

    <div class="card border-0 shadow-sm" style="max-width: 760px;">
        <div class="card-body p-4">
            <form action="{{ route('admin.forms.store') }}" method="POST">
                @csrf
                @include('admin.forms.partials.form-fields', ['categories' => $categories])

                <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-brand">Simpan &amp; Lanjut ke Builder</button>
            </form>
        </div>
    </div>

</x-layouts.app>
