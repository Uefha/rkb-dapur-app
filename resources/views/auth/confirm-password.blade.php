<x-layouts.guest title="Konfirmasi Kata Sandi - RKB Dapur">
    <div class="text-center mb-4">
        <img class="brand-badge mx-auto mb-3" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt="Logo RKB Dapur">
        <h1 class="h4 fw-bold mb-1">Konfirmasi Kata Sandi</h1>
        <p class="text-muted small mb-0">Ini adalah area yang aman. Mohon konfirmasi kata sandi Anda sebelum melanjutkan.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold">Kata Sandi</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password" autofocus>
        </div>

        <button type="submit" class="btn btn-brand w-100 fw-semibold py-2">Konfirmasi</button>
    </form>
</x-layouts.guest>
