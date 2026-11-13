// Bölüm girişi (v2, docs/DESIGN.md §3.4): [data-reveal] öğeleri görünür alana girince bir kez belirir.
// html.js-reveal yalnızca burada eklenir: betik çalışmazsa CSS hiçbir şeyi gizlemez. Azaltılmış hareket / düşük
// efekt modunda CSS geçişi kapalıdır, öğeler doğrudan görünür.
export function initReveal() {
    const items = document.querySelectorAll('[data-reveal]');
    if (items.length === 0 || !('IntersectionObserver' in window)) {
        return;
    }

    document.documentElement.classList.add('js-reveal');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    items.forEach((item) => observer.observe(item));
}
