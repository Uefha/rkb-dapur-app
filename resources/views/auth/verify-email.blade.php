<x-layouts.guest title="Verifikasi Email - RKB Dapur">
    <div class="text-center mb-4">
        <img class="brand-badge mx-auto mb-3" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt="Logo RKB Dapur">
        <h1 class="h4 fw-bold mb-1">Verifikasi Alamat Email</h1>
        <p class="text-muted small mb-0">
            Terima kasih telah mendaftar. Sebelum melanjutkan, mohon verifikasi email Anda
            dengan mengklik tautan yang baru saja kami kirimkan.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small">
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda daftarkan.
        </div>
    @endif

    <div class="d-flex flex-column gap-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-brand w-100 fw-semibold py-2">Kirim Ulang Email Verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary w-100 fw-semibold py-2">Keluar</button>
        </form>
    </div>
</x-layouts.guest>
