<x-layouts.app title="Dashboard - RKB Dapur" heading="Dashboard Petugas" subheading="Ringkasan form yang tersedia untuk Anda isi">
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <a href="{{ route('user.forms.index') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Form Tersedia</p>
                        <h3 class="fw-bold mb-0">{{ $publishedFormsCount }}</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('user.my-submissions.index', ['status' => 'draft']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Draft Saya</p>
                        <h3 class="fw-bold mb-0">{{ $draftCount }}</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('user.my-submissions.index', ['status' => 'submitted']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Menunggu Verifikasi</p>
                        <h3 class="fw-bold mb-0">{{ $pendingCount }}</h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('user.my-submissions.index', ['status' => 'approved']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Disetujui</p>
                        <h3 class="fw-bold mb-0">{{ $approvedCount }}</h3>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-layouts.app>
