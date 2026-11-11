// Sunucu durumu polling (docs/ARCHITECTURE.md §9): 30 sn, yalnızca sekme görünürken, hata halinde üstel geri çekilme.
const ENDPOINT = '/api/v1/public/server-status';
const BASE_INTERVAL = 30_000;
const MAX_INTERVAL = 300_000;

export function initServerStatus() {
    const pills = () => document.querySelectorAll('[data-status-pill]');
    if (pills().length === 0) return;

    let interval = BASE_INTERVAL;
    let timer = null;
    let etag = null;

    const render = (data) => {
        pills().forEach((pill) => {
            pill.dataset.status = data.status;
            const label = pill.querySelector('[data-field="label"]');
            if (label) label.textContent = pill.dataset[`label${data.status[0].toUpperCase()}${data.status.slice(1)}`] ?? data.label;

            const players = pill.querySelector('[data-field="players"]');
            if (players) {
                const show = data.players !== null && data.players !== undefined;
                players.classList.toggle('hidden', !show);
                if (show) {
                    players.textContent = (pill.dataset.playersTemplate || '{players} / {max}')
                        .replace('{players}', data.players)
                        .replace('{max}', data.max_players ?? '—');
                }
            }
        });
    };

    const tick = async () => {
        if (document.visibilityState !== 'visible') return schedule();
        try {
            const response = await fetch(ENDPOINT, {
                headers: { Accept: 'application/json', ...(etag ? { 'If-None-Match': etag } : {}) },
                credentials: 'same-origin',
            });
            if (response.status === 200) {
                etag = response.headers.get('ETag');
                const body = await response.json();
                if (body?.data) render(body.data);
            } else if (response.status !== 304) {
                throw new Error(String(response.status));
            }
            interval = BASE_INTERVAL;
        } catch {
            interval = Math.min(interval * 2, MAX_INTERVAL);
        }
        schedule();
    };

    const schedule = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(tick, interval);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            window.clearTimeout(timer);
            tick();
        }
    });

    schedule();
}
