// Kurallar sayfası istemci tarafı arama (docs/SRS.md FR-PUB-010). Türkçe büyük/küçük harf duyarsız.
import { announce } from './live-region';

const normalize = (value) => value.toLocaleLowerCase('tr-TR').normalize('NFKD').replace(/\p{Diacritic}/gu, '');

export function initRulesFilter() {
    const input = document.querySelector('[data-rules-filter]');
    if (!input) return;

    const items = [...document.querySelectorAll('[data-rule-item]')].map((el) => ({ el, text: normalize(el.dataset.search || '') }));
    const groups = [...document.querySelectorAll('[data-rule-group]')];
    const empty = document.querySelector('[data-rules-empty]');
    let debounce = null;

    input.addEventListener('input', () => {
        window.clearTimeout(debounce);
        debounce = window.setTimeout(() => {
            const query = normalize(input.value.trim());
            let visible = 0;
            items.forEach(({ el, text }) => {
                const match = query === '' || text.includes(query);
                el.hidden = !match;
                if (match) visible++;
            });
            groups.forEach((group) => {
                group.hidden = !group.querySelector('[data-rule-item]:not([hidden])');
            });
            if (empty) empty.hidden = visible > 0;
            if (query !== '') announce(`${visible} kural bulundu`);
        }, 120);
    });
}
