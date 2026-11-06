<x-layouts.app title="{{ auth()->user()->isAdmin() ? 'Profil Admin' : 'Profil Saya' }} - RKB Dapur" heading="{{ auth()->user()->isAdmin() ? 'Profil Admin' : 'Profil Saya' }}" subheading="Kelola nama pengguna, email login, dan kata sandi akun Anda">

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-1">Informasi Profil</h2>
                    <p class="text-muted small mb-4">Perbarui nama tampilan dan email yang digunakan untuk login.</p>

                    @if (session('status') === 'profile-updated')
                        <div class="alert alert-success py-2 small">Profil berhasil diperbarui.</div>
                    @endif

                    @if ($errors->has('name') || $errors->has('email'))
                        <div class="alert alert-danger py-2 small">
                            @foreach (array_merge($errors->get('name'), $errors->get('email')) as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Nama Pengguna</label>
                            <input id="name" type="text" name="name" class="form-control"
                                   value="{{ old('name', $user->name) }}" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label small fw-semibold">Email / Username Login</label>
                            <input id="email" type="email" name="email" class="form-control"
                                   value="{{ old('email', $user->email) }}" required>

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <p class="small text-warning mt-2 mb-0">
                                    Email Anda belum terverifikasi.
                                    <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">Kirim ulang tautan verifikasi.</button>
                                </p>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-brand fw-semibold px-4">Simpan Perubahan</button>
                    </form>

                    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
                        @csrf
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-1">Ubah Kata Sandi</h2>
                    <p class="text-muted small mb-4">Gunakan kata sandi yang kuat dan tidak dipakai di tempat lain.</p>

                    @if (session('status') === 'password-updated')
                        <div class="alert alert-success py-2 small">Kata sandi berhasil diperbarui.</div>
                    @endif

                    @if ($errors->has('current_password') || $errors->has('password'))
                        <div class="alert alert-danger py-2 small">
                            @foreach (array_merge($errors->get('current_password'), $errors->get('password')) as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold">Kata Sandi Saat Ini</label>
                            <input id="current_password" type="password" name="current_password"
                                   class="form-control" autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Kata Sandi Baru</label>
                            <input id="password" type="password" name="password"
                                   class="form-control" autocomplete="new-password">
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label small fw-semibold">Konfirmasi Kata Sandi Baru</label>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                   class="form-control" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-brand fw-semibold px-4">Perbarui Kata Sandi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>
