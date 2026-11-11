// Tema / düşük efekt tercihi anında uygulanır, arka planda kaydedilir (docs/DESIGN.md §4).
// Çerez onayı formları da sayfa yenilemeden gönderilir.
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const postForm = (form) =>
    fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: new FormData(form),
        credentials: 'same-origin',
    });

const resolveTheme = (preference) =>
    preference === 'system' ? (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark') : preference;

export function initPreferences() {
    const themeForm = document.querySelector('[data-theme-form]');
    themeForm?.addEventListener('change', () => {
        const data = new FormData(themeForm);
        const theme = data.get('theme') || 'dark';
        const effects = data.getAll('effects').includes('low') ? 'low' : 'full';
        const root = document.documentElement;
        root.dataset.theme = resolveTheme(theme);
        root.dataset.themePref = theme;
        root.dataset.effects = effects;
        postForm(themeForm).catch(() => themeForm.submit());
    });

    // Sistem teması seçiliyken işletim sistemi teması değişirse takip et.
    matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        if (document.documentElement.dataset.themePref === 'system') {
            document.documentElement.dataset.theme = resolveTheme('system');
        }
    });

    document.querySelectorAll('[data-cookie-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await postForm(form);
                form.closest('[data-cookie-banner]')?.remove();
            } catch {
                form.submit();
            }
        });
    });
}
