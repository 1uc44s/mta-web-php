// Bağlama kodu kartı — docs/SRS.md FR-ACC-007, DESIGN §6.2 x-brand.link-code.
// Geri sayım + sekme görünürken 5 sn'de bir link-status sorgusu. Uç nokta kodu asla döndürmez.
const POLL_MS = 5_000;
const CIRCUMFERENCE = 97.4;

export function initLinkCode() {
    const card = document.querySelector('[data-link-code]');
    if (!card) {
        return;
    }

    const total = Number(card.dataset.expiresIn || 0);
    const deadline = Date.now() + total * 1000;
    const timer = card.querySelector('[data-link-code-timer]');
    const ring = card.querySelector('[data-link-code-ring]');
    const cells = card.querySelector('[data-link-code-cells]');
    const state = card.querySelector('[data-link-code-state]');
    const url = card.dataset.statusUrl;
    let finished = false;
    let countdown = null;
    let pollHandle = null;

    const finish = (message, tone) => {
        finished = true;
        clearInterval(countdown);
        clearInterval(pollHandle);
        if (state) {
            state.textContent = message;
            state.classList.remove('text-text-muted');
            state.classList.add(tone);
        }
        document.querySelector('[data-link-code-refresh]')?.classList.remove('hidden');
    };

    const expire = () => {
        cells?.classList.add('opacity-40');
        finish(state?.dataset.msgExpired ?? '', 'text-text-muted');
    };

    const render = () => {
        const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
        if (timer) {
            timer.textContent = `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}`;
        }
        if (ring && total > 0) {
            ring.setAttribute('stroke-dashoffset', String(CIRCUMFERENCE * (1 - left / total)));
        }
        if (left === 0 && !finished) {
            expire();
        }
    };

    const poll = async () => {
        if (finished || !url || document.visibilityState !== 'visible') {
            return;
        }
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                return;
            }
            const body = await response.json();
            const data = body?.data ?? {};
            if (data.state === 'linked') {
                const template = state?.dataset.msgLinked ?? '';
                finish(template.replace(':name', data.account_name ?? ''), 'text-success');
            } else if (data.state === 'expired' || data.state === 'none') {
                expire();
            }
        } catch {
            // Ağ hatası: bir sonraki turda tekrar denenir.
        }
    };

    countdown = setInterval(render, 1000);
    render();
    pollHandle = setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', poll);
}
