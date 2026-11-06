<x-layouts.app :title="$form->title . ' - RKB Dapur'" heading="Detail Form" subheading="Tinjau informasi form sebelum mulai mengisi">

    <div class="mb-3">
        <a href="{{ route('user.forms.index') }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Form Tersedia
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <span class="badge bg-light text-dark border mb-2">{{ $form->category->name }}</span>
            <h1 class="h5 fw-bold mb-1">{{ $form->title }}</h1>
            <p class="text-muted small mb-3">
                Kode: <code>{{ $form->code }}</code>
                @if ($form->currentVersion)
                    &middot; Versi {{ $form->currentVersion->version_number }}
                @endif
                &middot; Status: <span class="badge bg-success">{{ $form->statusLabel() }}</span>
            </p>

            @if ($form->description)
                <p class="mb-4">{{ $form->description }}</p>
            @endif

            @if ($form->purpose)
                <h2 class="h6 fw-bold mb-1">Tujuan</h2>
                <p class="text-muted small mb-3">{{ $form->purpose }}</p>
            @endif

            @if ($form->instructions)
                <h2 class="h6 fw-bold mb-1">Petunjuk Pengisian</h2>
                <p class="text-muted small mb-4" style="white-space: pre-line;">{{ $form->instructions }}</p>
            @endif

            <a href="{{ route('user.forms.fill', $form) }}" class="btn btn-brand fw-semibold px-4">
                <i class="bi bi-pencil-square me-1"></i> Isi Form
            </a>
        </div>
    </div>

</x-layouts.app>
