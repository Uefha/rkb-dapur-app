(() => {
    const installButton = document.getElementById('pwaInstallButton');
    const helpModal = document.getElementById('pwaInstallHelpModal');
    const instructions = document.getElementById('pwaInstallInstructions');
    const manifestLink = document.querySelector('link[rel="manifest"]');
    let installPrompt = null;

    if ('serviceWorker' in navigator && manifestLink) {
        const manifestUrl = new URL(manifestLink.href, window.location.href);
        const appBaseUrl = new URL('.', manifestUrl);
        window.addEventListener('load', () => {
            navigator.serviceWorker.register(new URL('sw.js', appBaseUrl), {
                scope: appBaseUrl.pathname,
            }).catch(error => console.error('PWA service worker gagal didaftarkan:', error));
        });
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        if (installButton) installButton.hidden = true;
        return;
    }

    if (installButton) installButton.hidden = false;

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
    });

    installButton?.addEventListener('click', async () => {
        if (installPrompt) {
            try {
                await installPrompt.prompt();
                const choice = await installPrompt.userChoice;
                if (choice?.outcome === 'accepted') installButton.hidden = true;
                installPrompt = null;
                return;
            } catch (error) {
                console.error('Prompt pemasangan PWA gagal dibuka:', error);
            }
        }

        if (!helpModal || !window.bootstrap) return;
        const isIOS = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
        const isAndroid = /android/i.test(window.navigator.userAgent);
        const deviceInstructions = isIOS
            ? 'Di Safari, ketuk tombol Bagikan lalu pilih Tambahkan ke Layar Utama.'
            : isAndroid
                ? 'Buka menu browser (⋮), lalu pilih Instal aplikasi atau Tambahkan ke layar utama.'
                : 'Gunakan menu browser atau ikon instalasi pada bilah alamat, lalu pilih Instal RKB Dapur.';
        instructions.textContent = `${deviceInstructions} Jika opsi pemasangan tidak muncul, pastikan situs dibuka melalui HTTPS.`;
        bootstrap.Modal.getOrCreateInstance(helpModal).show();
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        if (installButton) installButton.hidden = true;
    });
})();
