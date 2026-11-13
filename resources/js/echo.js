// Gerçek zaman (Faz 10) — docs/ARCHITECTURE.md §9. Bağlantı ayarları derleme zamanı VITE_* değil, sunucunun yazdığı
// <meta name="mrp-realtime"> etiketinden okunur; etiket yoksa (Reverb kapalı / oturum yok) hiçbir bağlantı açılmaz ve
// Echo + pusher-js paketleri hiç indirilmez (dinamik import). Kapalıyken polling modülleri olduğu gibi çalışır.
import { announce } from './modules/live-region';

export async function initRealtime() {
    const meta = document.querySelector('meta[name="mrp-realtime"]');
    if (!meta) {
        return;
    }

    let config;
    try {
        config = JSON.parse(meta.getAttribute('content') || '');
    } catch {
        return;
    }
    if (!config?.key || !config?.host || !config?.user) {
        return;
    }

    const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')]);
    window.Pusher = Pusher;

    const tls = config.scheme === 'https';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: tls,
        enabledTransports: tls ? ['wss'] : ['ws'],
        authEndpoint: '/broadcasting/auth',
        auth: { headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } },
    });

    // Okunmamış bildirim rozeti: anında artır (polling yine ETag ile doğrular).
    echo.private(`user.${config.user}`).notification(() => {
        const badge = document.querySelector('[data-unread-badge]');
        if (!badge) {
            return;
        }
        const next = (Number.parseInt(badge.textContent, 10) || 0) + 1;
        badge.textContent = next > 99 ? '99+' : String(next);
        badge.classList.remove('hidden');
    });

    // "Yeni hareket — yenile" bantları: data-realtime-banner="kanal1 kanal2", data-realtime-event="support.message".
    document.querySelectorAll('[data-realtime-banner][data-realtime-event]').forEach((banner) => {
        const event = `.${banner.getAttribute('data-realtime-event')}`;
        (banner.getAttribute('data-realtime-banner') || '').split(/\s+/).filter(Boolean).forEach((channel) => {
            echo.private(channel).listen(event, () => {
                if (banner.hidden) {
                    banner.hidden = false;
                    announce(banner.textContent.trim());
                }
            });
        });
    });
}
