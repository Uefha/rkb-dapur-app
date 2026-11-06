<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#123b2c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ rtrim(request()->getBaseUrl(), '/') }}/manifest.webmanifest">
    <link rel="icon" href="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="{{ rtrim(request()->getBaseUrl(), '/') }}/pwa-icon-192.png">
    <title>{{ $title ?? 'RKB Dapur' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f6f5; }
        .sidebar {
            width: 250px; height: 100vh; overflow-y: auto; background: #123b2c; color: #fff;
            position: fixed; z-index: 1045; top:0; left:0; padding: 1.5rem 1rem;
            transition: width .2s ease, transform .2s ease;
        }
        .sidebar .brand { font-weight: 800; font-size: 1.1rem; margin-bottom: 2rem; display:flex; align-items:center; gap:.6rem; color:#fff; text-decoration:none; white-space: nowrap; }
        .sidebar .brand-logo { width:2.25rem; height:2.25rem; flex:0 0 auto; object-fit:cover; border-radius:50%; background:#fff; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); border-radius: .5rem; margin-bottom: .25rem; white-space: nowrap; overflow: hidden; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: rgba(255,255,255,.12); color:#fff; }
        .sidebar .nav-link i { width: 1.25rem; text-align: center; }
        .main-content { min-width: 0; margin-left: 250px; padding: 2rem; transition: margin-left .2s ease; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: .75rem; }
        .topbar-title { min-width: 0; display:flex; align-items:flex-start; gap:.75rem; }
        .topbar-title > div { min-width:0; }
        .sidebar-toggle { flex: 0 0 auto; width:2.4rem; height:2.4rem; display:inline-flex; align-items:center; justify-content:center; }
        .sidebar-backdrop { display:none; }
        .sidebar-collapsed .sidebar { width: 76px; padding-inline: .75rem; }
        .sidebar-collapsed .sidebar .brand span,
        .sidebar-collapsed .sidebar .nav-label { display:none; }
        .sidebar-collapsed .sidebar .nav-link { padding-inline: .75rem; text-align:center; }
        .sidebar-collapsed .sidebar .nav-link i { margin-right: 0 !important; }
        .sidebar-collapsed .main-content { margin-left: 76px; }
        .btn-brand { background: #1a5c45; border-color: #1a5c45; color: #fff; }
        .btn-brand:hover { background: #14432f; border-color: #14432f; color: #fff; }
        .modal-content { border:0; border-radius:1rem; box-shadow:0 1.25rem 3rem rgba(15,35,25,.2); overflow:hidden; }
        .modal-header { border-bottom:0; padding:1.35rem 1.5rem .5rem; }
        .modal-body { padding:1rem 1.5rem 1.25rem; }
        .modal-footer { border-top:0; padding:.5rem 1.5rem 1.35rem; }
        .confirm-dialog-icon { width:3.25rem; height:3.25rem; display:grid; place-items:center; border-radius:1rem; font-size:1.35rem; }
        .confirm-dialog-icon.danger { background:#fff0f0; color:#c62828; }
        .confirm-dialog-icon.warning { background:#fff6df; color:#9a6700; }
        .confirm-dialog-icon.success { background:#e8f5ed; color:#1a6b43; }
        .confirm-dialog-icon.primary { background:#eaf1ff; color:#2459a6; }
        .confirm-dialog-message { color:#637168; line-height:1.6; }
        @media (max-width: 768px) {
            .sidebar { width:min(84vw, 300px); max-width:300px; transform:translateX(-105%); }
            .sidebar-open .sidebar { transform:translateX(0); }
            .sidebar-collapsed .sidebar { width:min(84vw, 300px); padding:1.5rem 1rem; }
            .sidebar-collapsed .sidebar .brand span,
            .sidebar-collapsed .sidebar .nav-label { display:inline; }
            .sidebar-collapsed .sidebar .nav-link { text-align:left; }
            .sidebar-collapsed .sidebar .nav-link i { margin-right:.5rem !important; }
            .main-content, .sidebar-collapsed .main-content { margin-left:0; padding:1rem; }
            .sidebar-backdrop { position:fixed; inset:0; z-index:1040; border:0; background:rgba(0,0,0,.45); }
            .sidebar-open .sidebar-backdrop { display:block; }
            .sidebar-open { overflow:hidden; }
            .topbar { align-items:flex-start; }
            .topbar-title { flex:1 1 100%; }
            .topbar > .d-flex { width:100%; justify-content:space-between; gap:.5rem !important; }
            .topbar > .d-flex a { overflow-wrap:anywhere; }
            .table-responsive { overflow-x:visible !important; border:0 !important; }
            .table-responsive > .table { width:100%; min-width:0; margin-bottom:0; border-collapse:separate; border-spacing:0; }
            .table-responsive > .table > thead { display:none; }
            .table-responsive > .table > tbody { display:block; }
            .table-responsive > .table > tbody > tr {
                display:block; margin:0 0 .85rem; padding:.4rem .75rem;
                border:1px solid #dee2e6; border-radius:.7rem; background:#fff;
                box-shadow:0 .125rem .35rem rgba(20,40,30,.05);
            }
            .table-responsive > .table > tbody > tr > td {
                display:grid; grid-template-columns:minmax(5.5rem, 34%) minmax(0, 1fr);
                align-items:start; gap:.65rem; width:100%; padding:.55rem .15rem !important;
                border:0 !important; border-bottom:1px solid #edf0ee !important;
                text-align:left !important; overflow-wrap:anywhere; word-break:break-word;
            }
            .table-responsive > .table > tbody > tr > td::before {
                content:attr(data-label); color:#66756d; font-size:.74rem; font-weight:700;
            }
            .table-responsive > .table > tbody > tr > td[data-label=""]::before { display:none; }
            .table-responsive > .table > tbody > tr > td:last-child { border-bottom:0 !important; }
            .table-responsive > .table > tbody > tr > td.table-empty-state { display:block; text-align:center !important; }
            .table-responsive > .table > tbody > tr > td.table-empty-state::before { display:none; }
            .table-responsive > .table > tbody > tr > td[data-label="Aksi"] { display:block; }
            .table-responsive > .table > tbody > tr > td[data-label="Aksi"]::before { display:block; margin-bottom:.4rem; }
            .table-responsive > .table > tbody > tr > td[data-label="Aksi"] .btn-group { flex-wrap:wrap; }
            .table-responsive table[id^="dyntable"] > tbody > tr > td { grid-template-columns:minmax(6.25rem, 38%) minmax(0, 1fr); }
            .table-responsive table[id^="dyntable"] input,
            .table-responsive table[id^="dyntable"] select,
            .table-responsive table[id^="dyntable"] textarea { min-width:0; width:100%; }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="appSidebar" aria-label="Navigasi utama">
        <a href="{{ auth()->user()->homeRoute() === 'admin.dashboard' ? route('admin.dashboard') : route('user.dashboard') }}" class="brand"><img class="brand-logo" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt=""><span>RKB Dapur</span></a>
        <nav class="nav flex-column">
            @auth
                @if (auth()->user()->isAdmin())
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" title="Dashboard"><i class="bi bi-grid me-2"></i><span class="nav-label">Dashboard</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}" title="Kategori Form"><i class="bi bi-tags me-2"></i><span class="nav-label">Kategori Form</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.forms.*') ? 'active' : '' }}" href="{{ route('admin.forms.index') }}" title="Manajemen Form"><i class="bi bi-file-earmark-text me-2"></i><span class="nav-label">Manajemen Form</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" title="Manajemen User"><i class="bi bi-people me-2"></i><span class="nav-label">Manajemen User</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.submissions.*') ? 'active' : '' }}" href="{{ route('admin.submissions.index') }}" title="Submission"><i class="bi bi-clipboard-check me-2"></i><span class="nav-label">Submission</span></a>
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}" title="Pengaturan"><i class="bi bi-gear me-2"></i><span class="nav-label">Pengaturan</span></a>
                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" title="Profil Admin"><i class="bi bi-person-gear me-2"></i><span class="nav-label">Profil Admin</span></a>
                @else
                    <a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}" title="Dashboard"><i class="bi bi-grid me-2"></i><span class="nav-label">Dashboard</span></a>
                    <a class="nav-link {{ request()->routeIs('user.forms.*') ? 'active' : '' }}" href="{{ route('user.forms.index') }}" title="Form Tersedia"><i class="bi bi-journal-text me-2"></i><span class="nav-label">Form Tersedia</span></a>
                    <a class="nav-link {{ request()->routeIs('user.my-submissions.*') ? 'active' : '' }}" href="{{ route('user.my-submissions.index') }}" title="Form Saya"><i class="bi bi-clipboard-check me-2"></i><span class="nav-label">Form Saya</span></a>
                @endif
            @endauth
        </nav>
    </div>

    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Tutup navigasi"></button>

    <div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-labelledby="actionConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="confirm-dialog-icon danger mx-auto mb-3" id="actionConfirmIcon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h2 class="h5 fw-bold mb-2" id="actionConfirmTitle">Konfirmasi tindakan</h2>
                    <p class="confirm-dialog-message small mb-4" id="actionConfirmMessage">Apakah Anda yakin ingin melanjutkan?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger px-3" id="actionConfirmSubmit">Ya, lanjutkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="main-content" id="mainContent">
        <div class="topbar">
            <div class="topbar-title">
                <button class="btn btn-outline-secondary sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka/tutup menu" aria-controls="appSidebar" aria-expanded="true">
                    <i class="bi bi-layout-sidebar-inset" id="sidebarToggleIcon" aria-hidden="true"></i>
                </button>
                <div>
                    <h1 class="h5 fw-bold mb-0">{{ $heading ?? 'Dashboard' }}</h1>
                    <p class="text-muted small mb-0">{{ $subheading ?? '' }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="pwaInstallButton" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-download me-1"></i>Pasang aplikasi
                </button>
                <a href="{{ route('profile.edit') }}" class="small text-muted text-decoration-none">
                    {{ auth()->user()->name }} &middot; {{ auth()->user()->role->display_name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">Keluar</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
        @endif

        {{ $slot }}
    </main>

    <div class="modal fade" id="pwaInstallHelpModal" tabindex="-1" aria-labelledby="pwaInstallHelpTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="confirm-dialog-icon primary mx-auto mb-3"><i class="bi bi-phone"></i></div>
                    <h2 class="h5 fw-bold mb-2" id="pwaInstallHelpTitle">Pasang RKB Dapur</h2>
                    <p class="confirm-dialog-message small mb-3" id="pwaInstallInstructions">Ikuti petunjuk browser untuk memasang aplikasi.</p>
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bundel JS Bootstrap 5 (termasuk Popper) -- WAJIB ada di sini. Tanpa ini,
         semua elemen dengan atribut data-bs-toggle (modal, dropdown, dst.) di
         seluruh aplikasi terlihat normal tapi diam saja saat diklik, karena
         tidak ada JS yang mendengarkan atribut tersebut. Dimuat sebelum
         @stack('scripts') supaya script halaman yang butuh referensi ke
         objek bootstrap.* (kalau ada di fase berikutnya) sudah bisa memakainya. --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (() => {
            const modalElement = document.getElementById('actionConfirmModal');
            const modal = new bootstrap.Modal(modalElement);
            const title = document.getElementById('actionConfirmTitle');
            const message = document.getElementById('actionConfirmMessage');
            const icon = document.getElementById('actionConfirmIcon');
            const confirmButton = document.getElementById('actionConfirmSubmit');
            let pendingForm = null;

            document.addEventListener('submit', event => {
                const form = event.target.closest('form[data-confirm-message]');
                if (!form || form.dataset.confirmBypass === 'true') return;

                event.preventDefault();
                pendingForm = form;
                title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
                message.textContent = form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan?';
                const tone = form.dataset.confirmTone || 'danger';
                const tones = {
                    danger: ['btn-danger', 'bi-exclamation-triangle-fill'],
                    warning: ['btn-warning', 'bi-exclamation-circle-fill'],
                    success: ['btn-success', 'bi-check-circle-fill'],
                    primary: ['btn-primary', 'bi-question-circle-fill'],
                };
                const [buttonClass, iconClass] = tones[tone] || tones.danger;
                confirmButton.className = `btn ${buttonClass} px-3`;
                confirmButton.textContent = form.dataset.confirmButton || 'Ya, lanjutkan';
                icon.className = `confirm-dialog-icon ${tone} mx-auto mb-3`;
                icon.innerHTML = `<i class="bi ${iconClass}"></i>`;
                modal.show();
            });

            confirmButton.addEventListener('click', () => {
                if (!pendingForm) return;
                const form = pendingForm;
                pendingForm = null;
                form.dataset.confirmBypass = 'true';
                modal.hide();
                HTMLFormElement.prototype.submit.call(form);
            });

            modalElement.addEventListener('hidden.bs.modal', () => { pendingForm = null; });
        })();
    </script>

    <script>
        (() => {
            const body = document.body;
            const sidebar = document.getElementById('appSidebar');
            const toggle = document.getElementById('sidebarToggle');
            const icon = document.getElementById('sidebarToggleIcon');
            const backdrop = document.getElementById('sidebarBackdrop');
            const mobile = window.matchMedia('(max-width: 768px)');
            const getSavedCollapsed = () => {
                try { return localStorage.getItem('rkb-sidebar-collapsed') === 'true'; }
                catch (_) { return false; }
            };
            const saveCollapsed = value => {
                try { localStorage.setItem('rkb-sidebar-collapsed', String(value)); }
                catch (_) { /* Sidebar tetap bisa dipakai tanpa penyimpanan preferensi. */ }
            };

            const updateToggle = () => {
                const open = mobile.matches ? body.classList.contains('sidebar-open') : !body.classList.contains('sidebar-collapsed');
                toggle.setAttribute('aria-expanded', String(open));
                icon.className = mobile.matches
                    ? (open ? 'bi bi-x-lg' : 'bi bi-list')
                    : (open ? 'bi bi-layout-sidebar-inset' : 'bi bi-layout-sidebar');
                toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
            };

            if (!mobile.matches && getSavedCollapsed()) {
                body.classList.add('sidebar-collapsed');
            }

            toggle.addEventListener('click', () => {
                if (mobile.matches) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    saveCollapsed(body.classList.contains('sidebar-collapsed'));
                }
                updateToggle();
            });

            const closeMobileSidebar = () => {
                body.classList.remove('sidebar-open');
                updateToggle();
            };

            backdrop.addEventListener('click', closeMobileSidebar);
            sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
                if (mobile.matches) closeMobileSidebar();
            }));
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && mobile.matches) closeMobileSidebar();
            });
            mobile.addEventListener('change', () => {
                body.classList.remove('sidebar-open');
                if (!mobile.matches && getSavedCollapsed()) {
                    body.classList.add('sidebar-collapsed');
                }
                updateToggle();
            });
            updateToggle();
        })();
    </script>

    <script>
        document.querySelectorAll('.table-responsive table').forEach(table => {
            const headings = Array.from(table.querySelectorAll('thead th'))
                .map(heading => heading.textContent.trim());

            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = Array.from(row.querySelectorAll(':scope > td'));
                if (cells.length === 1 && cells[0].colSpan > 1) {
                    cells[0].classList.add('table-empty-state');
                    return;
                }
                cells.forEach((cell, index) => {
                    if (!cell.hasAttribute('data-label')) {
                        cell.dataset.label = headings[index] || '';
                    }
                });
            });
        });
    </script>

    @stack('scripts')
    <script src="{{ rtrim(request()->getBaseUrl(), '/') }}/pwa.js?v=6" defer></script>
</body>
</html>
