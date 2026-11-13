// Web Push aboneliği (Faz 10) — docs/ARCHITECTURE.md §8.1, ROUTES §5.1. Yalnızca <meta name="mrp-push"> varsa
// (özellik açık + VAPID anahtarı) ve service worker kayıtlıysa çalışır. İzin yalnızca kullanıcının tıklamasıyla istenir.
const toUint8 = (base64) => {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(window.atob(padded), (c) => c.charCodeAt(0));
};

const post = (url, body) => fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
    },
    body: JSON.stringify(body),
});

export function initPush() {
    const root = document.querySelector('[data-push-control]');
    if (!root) {
        return;
    }

    const meta = document.querySelector('meta[name="mrp-push"]');
    const status = root.querySelector('[data-push-status]');
    const enable = root.querySelector('[data-push-enable]');
    const disable = root.querySelector('[data-push-disable]');
    const setStatus = (key) => {
        if (status) {
            status.textContent = root.getAttribute(`data-msg-${key}`) || '';
        }
    };

    if (!meta || !window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        setStatus('unsupported');
        return;
    }

    const publicKey = meta.getAttribute('content');
    const subscribeUrl = meta.getAttribute('data-subscribe-url');
    const unsubscribeUrl = meta.getAttribute('data-unsubscribe-url');

    const render = async () => {
        const registration = await navigator.serviceWorker.getRegistration('/');
        if (!registration) {
            setStatus('unsupported');
            return;
        }
        const subscription = await registration.pushManager.getSubscription();
        enable.hidden = subscription !== null || Notification.permission === 'denied';
        disable.hidden = subscription === null;
        setStatus(Notification.permission === 'denied' ? 'denied' : (subscription ? 'on' : 'off'));
    };

    enable?.addEventListener('click', async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                await render();
                return;
            }
            const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toUint8(publicKey) });
            const json = subscription.toJSON();
            const encodings = window.PushManager.supportedContentEncodings || ['aes128gcm'];
            const response = await post(subscribeUrl, {
                endpoint: json.endpoint,
                keys: { p256dh: json.keys?.p256dh, auth: json.keys?.auth },
                contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
            });
            if (!response.ok) {
                await subscription.unsubscribe();
                setStatus('error');
                return;
            }
        } catch {
            setStatus('error');
            return;
        }
        await render();
    });

    disable?.addEventListener('click', async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                await post(unsubscribeUrl, { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
            }
        } catch {
            setStatus('error');
            return;
        }
        await render();
    });

    render().catch(() => setStatus('unsupported'));
}
