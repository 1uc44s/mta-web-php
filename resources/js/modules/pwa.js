// PWA — docs/SRS.md FR-PWA-002/004/005.
// SW yalnızca güvenli bağlamda ve <meta name="mrp-sw"> doluysa kaydedilir. Bekleyen yeni sürüm için
// "Yeni sürüm hazır — Yenile" toast'ı gösterilir; çıkış formunda runtime cache temizlenir.
export function initPwa() {
    const meta = document.querySelector('meta[name="mrp-sw"]');
    const swUrl = meta?.getAttribute('content');

    if (!swUrl || !window.isSecureContext || !('serviceWorker' in navigator)) {
        return;
    }

    navigator.serviceWorker.register(swUrl, { scope: '/' }).then((registration) => {
        const offerUpdate = (worker) => showUpdateToast(() => worker.postMessage('SKIP_WAITING'));

        if (registration.waiting) {
            offerUpdate(registration.waiting);
        }

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            worker?.addEventListener('statechange', () => {
                if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                    offerUpdate(worker);
                }
            });
        });
    }).catch(() => {});

    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!reloading) {
            reloading = true;
            window.location.reload();
        }
    });

    document.addEventListener('submit', (event) => {
        if (event.target.matches('[data-logout-form], form[action$="/logout"]')) {
            navigator.serviceWorker.controller?.postMessage('CLEAR_RUNTIME_CACHE');
        }
    });
}

function showUpdateToast(onConfirm) {
    if (document.querySelector('[data-sw-toast]')) {
        return;
    }

    const toast = document.createElement('div');
    toast.setAttribute('data-sw-toast', '');
    toast.setAttribute('role', 'status');
    toast.className = 'fixed inset-x-4 bottom-20 z-50 mx-auto flex max-w-md items-center gap-3 rounded-md border border-border-strong bg-surface-1 px-4 py-3 text-sm text-text shadow-2 sm:right-4 sm:left-auto sm:mx-0 lg:bottom-4';

    const text = document.createElement('span');
    text.textContent = 'Yeni sürüm hazır.';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ml-auto rounded-md bg-accent px-3 py-1.5 font-medium text-accent-fg';
    button.textContent = 'Yenile';
    button.addEventListener('click', () => {
        button.disabled = true;
        onConfirm();
    });

    toast.append(text, button);
    document.body.append(toast);
}
