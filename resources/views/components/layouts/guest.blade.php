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
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(160deg, #0f3d2e 0%, #1a5c45 45%, #eab308 150%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .auth-card .card-body { padding: 2.5rem; }
        .brand-badge {
            width: 96px; height: 96px; border-radius: 50%;
            object-fit: cover; background: #fff; padding: 2px;
            box-shadow: 0 4px 16px rgba(0,0,0,.18);
        }
        .btn-brand { background: #1a5c45; border-color: #1a5c45; color: #fff; }
        .btn-brand:hover { background: #14432f; border-color: #14432f; color: #fff; }
    </style>
</head>
<body>
    <div class="card auth-card">
        <div class="card-body">
            {{ $slot }}
            <div class="border-top mt-4 pt-3">
                <button type="button" id="pwaInstallButton" class="btn btn-outline-success w-100">
                    <i class="bi bi-download me-1"></i>Pasang aplikasi RKB Dapur
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pwaInstallHelpModal" tabindex="-1" aria-labelledby="pwaInstallHelpTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <img class="brand-badge mx-auto mb-3" src="{{ rtrim(request()->getBaseUrl(), '/') }}/logo.png" alt="Logo RKB Dapur">
                    <h2 class="h5 fw-bold mb-2" id="pwaInstallHelpTitle">Pasang RKB Dapur</h2>
                    <p class="small text-muted mb-3" id="pwaInstallInstructions">Ikuti petunjuk browser untuk memasang aplikasi.</p>
                    <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ rtrim(request()->getBaseUrl(), '/') }}/pwa.js?v=6" defer></script>
</body>
</html>
