<x-layouts.app title="Pengaturan - RKB Dapur" heading="Pengaturan" subheading="Atur kontak bantuan yang ditampilkan pada halaman login">
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm small">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm" style="max-width: 720px">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-1"><i class="bi bi-whatsapp text-success me-2"></i>Kontak WhatsApp Admin</h2>
            <p class="text-muted small mb-4">Nomor ini menjadi tujuan bantuan bagi pengguna yang lupa kata sandi.</p>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="support_whatsapp" class="form-label small fw-semibold">Nomor WhatsApp</label>
                    <input id="support_whatsapp" name="support_whatsapp" type="tel" inputmode="tel"
                           class="form-control" value="{{ old('support_whatsapp', $whatsapp) }}"
                           placeholder="Contoh: 6281234567890" maxlength="30">
                    <div class="form-text">Gunakan kode negara tanpa tanda + untuk tautan paling andal, misalnya 6281234567890. Kosongkan untuk menyembunyikan tombol WhatsApp di halaman login.</div>
                </div>

                <button type="submit" class="btn btn-brand fw-semibold"><i class="bi bi-check-lg me-1"></i>Simpan Pengaturan</button>
            </form>
        </div>
    </div>
</x-layouts.app>
