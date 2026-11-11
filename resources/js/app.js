// Meridyen Roleplay — istemci girişi.
// Alpine CSP build: ifadeler eval ile değerlendirilmez; bileşenler Alpine.data ile kaydedilir (docs/RULES.md §9).
// Public sitenin davranışları framework'süz küçük modüllerdir; JS kapalıyken sayfalar çalışmaya devam eder.
import Alpine from '@alpinejs/csp';
import { initCopy } from './modules/copy';
import { initDialogs } from './modules/dialogs';
import { initLinkCode } from './modules/link-code';
import { initNotificationBadge } from './modules/notifications';
import { initPreferences } from './modules/preferences';
import { initPwa } from './modules/pwa';
import { initRulesFilter } from './modules/rules-filter';
import { initServerStatus } from './modules/server-status';
import { initUi } from './modules/ui';

window.Alpine = Alpine;

const boot = () => {
    initDialogs();
    initCopy();
    initPreferences();
    initRulesFilter();
    initServerStatus();
    initUi();
    initNotificationBadge();
    initLinkCode();
    initPwa();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}

Alpine.start();
