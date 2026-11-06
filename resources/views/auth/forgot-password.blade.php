<x-layouts.guest title="Lupa Kata Sandi - RKB Dapur">
    <div class="text-center mb-4">
        <img class="brand-badge mx-auto mb-3" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt="Logo RKB Dapur">
        <h1 class="h4 fw-bold mb-1">Lupa Kata Sandi</h1>
        <p class="text-muted small mb-0">Pilih cara pemulihan kata sandi yang tersedia.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success small">{{ session('status') }}</div>
    @endif

    @if ($recoveryEnabled)
        <div class="mb-3">
            <button class="btn btn-brand fw-semibold w-100" type="button" data-bs-toggle="collapse" data-bs-target="#recoveryForm" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="recoveryForm">
                <i class="bi bi-key me-2"></i>Ubah sandi dengan kata kunci pemulihan
            </button>
            <div class="collapse {{ $errors->any() ? 'show' : '' }} mt-3" id="recoveryForm">
                <form method="POST" action="{{ route('password.recovery') }}" class="border rounded-3 p-3">
                    @csrf
                    <p class="small text-muted">Masukkan email akun, kata kunci pemulihan, dan kata sandi baru.</p>
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email akun</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="recovery_key" class="form-label small fw-semibold">Kata kunci pemulihan</label>
                        <input id="recovery_key" type="password" name="recovery_key" class="form-control @error('recovery_key') is-invalid @enderror" autocomplete="off" required>
                        @error('recovery_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Kata sandi baru</label>
                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label small fw-semibold">Konfirmasi kata sandi baru</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-brand w-100 fw-semibold">Simpan kata sandi baru</button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 small mb-3">
            Fitur kata kunci pemulihan belum diaktifkan. Admin dapat mengaturnya melalui <code>PASSWORD_RECOVERY_KEY</code> di file environment.
        </div>
    @endif

    <div class="text-center">
        <div class="alert alert-info border-0 small mb-3">
            <i class="bi bi-info-circle me-1"></i> Fitur pengiriman tautan reset dinonaktifkan. Silakan hubungi admin melalui WhatsApp.
        </div>
        @if (!empty($whatsappLink))
            <a href="https://wa.me/{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" class="btn btn-success fw-semibold px-4 py-2 mb-3">
                <i class="bi bi-whatsapp me-2"></i>Hubungi Admin via WhatsApp{{ $whatsapp ? ' · '.$whatsapp : '' }}
            </a>
        @else
            <div class="small text-muted mb-3">Nomor WhatsApp admin belum tersedia. Silakan hubungi admin melalui jalur komunikasi yang biasa digunakan.</div>
        @endif
        <p class="small text-muted mb-0">
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Kembali ke halaman masuk</a>
        </p>
    </div>
</x-layouts.guest>
