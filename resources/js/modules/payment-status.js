// Ödeme dönüş sayfası — docs/SRS.md FR-PAY-004: sipariş `pending` iken 5 sn'de bir durum sorgusu; değişince sayfa yenilenir.
// Bakiye istemcide hiçbir zaman değiştirilmez; sunucu webhook'u işlediğinde yeni durum görünür.
const POLL_MS = 5_000;
const MAX_POLLS = 120;

export function initPaymentStatus() {
    const box = document.querySelector('[data-payment-status-url]');
    if (!box) {
        return;
    }

    const url = box.getAttribute('data-payment-status-url');
    let polls = 0;

    const timer = setInterval(async () => {
        polls += 1;
        if (polls > MAX_POLLS) {
            clearInterval(timer);
            return;
        }
        if (document.visibilityState !== 'visible') {
            return;
        }
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                return;
            }
            const body = await response.json();
            if (body?.data && body.data.pending === false) {
                clearInterval(timer);
                window.location.reload();
            }
        } catch {
            // Ağ hatası: bir sonraki turda tekrar denenir.
        }
    }, POLL_MS);
}
