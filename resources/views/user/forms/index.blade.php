<x-layouts.app title="Form Tersedia - RKB Dapur" heading="Form Tersedia" subheading="Pilih form yang ingin Anda isi">

    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode form..."
                           class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="sort" class="form-select form-select-sm">
                        <option value="title_asc" @selected($sort === 'title_asc')>Nama A-Z</option>
                        <option value="title_desc" @selected($sort === 'title_desc')>Nama Z-A</option>
                        <option value="newest" @selected($sort === 'newest')>Terbaru</option>
                    </select>
                </div>
                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-brand btn-sm w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if ($forms->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Tidak ada form yang cocok dengan pencarian Anda.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($forms as $form)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <span class="badge bg-light text-dark border mb-2 align-self-start">{{ $form->category->name }}</span>
                            <h3 class="h6 fw-bold mb-1">{{ $form->title }}</h3>
                            <code class="small text-muted mb-2">{{ $form->code }}</code>
                            <p class="text-muted small mb-3 flex-grow-1">
                                {{ \Illuminate\Support\Str::limit($form->description ?? '', 90) }}
                            </p>
                            <a href="{{ route('user.forms.show', $form) }}" class="btn btn-outline-secondary btn-sm w-100">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    @endif

</x-layouts.app>
