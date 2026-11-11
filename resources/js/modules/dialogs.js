// Native <dialog> açma/kapama (docs/DESIGN.md §6.1). Açan öğe data-dialog-open="id" taşır;
// JS yoksa öğenin href'i çalışır (progressive enhancement).
const openers = new WeakMap();

export function initDialogs() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-dialog-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.dialogOpen);
            if (dialog instanceof HTMLDialogElement) {
                event.preventDefault();
                document.querySelectorAll('dialog[open]').forEach((open) => open !== dialog && open.close());
                openers.set(dialog, opener);
                dialog.showModal();
            }
            return;
        }

        // Arka plana (dialog öğesinin kendisine) tıklanınca kapat.
        if (event.target instanceof HTMLDialogElement && event.target.open) {
            const rect = event.target.getBoundingClientRect();
            const inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) event.target.close();
        }
    });

    // Kapanınca odağı açan öğeye geri ver (NFR-A11Y-004).
    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('close', () => openers.get(dialog)?.focus());
    });
}
