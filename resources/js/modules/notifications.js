// Okunmamış bildirim sayacı — docs/SRS.md FR-UCP-003: sekme görünürken 60 sn'de bir, ETag ile.
const INTERVAL = 60_000;

export function initNotificationBadge() {
    const badge = document.querySelector('[data-unread-badge][data-count-url]');
    if (!badge) {
        return;
    }

    const url = badge.getAttribute('data-count-url');
    let etag = null;

    const tick = async () => {
        if (document.visibilityState !== 'visible') {
            return;
        }
        try {
            const headers = { Accept: 'application/json' };
            if (etag) {
                headers['If-None-Match'] = etag;
            }
            const response = await fetch(url, { headers, credentials: 'same-origin' });
            if (response.status === 304 || !response.ok) {
                return;
            }
            etag = response.headers.get('ETag');
            const body = await response.json();
            const unread = Number(body?.data?.unread ?? 0);
            badge.textContent = unread > 99 ? '99+' : String(unread);
            badge.classList.toggle('hidden', unread === 0);
        } catch {
            // Ağ hatası: bir sonraki turda tekrar denenir.
        }
    };

    setInterval(tick, INTERVAL);
    document.addEventListener('visibilitychange', tick);
}
