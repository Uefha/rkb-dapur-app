<x-layouts.guest title="Masuk - RKB Dapur">
    <div class="text-center mb-4">
        <img class="brand-badge mx-auto mb-3" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt="Logo RKB Dapur">
        <h1 class="h4 fw-bold mb-1">RKB Dapur</h1>
        <p class="text-muted small mb-0">Platform Digitalisasi Form Operasional Dapur Umum</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control" required autofocus autocomplete="username">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">Kata Sandi</label>
            <input id="password" type="password" name="password"
                   class="form-control" required autocomplete="current-password">
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small" for="remember">Ingat saya</label>
        </div>

        <button type="submit" class="btn btn-brand w-100 fw-semibold py-2 mb-3">Masuk</button>

        <p class="text-center small text-muted mb-0">
            <a href="{{ route('password.request') }}" class="text-decoration-none">Lupa kata sandi?</a>
        </p>
    </form>
</x-layouts.guest>
