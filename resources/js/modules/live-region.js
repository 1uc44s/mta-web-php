// Ekran okuyucu duyuruları için tek aria-live bölgesi (document.blade.php → #sr-live).
export function announce(message) {
    const region = document.getElementById('sr-live');
    if (!region) return;
    region.textContent = '';
    window.requestAnimationFrame(() => {
        region.textContent = message;
    });
}
