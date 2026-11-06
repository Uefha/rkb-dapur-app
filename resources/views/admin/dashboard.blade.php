<x-layouts.app title="Dashboard Admin - RKB Dapur" heading="Dashboard Admin" subheading="Ringkasan pengelolaan form & submission">

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Form</p>
                    <h3 class="fw-bold mb-0">{{ $stats['total_forms'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Form Published</p>
                    <h3 class="fw-bold mb-0">{{ $stats['published_forms'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Form Draft</p>
                    <h3 class="fw-bold mb-0">{{ $stats['draft_forms'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Form Archived</p>
                    <h3 class="fw-bold mb-0">{{ $stats['archived_forms'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.submissions.index', ['status' => 'submitted']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Menunggu Verifikasi</p>
                        <h3 class="fw-bold mb-0">{{ $stats['pending_submissions'] }}</h3>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-2">Mulai Cepat</h2>
            <p class="text-muted small mb-3">Buat kategori (jika belum ada yang cocok), lalu buat form baru dan susun strukturnya lewat Form Builder.</p>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-success btn-sm me-2">
                <i class="bi bi-tags me-1"></i> Kelola Kategori
            </a>
            <a href="{{ route('admin.forms.create') }}" class="btn btn-brand btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Form Baru
            </a>
        </div>
    </div>

</x-layouts.app>
