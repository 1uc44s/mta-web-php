// Panoya kopyalama (US-PUB-02 KK1): etiket 2 sn "Kopyalandı" olur ve ekran okuyucuya duyurulur.
import { announce } from './live-region';

export function initCopy() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy]');
        if (!button) return;

        const label = button.querySelector('[data-copy-label]');
        const original = label?.textContent ?? '';
        const copiedText = button.dataset.copiedLabel || 'Kopyalandı';

        try {
            await navigator.clipboard.writeText(button.dataset.copy);
        } catch {
            // Clipboard API yoksa (http) geçici seçimle kopyala.
            const field = Object.assign(document.createElement('textarea'), { value: button.dataset.copy });
            field.setAttribute('readonly', '');
            field.className = 'sr-only';
            document.body.append(field);
            field.select();
            document.execCommand('copy');
            field.remove();
        }

        if (label) label.textContent = copiedText;
        button.dataset.copied = 'true';
        announce(copiedText);

        window.setTimeout(() => {
            if (label) label.textContent = original;
            delete button.dataset.copied;
        }, 2000);
    });
}
