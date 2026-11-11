// Küçük arayüz davranışları: header kaydırma çizgisi, toast otomatik kapanma, çift gönderim engeli,
// details menülerinin dışarı tıklayınca/Esc ile kapanması.
export function initUi() {
    const header = document.querySelector('[data-site-header]');
    if (header) {
        const update = () => (header.dataset.scrolled = String(window.scrollY > 8));
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        let timer = window.setTimeout(() => toast.remove(), 5000);
        toast.addEventListener('mouseenter', () => window.clearTimeout(timer));
        toast.addEventListener('mouseleave', () => (timer = window.setTimeout(() => toast.remove(), 2000)));
    });

    // Formlar: gönderimde butonu kilitle (brief §3.5 — çift işlem yok).
    document.addEventListener('submit', (event) => {
        const button = event.target.querySelector('[data-submit-once]');
        if (!button || event.defaultPrevented) return;
        window.setTimeout(() => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });
    });

    const menus = () => document.querySelectorAll('details[data-theme-menu][open]');
    document.addEventListener('click', (event) => {
        menus().forEach((menu) => !menu.contains(event.target) && menu.removeAttribute('open'));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        menus().forEach((menu) => {
            menu.removeAttribute('open');
            menu.querySelector('summary')?.focus();
        });
    });
}
