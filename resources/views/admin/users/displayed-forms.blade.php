<x-layouts.app title="Form Ditampilkan - RKB Dapur" heading="Form Ditampilkan" subheading="Daftar form yang dapat diakses oleh {{ $user->name }}">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-semibold">{{ $user->name }}</div>
                    <div class="text-muted small">{{ $user->email }}</div>
                </div>
                <span class="badge text-bg-primary">{{ $forms->count() }} form</span>
            </div>
            @if($forms->isEmpty())
                <p class="text-muted mb-0">Tidak ada form published yang ditampilkan kepada user ini.</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach($forms as $form)
                        <div class="list-group-item px-0">
                            <div class="fw-semibold">{{ $form->title }}</div>
                            <div class="small text-muted">{{ $form->code }} · {{ $form->visibility === 'all' ? 'Semua user' : 'Akses khusus' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm mt-3">Kembali ke Manajemen User</a>
        </div>
    </div>
</x-layouts.app>
