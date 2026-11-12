# Meridyen Roleplay — Web Platformu

MTA:SA roleplay topluluğu için web platformu: public site, oyuncu paneli (`/panel`), yönetim paneli (`/admin`) ve MTA köprüsü (`/api/v1/game`).
Laravel 13 · PHP 8.4 · MariaDB 10.11 · Blade + Tailwind CSS v4 + Alpine.js (CSP build).

> **Tek doğruluk kaynağı:** [`docs/`](docs/README.md). Her özellik önce dokümana işlenir, sonra kodlanır. Bu README, her fazın sonunda güncellenen **teknik özettir**; ayrıntılar docs'tadır.

## Faz durumu

| Faz | Kapsam | Durum |
|---|---|---|
| 0 | Dokümantasyon | ✅ |
| 1 | Temel kurulum (Laravel, güvenlik başlıkları, kalite araçları) | ✅ |
| 2 | Tasarım sistemi + public site | ✅ |
| 3 | Kimlik doğrulama | ✅ |
| 4 | RBAC, branşlar, yönetim paneli iskeleti | ✅ |
| 5 | CMS, Akademi, mesaj şablon motoru | ✅ |
| 6 | Oyuncu paneli, destek, PWA | ✅ |
| 7 | MTA köprüsü + Lua resource | ✅ |
| 8 | Başvurular, moderasyon, oyun komutları, cihazlar | ✅ (özellik bayrakları varsayılan kapalı) |
| 9 | Ekonomi ve ödeme | ⏳ |
| 10 | Gelişmiş kanallar | ⏳ |
| 11 | Sertleştirme ve yayın | 🟡 R1 yerel kısım ✅ (güvenlik testleri, audit, yedek/geri yükleme testi, izleme, operasyon dokümanı); production kurulumu, hukuk ve marka onayı açık |

---

## 1. Kurulum (Windows + XAMPP)

**Gereksinimler:** PHP 8.4 (`intl, gd, zip, sodium, pdo_mysql` açık), MariaDB 10.11+ / MySQL 8.4, Composer 2, Node 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

`.env` içinde doldur: `DB_PASSWORD`, `BRIDGE_SERIAL_PEPPER` (64 karakter hex: `php -r "echo bin2hex(random_bytes(32));"`).

```bash
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

- Yerel ortamda (`APP_ENV=local`) `DemoSeeder` örnek içerik ekler. Örnek içerikler "Örnek içerik" notuyla işaretlidir.
- XAMPP Apache ile `http://roleplay-web.test` kullanmak için hosts ve vhost adımları: [docs/TODO.md §2.1 / 1.6](docs/TODO.md).
- Tasarım geliştirirken `npm run dev` çalıştır. Tailwind yalnızca derleme anında var olan sınıfları üretir; yeni view ekledikten sonra `npm run build` gerekir.

### Komutlar

| Komut | Ne yapar |
|---|---|
| `composer test` | Pest (Unit + Feature + Arch). MariaDB test veritabanı `roleplay_web_test` kullanılır |
| `composer lint` / `composer lint:fix` | Laravel Pint (PSR-12 + `declare_strict_types`) |
| `composer analyse` | Larastan seviye 6 |
| `npm run build` | Vite production build |
| `php artisan db:seed --class=ContentSeeder` | Sistem içeriklerini (ana sayfa, yasal sayfalar, kurallar, SSS, Akademi, menü) yeniden yükler |

---

## 2. Web rotaları

Tam katalog: [docs/ROUTES.md](docs/ROUTES.md).

### Public (Faz 2)

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/` | `public.home` | CMS bölümlerinden ana sayfa |
| GET | `/about` | `public.about` | |
| GET | `/systems`, `/systems/{slug}` | `public.systems.*` | Yalnızca yayında + oyunda aktif sistemler |
| GET | `/jobs`, `/jobs/{slug}` | `public.jobs.*` | `?class=legal\|special\|event` |
| GET | `/factions`, `/factions/{slug}` | `public.factions.*` | `?type=legal\|illegal\|government\|private` |
| GET | `/start` | `public.start` | Akademi onboarding dersinden rehber + `#katil` |
| GET | `/rules` | `public.rules` | `#rp-1` gibi kod çapaları, istemci tarafı arama |
| GET | `/academy`, `/academy/{course}`, `/academy/{course}/{topic}` | `public.academy.*` | |
| GET | `/news`, `/news/category/{slug}`, `/news/{slug}` | `public.news.*` | 12/sayfa |
| GET | `/events`, `/events/{slug}` | `public.events.*` | |
| GET | `/faq` | `public.faq` | FAQPage JSON-LD |
| GET / POST | `/contact` | `public.contact`, `.store` | Honeypot + 3 sn min. süre + 5/saat/IP |
| GET | `/privacy`, `/kvkk`, `/terms`, `/cookies` | `public.legal.*` | Taslak metinler (hukuk kontrolü bekliyor) |
| GET | `/p/{slug}` | `public.page` | Ek standart sayfalar |
| POST | `/preferences/theme` | `public.preferences.theme` | `theme=dark\|light\|system`, `effects=full\|low` |
| POST | `/cookie-consent` | `public.cookie-consent` | `analytics=0\|1` |
| GET | `/sitemap.xml`, `/robots.txt`, `/feed.xml` | `public.sitemap` … | |
| GET | `/up` | — | Sağlık kontrolü |

### Kimlik doğrulama (Faz 3)

Kayıt ve e-posta doğrulama kendi controller'larımızdadır; giriş, çıkış, parola sıfırlama, 2FA ve parola onayı Fortify'dandır (docs/PRD.md D-06, D-15).

| Yöntem | URL | Ad | Middleware / limit | Not |
|---|---|---|---|---|
| GET / POST | `/register` | `register`, `register.store` | guest · 5/saat/IP | Otomatik giriş yok. Kayıtlı e-postada aynı yanıt + hesap sahibine bilgi e-postası |
| GET | `/register/check-email` | `register.check-email` | guest | "E-postanı kontrol et" |
| GET | `/email/verify/{ulid}/{hash}` | `verification.verify` | signed · 6/dk | **Oturum gerekmez.** `hash = sha256(email)`, 60 dk geçerli; başarıda `/login` |
| GET | `/email/verify` | `verification.notice` | auth | |
| POST | `/email/verification-notification` | `verification.send` | auth · 6/saat | Yeniden gönder |
| GET / POST | `/login` | `login`, `login.store` | guest · 5/dk (tanımlayıcı+IP), 30/saat/IP | `login` alanı: kullanıcı adı **veya** e-posta |
| POST | `/logout` | `logout` | auth | Yanıtta `Clear-Site-Data: "cache"` |
| GET / POST | `/forgot-password` | `password.request`, `password.email` | guest · 3/10 dk | Bilinmeyen e-postada da aynı yanıt |
| GET / POST | `/reset-password/{token}`, `/reset-password` | `password.reset`, `password.update` | guest · 3/10 dk | Başarıda tüm oturumlar silinir, bildirim e-postası |
| GET / POST | `/two-factor-challenge` | `two-factor.login`, `.store` | guest | TOTP veya kurtarma kodu |
| GET / POST | `/user/confirm-password` | `password.confirm` | auth | Hassas işlemler öncesi |
| GET | `/panel` | `panel.dashboard` | web, auth, active | Faz 3 iskeleti; tam panel Faz 6 |

**Parola kuralları:** 10–128 karakter, sızıntı listesinde olmamalı (HIBP k-anonymity), kullanıcı adını / e-posta yerel kısmını içermemeli. Hash: **Argon2id**.
**Askı:** `status=suspended` hesap girişte gerekçe + bitiş tarihiyle reddedilir; süresi dolan askı girişte kalkar; oturum açıkken askıya alınan kullanıcı `active` middleware ile sonraki istekte çıkarılır.
**E-posta (yerel):** `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync` → e-postalar `storage/logs/laravel-*.log` içine yazılır. Doğrulama bağlantısı `APP_URL` hostuyla imzalanır; bağlantıyı aynı hosttan açın.

### Yönetim paneli — ACP (Faz 4)

**İlk süper admini oluşturma** (seed ile oluşmaz):

```bash
php artisan mrp:make-super-admin sen@ornek.com
```

Hesap varsa ona rol verilir, yoksa kullanıcı adı ve parola **interaktif** sorulur. Ardından `/login` → `/admin` açıldığında 2FA kurulumu (`/panel/security/two-factor`) zorunlu tutulur: QR'ı bir kimlik doğrulama uygulamasıyla okut, kodu gir, kurtarma kodlarını sakla.

Middleware zinciri: `web, auth, verified, active, staff, mfa, admin.idle (30 dk), admin.activity`. Personel olmayan → **404**, 2FA yok → kurulum sayfası, izin yok → **403**. "PC" = parola onayı (10 dk).

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | personel |
| GET | `/admin/system/health` | `admin.system.health` | `settings.read` |
| POST | `/admin/system/failed-jobs/{uuid}/retry` | `admin.system.failed-jobs.retry` | `settings.update` |
| GET | `/admin/users`, `/admin/users/{ulid}` | `admin.users.index/show` | `users.read` (e-posta `users.read_pii` yoksa maskeli) |
| GET | `/admin/users/{ulid}/security` | `admin.users.security` | `users.security_read` |
| POST | `/admin/users/{ulid}/status` | `admin.users.status` | `users.suspend` + PC, gerekçe zorunlu, hiyerarşi |
| POST | `/admin/users/{ulid}/resend-verification` | `admin.users.resend-verification` | `users.update`, 3/saat |
| GET | `/admin/staff`, `/admin/staff/{ulid}` | `admin.staff.index/show` | `staff.read` |
| POST | `/admin/staff/{ulid}/roles` | `admin.staff.roles.assign` | `staff.manage` + PC |
| POST | `/admin/staff/{ulid}/roles/{role}/revoke` | `admin.staff.roles.revoke` | `staff.manage` + PC (son süper admin korunur) |
| GET | `/admin/roles`, `/admin/roles/{role}/edit` | `admin.roles.index/edit` | `roles.read` |
| GET/POST | `/admin/roles/create`, `/admin/roles`, `/admin/roles/{role}`, `/admin/roles/{role}/delete` | `admin.roles.create/store/update/destroy` | `roles.manage` (yalnızca süper admin) + PC; sistem rolleri salt okunur |
| GET | `/admin/branches`, `/admin/branches/{id}` | `admin.branches.index/show` | `staff.read` |
| GET/POST | `/admin/branches/create`, `/admin/branches`, `/admin/branches/{id}` | `admin.branches.create/store/update` | `branches.manage` |
| POST | `/admin/branches/{id}/members`, `…/members/{ulid}/remove` | `admin.branches.members.store/destroy` | `branches.manage` veya (`branches.assign_members` + branş lideri) |
| POST | `/admin/branches/{id}/members/{ulid}/lead` | `admin.branches.members.lead` | `branches.manage` |
| GET | `/admin/audit-logs`, `/admin/audit-logs/{ulid}` | `admin.audit-logs.index/show` | `audit.read` (finans yöneticisi yalnızca `wallet.*/payments.*/refunds.*`) |
| GET | `/admin/activity-logs` | `admin.activity-logs.index` | `activity.read` (süper admin) |
| GET | `/admin/settings`, `/admin/settings/{group}` | `admin.settings.index/edit` | `settings.read` |
| POST | `/admin/settings/{group}` | `admin.settings.update` | `settings.update` + PC; `security`, `mail`, `features.payments` yalnızca süper admin |
| GET | `/panel/security/two-factor` | `panel.security.two-factor` | giriş yapmış kullanıcı (personel 2FA'yı kapatamaz) |

**Artisan komutları:** `mrp:make-super-admin {email}` · `mrp:expire-roles` (saatlik) · `audit:verify` (günlük 03:30; bozuk zincirde çıkış kodu 1) · `content:publish-scheduled` (dakikalık, Faz 5). Zamanlayıcı için sunucuda: `* * * * * php artisan schedule:run`.

### İçerik yönetimi — CMS (Faz 5)

**Kaynak deseni.** Aşağıdaki her `{kaynak}` için aynı rota seti üretilir (tanımlar: `App\Services\Content\ContentResourceRegistry`):
`pages, news, news-categories, tags, systems, system-categories, jobs, factions, rule-categories, rules, faqs, events, navigation, social-links, academy-courses, academy-topics` → `/admin/content/{kaynak}`; duyurular → `/admin/announcements`.

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/content/{kaynak}` (`?status=draft\|in_review\|scheduled\|published\|archived&q=`) | `admin.content.{kaynak}.index` | `content.read` |
| GET / POST | `/admin/content/{kaynak}/create`, `/admin/content/{kaynak}` | `….create`, `….store` | `content.create` |
| GET / POST | `/admin/content/{kaynak}/{id}/edit`, `/admin/content/{kaynak}/{id}` | `….edit`, `….update` | `content.read` / `content.update` |
| POST | `/admin/content/{kaynak}/{id}/submit-review` | `….submit-review` | `content.update` |
| POST | `/admin/content/{kaynak}/{id}/publish` · `unpublish` · `archive` · `restore` | `….publish` … | `content.publish` (ileri tarihli `published_at` → `scheduled`) |
| POST | `/admin/content/{kaynak}/{id}/delete` | `….destroy` | `content.delete` (sistem sayfası 403) |
| POST | `/admin/content/{kaynak}/reorder` (`id`, `direction=up\|down`) | `….reorder` | `content.update` |
| GET | `/admin/content/{kaynak}/{id}/preview` (imzalı, 30 dk) | `….preview` | `content.read` |

Akademi kaynakları (`academy-*`) için tüm işlemler `academy.manage`; duyurular için `announcements.manage`, yayın için `announcements.publish`.

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET / POST | `/admin/content/pages/{page}/sections` | `admin.content.pages.sections(.store)` | `content.read` / `content.update` |
| POST | `/admin/content/pages/{page}/sections/{section}` (`data` JSON, `is_visible`) · `/delete` · `/move` | `….sections.update/destroy/reorder` | `content.update` (şema doğrulaması) |
| GET / POST | `/admin/content/pages/{page}/revisions`, `…/revisions/{revision}/restore` | `….revisions(.restore)` | `content.read` / `content.publish` |
| GET | `/admin/content/rules/{rule}/revisions` | `admin.content.rules.revisions` | `content.read` |
| GET / POST | `/admin/content/rules-version`, `/admin/content/rules/bump-version` (`version`, `note`) | `admin.content.rules.version`, `….bump-version` | `content.publish` |
| GET / POST | `/admin/media`, `/admin/media/{ulid}`, `/admin/media/{ulid}/delete` | `admin.media.index/store/update/destroy` | `content.read` / `media.upload` (20/dk) / `media.delete` (kullanımdaysa 409) |
| GET | `/admin/templates`, `/admin/templates/{id}/edit` | `admin.templates.index/edit` | `templates.read` |
| POST | `/admin/templates/{id}` (`subject`, `body`) | `admin.templates.update` | `templates.manage` (söz dizimi + allowlist kayıtta doğrulanır) |
| POST | `/admin/templates/{id}/preview` → JSON `{success, data: {subject, html}}` veya 422 `TEMPLATE_SYNTAX` | `admin.templates.preview` | `templates.read` |
| POST | `/admin/templates/{id}/test-send`, `…/revisions/{revision}/restore` | `admin.templates.test-send` (5/10 dk), `….revisions.restore` | `templates.manage` |
| GET / POST | `/admin/contact`, `/admin/contact/{ulid}`, `/admin/contact/{ulid}/status` | `admin.contact.index/show/status` | `contact.read` |

**Oyuncu tarafı (Faz 5):**

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/academy` | `panel.academy` | Ders bazında ilerleme (tamamlanan / yayındaki konu) |
| POST | `/panel/academy/topics/{id}/complete` | `panel.academy.complete` | İdempotent; konu sayfasında "Okudum" butonu |
| POST | `/panel/rules/acknowledge` | `panel.rules.acknowledge` | Geçerli kural sürümünü onaylar (panelde bant) |
| POST | `/panel/announcements/{ulid}/read` | `panel.announcements.read` | Duyuruyu okundu işaretler |

**Medya:** Yüklenen görseller GD ile yeniden kodlanır (EXIF temizlenir), en fazla 2400 px orijinal + 480/960/1600 px **WebP** varyantları `storage/app/public/media/YYYY/MM/` altına yazılır. `public/storage` bağlantısı gerekir: `php artisan storage:link`.

**Şablon söz dizimi:** `{{ user.username }}`, `{{ x | upper }}`, `{{ x | truncate(80) }}`, `{{ x | default("—") }}`, `{{#if x}} … {{/if}}`. Ham çıktı, ifade ve kod yoktur; bilinmeyen değişken kayıtta reddedilir (bkz. docs/CMS.md §6, §10).

### Oyuncu paneli ve destek (Faz 6)

Panel düzeni: masaüstünde sol menü, mobilde alt sekme çubuğu (Genel · Destek · Akademi · Bildirimler · Menü). Tüm `/panel` ve `/admin` yanıtları `X-Robots-Tag: noindex, nofollow` + `Cache-Control: no-store, private` taşır.

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/notifications` | `panel.notifications.index` | Bildirimler (20/sayfa) + panel duyuruları |
| GET | `/panel/notifications/count` | `panel.notifications.count` | JSON `{success, data: {unread}}` + `ETag` (304); 60 sn polling |
| POST | `/panel/notifications/{uuid}/read`, `/panel/notifications/read-all` | `panel.notifications.read(-all)` | Başkasının bildirimi → 404 |
| GET / POST | `/panel/settings`, `/settings/preferences`, `/settings/notifications` | `panel.settings(.preferences/.notifications)` | Tema/düşük efekt (`users` + cookie), e-posta tercihleri |
| GET | `/panel/security` | `panel.security` | Parola, e-posta, 2FA, oturumlar (IP maskeli) |
| POST | `/panel/security/password` | `panel.security.password` | Mevcut parola zorunlu, 5/10 dk; diğer oturumlar kapanır |
| POST | `/panel/security/email` | `panel.security.email` | PC, 3/saat; onay bağlantısı **yeni** adrese (60 dk) |
| GET / POST | `/panel/security/email/confirm/{token}` | `panel.security.email.confirm(.store)` | GET yalnızca onay ekranı; POST uygular, eski adrese bilgi gider |
| POST | `/panel/security/sessions/{id}/revoke`, `…/revoke-others`, `…/revoke-all` | `panel.security.sessions.*` | PC |
| GET | `/panel/security/events` | `panel.security.events` | Son 90 gün |
| POST | `/panel/account/delete-request`, `/panel/account/delete-cancel` | `panel.account.*` | PC; 14 gün sonra anonimleştirme (personel talep edemez) |
| GET | `/panel/support` (`?status=open\|closed\|all`) | `panel.support.index` | Doğrulanmış e-posta gerekir |
| GET / POST | `/panel/support/create`, `/panel/support` | `panel.support.create/store` | 5/saat; `idempotency_key` (UUID); ≤ 3 ek |
| GET | `/panel/support/{ulid}` | `panel.support.show` | Yalnızca public mesajlar; başkasının talebi 404 |
| POST | `/panel/support/{ulid}/messages` · `/close` · `/reopen-request` | `panel.support.*` | Mesaj 20/10 dk; yeniden açma kapanıştan 7 gün içinde |
| GET | `/panel/support/attachments/{ulid}` | `panel.support.attachments.show` | Sahip + public mesaj; `attachment` + `nosniff` |

**ACP destek:**

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/support` (`?status=&assigned=me\|none&category=&q=`) | `admin.support.index` | `support.read` (branş kapsamı; `support.all_branches` hepsini görür) |
| GET | `/admin/support/counters` | `admin.support.counters` | JSON `{unassigned, mine, waiting_for_staff}` |
| GET | `/admin/support/{ulid}` | `admin.support.show` | Kapsam dışı → 404 |
| POST | `/admin/support/{ulid}/messages` (`body`, `visibility=public\|internal`) | `admin.support.messages.store` | `support.reply` (+ iç not için `support.internal_note`); makro değişkenleri `{{ player.username }}`, `{{ ticket.number }}`, `{{ ticket.subject }}`, `{{ staff.username }}` |
| POST | `/admin/support/{ulid}/status` · `/claim` · `/reopen-decision` | `admin.support.*` | `support.reply` (kapatma için `support.close`) |
| POST | `/admin/support/{ulid}/assign` · `/priority` · `/escalate` (`branch_id`, `note`) | `admin.support.*` | `support.assign` (atanan branş üyesi olmalı; escalation audit'e yazılır) |
| GET | `/admin/support/attachments/{ulid}` | `admin.support.attachments.show` | `support.read` + kapsam |
| — | `/admin/support/categories…`, `/admin/support/macros…` | `admin.support.categories.*`, `admin.support.macros.*` | `support.manage` (CMS kaynak deseni) |
| POST | `/admin/users/{ulid}/anonymize` (`reason`) | `admin.users.anonymize` | `users.anonymize` + PC |

**Durum makinesi:** `open → in_progress → waiting_for_player ⇄ waiting_for_staff → resolved → closed`. Yetkili public yanıtı → `waiting_for_player` (oyuncuya panel + tercihe bağlı e-posta); oyuncu yanıtı → `waiting_for_staff` (atanan yetkiliye panel bildirimi). İç not durum değiştirmez ve bildirim üretmez.

**Ekler:** JPG/PNG/WebP (WebP olarak yeniden kodlanır, EXIF ve polyglot yükü atılır), PDF (`%PDF-` imzası), TXT (UTF-8, NUL yok); ≤ 5 MB, ≤ 3 adet; dosya adı ULID; `storage/app/private/support/YYYY/MM/`.

**Zamanlayıcı:** `mrp:maintenance-tick` (saatlik) — 7 gün yanıtsız `waiting_for_player` → `resolved`, 7 gün sonra `resolved` → `closed` (`settings.support.auto_resolve_days/auto_close_days`) ve 14 günü dolan hesap silme talepleri → anonimleştirme.

**PWA:** `public/manifest.webmanifest` (`start_url=/panel?source=pwa`, 192/512 + maskable ikonlar `public/icons/`), `public/sw.js` (panel/admin/api/kimlik yolları **asla** cache'lenmez; gezinmeler ağ önce → `public/offline.html`; `/build/*` cache önce; `SKIP_WAITING` + `CLEAR_RUNTIME_CACHE`). SW yalnızca HTTPS/localhost'ta ve production'da (veya `.env` `PWA_DEV=true`) kaydedilir.

### Faz 7 — Oyun hesapları, karakterler, MTA entegrasyonu

**Oyuncu paneli:**

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/game-accounts` | `panel.game-accounts.index` | Bağlı hesaplar (`n / max`) + bağlama kodu kartı |
| POST | `/panel/game-accounts/link-code` | `panel.game-accounts.link-code` | Doğrulanmış e-posta, 5/saat; önceki kodu iptal eder. Kod **yalnızca** bir sonraki sayfa görüntülemesinde gösterilir (oturumdan `pull`) |
| POST | `/panel/game-accounts/link-code/cancel` | `panel.game-accounts.link-code.cancel` | |
| GET | `/panel/game-accounts/link-status` | `panel.game-accounts.link-status` | JSON `{state: none\|pending\|linked\|expired, expires_in, account_name?}` — kodu asla döndürmez; 5 sn polling |
| POST | `/panel/game-accounts/{id}/unlink-request` (`reason` ≥ 10) | `panel.game-accounts.unlink-request` | PC; başkasının bağlantısı 404 |
| GET | `/panel/characters`, `/panel/characters/{ulid}` | `panel.characters.index/show` | Yalnızca aktif bağlantılı hesapların karakterleri; detayda yalnızca `character_public_fields` |

**ACP:**

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/game-accounts` (`?q=&filter=all\|unlink_requested\|unlinked`) | `admin.game-accounts.index` | `game_accounts.read` |
| GET | `/admin/game-accounts/{ulid}` | `admin.game-accounts.show` | `game_accounts.read` (bağlantı geçmişi + karakterler) |
| POST | `/admin/game-accounts/links/{id}/approve-unlink` · `/reject-unlink` (`reason`) | `admin.game-accounts.*` | `game_accounts.manage` |
| POST | `/admin/game-accounts/links/{id}/revoke` (`reason`) | `admin.game-accounts.revoke` | `game_accounts.manage` + PC; köprüye `account.link_revoked` |
| POST | `/admin/game-accounts/manual-link` (`username, mta_server_id, mta_account_ref, account_name, evidence, reason`) | `admin.game-accounts.manual-link` | `game_accounts.manage` + PC (`verification_method=admin_manual`) |
| GET | `/admin/characters`, `/admin/characters/{ulid}` | `admin.characters.index/show` | `characters.read` |
| POST | `/admin/characters/{ulid}/visibility` (`hidden`, `reason`) | `admin.characters.visibility` | `characters.review`; audit `characters.visibility_changed` |
| GET | `/admin/integrations/mta` | `admin.integrations.mta` | `integrations.manage` — sunucular, durum, anahtarlar |
| POST | `/admin/integrations/mta/servers`, `/servers/{id}` | `admin.integrations.mta.servers.store/update` | + PC, `reason` (bakım bayrağı değişimi `servers.maintenance_toggled`) |
| POST | `/admin/integrations/mta/servers/{id}/credentials` (`scopes[]`, `expires_at?`, `reason`) | `admin.integrations.mta.credentials.store` | + PC. Secret **yalnızca bu yanıtta** gösterilir (flash/oturuma yazılmaz). Sunucu başına ≤ 2 aktif anahtar |
| POST | `/admin/integrations/mta/credentials/{id}/revoke` (`reason`) | `admin.integrations.mta.credentials.revoke` | + PC |

**Bağlantı durum makinesi:** `active → unlink_requested → unlinked` (onay) / `→ active` (ret); `active|unlink_requested → revoked` (yetkili veya hesap anonimleştirme). Bağlantı bittiğinde `game_commands`'a `account.link_revoked {account_ref}` girer (idempotency `link:{id}:revoked`).

**Zamanlayıcı:** `bridge:maintain` (15 dk) — nonce (15 dk), heartbeat (30 gün), `game_events` (90 gün), bağlama kodları (7 gün) temizliği + süresi dolan komutlar `expired`; `bridge:maintain --stats` (00:10) — `server_stats_daily`.

**Lua resource:** [`mta-resource/web_bridge/`](mta-resource/web_bridge/README.md) — kurulum, ACL, `config.lua`, gamemode export'ları (`syncCharacters`, `queueEvent`), olaylar (`onWebBridgeRequestCharacters`, `onWebBridgeLinkRevoked`, `onWebBridgeMaintenanceChanged`). Oyun içi komut: `/webbagla KOD`.

### Faz 8 — Başvurular, cezalar, oyun komutları, cihazlar

Özellik bayrakları (`ACP → Ayarlar → features`): `features.applications`, `features.sanctions`, `features.devices` — **varsayılan kapalı**; kapalıyken panel rotaları 404 döner ve menüde görünmez. ACP ekranları bayraktan bağımsızdır.

**Oyuncu paneli:**

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/applications` | `panel.applications.index` | Açık türler (uygunluk mesajıyla) + başvurularım. F:applications, doğrulanmış e-posta |
| GET / POST | `/panel/applications/new/{slug}` | `panel.applications.create/store` | `action=save_draft\|submit`, `answers[...]`, `character` (ULID). 10/saat |
| GET / POST | `/panel/applications/{ulid}` | `panel.applications.show/update` | Taslak bu adreste form olarak açılır; başkasının başvurusu 404 |
| POST | `/panel/applications/{ulid}/messages` · `/cancel` | `panel.applications.messages.store/cancel` | Yanıt yalnızca `needs_info` iken; iptal `draft/submitted/needs_info` |
| GET | `/panel/sanctions`, `/panel/sanctions/{ulid}` | `panel.sanctions.index/show` | Yalnızca `public_reason`, tür, tarih, süre; veren "Yönetim Ekibi". F:sanctions |
| POST | `/panel/sanctions/{ulid}/appeal` (`body` ≥ 30) | `panel.sanctions.appeal` | Ceza başına 1 itiraz, 5/saat |
| GET | `/panel/devices` | `panel.devices.index` | Cihazlar (parmak izi = HMAC'in ilk 8 karakteri). F:devices |
| POST | `/panel/devices/link-code` | `panel.devices.link-code` | `device_link` kodu; bağlı hesap gerekir. Oyunda `/cihazbagla KOD` |
| POST | `/panel/devices/{id}/revoke` | `panel.devices.revoke` | PC; yalnızca kendi bağlı hesabının cihazı |

**ACP:**

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/applications` (`?filter=open\|mine\|decided\|all&type=`) | `admin.applications.index` | `applications.read` + branş kapsamı (`applications.all_branches` hepsini görür) |
| GET | `/admin/applications/{ulid}` | `admin.applications.show` | Kapsam dışı → 404 |
| POST | `/admin/applications/{ulid}/status` (`status=in_review\|needs_info\|approved\|rejected`, `note_public`, `note_internal`) | `admin.applications.status` | `applications.review`; `needs_info`/`rejected` için public not zorunlu; karar audit `applications.decided` |
| POST | `/admin/applications/{ulid}/assign` · `/messages` (`visibility=public\|internal`) | `admin.applications.*` | `applications.review` |
| — | `/admin/application-types…` | `admin.application-types.*` | `applications.types.manage` (CMS kaynak deseni; form şeması `App\Support\ApplicationForms`) |
| GET | `/admin/sanctions` · `/create` · `/{ulid}` | `admin.sanctions.*` | `sanctions.read` / `sanctions.create` |
| POST | `/admin/sanctions` (`type, user\|account\|character, duration_minutes, public_reason, internal_note, evidence_url, rule_id, appealable`) | `admin.sanctions.store` | `sanctions.create` + PC; `perm_ban` için `sanctions.ban_permanent` + kanıt bağlantısı |
| POST | `/admin/sanctions/{ulid}/revoke` (`reason`) | `admin.sanctions.revoke` | `sanctions.revoke`; ban → `player.unban` komutu |
| GET / POST | `/admin/appeals`, `/admin/appeals/{ulid}`, `/decide` (`decision=accepted\|rejected`, `note_public`) | `admin.appeals.*` | `sanctions.appeals.review`; cezayı veren karar veremez |
| GET / POST | `/admin/game-commands`, `/admin/game-commands/{ulid}/cancel` | `admin.game-commands.*` | `game_commands.read`; elle komutta tür bazlı izin (`game_commands.warn/kick/ban_temp/ban_perm/announce`) + PC |
| GET / POST | `/admin/devices`, `/{id}/approve` (PC), `/{id}/reject` | `admin.devices.*` | `devices.read` / `devices.approve`, gerekçe zorunlu |

**Ceza → oyun komutu:** `warning→player.warn`, `mute→player.mute`, `jail→player.jail`, `kick→player.kick`, `temp_ban→player.ban{minutes}`, `perm_ban→player.ban{minutes:null}`; `web_suspension` kullanıcıyı askıya alır (oturumlar kapanır). İdempotency `sanction:{ulid}:apply|revoke`.

**Zamanlayıcı:** `mrp:moderation-tick` (her dakika) — 2 dk sonuçsuz `dispatched` komut → `pending` (en fazla 3 deneme, sonra `failed` + audit `game_commands.failed`); süresi dolan cezalar → `expired`, web askısı kaldırılır.

**Lua:** `/cihazbagla KOD` (serial yalnızca `getPlayerSerial` ile okunur, yalnızca istek gövdesinde gider), `config.lua` → `ENABLE_COMMANDS = true` (anahtarda `commands` kapsamı gerekir).

## 3. JSON API (`/api/v1`)

Hata biçimi (tüm JSON uçları):

```json
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "…", "details": [{ "field": "code", "message": "…" }] }, "requestId": "01J…" }
```

| Yöntem | URL | Kimlik doğrulama | Limit | Faz |
|---|---|---|---|---|
| GET | `/api/v1/public/server-status` | Yok | 60/dk/IP | 2 |
| GET | `/api/v1/game/ping` | Köprü imzası (her kapsam) | 120/dk/anahtar | 7 |
| POST | `/api/v1/game/heartbeat` | Köprü, `heartbeat` | 6/dk/anahtar | 7 |
| POST | `/api/v1/game/player/connected` · `/player/disconnected` | Köprü, `events` | 120/dk | 7 |
| POST | `/api/v1/game/accounts/sync` · `/characters/sync` | Köprü, `events` | 120/dk | 7 |
| POST | `/api/v1/game/account-link/verify` | Köprü, `account_link` | 120/dk + hesap başına 5 yanlış/10 dk | 7 |
| POST | `/api/v1/game/device-link/verify` | Köprü, `account_link` | 120/dk + hesap başına 5 yanlış/10 dk | 8 |
| GET | `/api/v1/game/commands/pending` | Köprü, `commands` | 120/dk | 8 |
| POST | `/api/v1/game/commands/result` | Köprü, `commands` | 120/dk | 8 |

**Köprü imzası** (ARCHITECTURE §7.2): başlıklar `X-Bridge-Key`, `X-Bridge-Timestamp` (unix sn, ±300), `X-Bridge-Nonce` (32–64 hex, tek kullanım), `X-Bridge-Signature` = `hex(HMAC-SHA256(secret, "METHOD\nPATH\nTIMESTAMP\nNONCE\n" + hex(SHA256(body))))`. Sıra: biçim → anahtar aktif → zaman → gövde ≤ 64 KB → imza (`hash_equals`) → kapsam → nonce INSERT (imzadan **sonra**) → `last_used_at`. Hatalar: `401 SIGNATURE_INVALID`, `403 FORBIDDEN`, `409 REPLAY_DETECTED`, `413 PAYLOAD_TOO_LARGE`, `422 VALIDATION_ERROR`, `429 RATE_LIMITED`. Başarısızlıklar `storage/logs/security-*.log`'a (secret'sız); 5 dk'da 20'yi aşarsa kritik audit `bridge.signature_alert`.

Örnek gövdeler ve yanıtlar:

```jsonc
// POST /heartbeat → {"data":{"status":"online","maintenance":false}}
{ "players": 87, "max_players": 300, "server_name": "Meridyen RP", "game_mode": "Roleplay", "map": "San Andreas", "resource_version": "1.0.0", "uptime_sec": 86400 }
// POST /player/connected → {"data":{"duplicate":false}}  (aynı event_id → duplicate:true, yan etki yok)
{ "event_id": "<uuid>", "occurred_at": "2026-11-11T18:40:00Z", "account": { "ref": "1042", "name": "kaan" } }
// POST /accounts/sync (≤ 100; yalnızca web'de kayıtlı hesaplar güncellenir) → {"data":{"duplicate":false,"updated":1}}
{ "event_id": "<uuid>", "accounts": [ { "ref": "1042", "name": "kaan", "last_seen_at": "…" } ] }
// POST /characters/sync (≤ 20; bağlı değilse {"ignored":true}) → {"data":{"duplicate":false,"ignored":false,"synced":1}}
{ "event_id": "<uuid>", "account_ref": "1042", "characters": [ { "ref": "c-551", "name": "Kaan Demir", "status": "active", "level": 12,
  "playtime_minutes": 5400, "faction_key": "lspd", "job_key": "taxi", "public_profile": { "bio": "…" } } ] }
// POST /account-link/verify → {"data":{"linked":true,"web_username":"kaan_w"}} · 422 LINK_CODE_INVALID · 409 ACCOUNT_ALREADY_LINKED · 409 LINK_LIMIT_REACHED · 429 RATE_LIMITED
{ "event_id": "<uuid>", "code": "K7Q4-M2XD", "account": { "ref": "1042", "name": "kaan" } }
// POST /device-link/verify (serial: MTA 32 hex; yalnızca HMAC(serial, BRIDGE_SERIAL_PEPPER) saklanır) → {"data":{"status":"pending_approval"}} · 422 LINK_CODE_INVALID · 503 (pepper yok)
{ "event_id": "<uuid>", "code": "K7Q4-M2XD", "account": { "ref": "1042" }, "serial": "<32 hex>" }
// GET /commands/pending → {"data":[{"id":"01jc…","type":"player.kick","payload":{"account_ref":"1042","reason":"…"},"expires_at":"…"}]}  (en fazla 20; dispatched olarak işaretlenir)
// POST /commands/result → {"data":{"id":"01jc…","status":"succeeded","duplicate":false}}  (tekrar sonuç → duplicate:true)
{ "id": "01jc…", "status": "failed", "detail": { "error": "player_offline" } }   // error: player_offline | unsupported_command | invalid_payload | timeout | failed
```

Ortak HMAC test vektörleri: `mta-resource/web_bridge/tests/vectors.json` (Pest) = `tests/vectors.lua` (Lua öz-testi).

`GET /api/v1/public/server-status` yanıtı (15 sn cache, `ETag` / `304` destekli):

```json
{ "success": true, "data": { "server": "main", "name": "Meridyen Roleplay", "status": "offline", "label": "Çevrimdışı",
  "players": null, "max_players": 300, "last_heartbeat_at": null, "freshness_seconds": null, "maintenance_note": null,
  "connect": { "host": "127.0.0.1", "port": 22003, "uri": "mtasa://127.0.0.1:22003" } } }
```

`status`: `online` (son heartbeat < 90 sn) · `stale` (90 sn – 5 dk, son bilinen sayı) · `offline` (> 5 dk veya hiç yok, sayı gizli) · `maintenance` (admin bayrağı).

---

## 4. Veritabanı

Şema kaynağı: [docs/DATABASE.md](docs/DATABASE.md), ilişkiler: [docs/ERD.md](docs/ERD.md). Motor InnoDB, `utf8mb4_unicode_ci`, tüm zamanlar UTC, para `BIGINT` minor unit.

| Migration | Tablolar | Faz |
|---|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` | 1 → 3 (DATABASE §3.1 şeması) |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` | 1 |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` | 1 |
| `2026_11_11_090000_create_identity_tables` | `user_security_events`, `email_change_requests`, `consents` | 3 |
| `2026_11_11_110000_create_access_control_tables` | `roles`, `permissions`, `role_permissions`, `user_roles`, `branches`, `branch_members`, `audit_logs` (+ trigger), `admin_activity_logs` | 4 |
| `2026_11_12_090000_create_cms_messaging_tables` | `announcements`, `announcement_reads`, `academy_progress`, `rule_acknowledgements`, `message_templates`, `message_template_revisions`, `message_dispatches` | 5 |
| `2026_11_12_100000_create_support_notification_tables` | `support_categories`, `support_ticket_sequence`, `support_tickets`, `support_messages`, `support_attachments`, `support_ticket_events`, `support_macros`, `user_notification_preferences`, `notifications` | 6 |
| `2026_11_12_110000_create_bridge_game_tables` | `integration_credentials`, `bridge_nonces`, `server_heartbeats`, `server_stats_daily`, `mta_accounts`, `game_account_links`, `game_link_codes`, `characters`, `game_events`, `game_commands` (+ FK `support_tickets.character_id`) | 7 |
| `2026_11_12_120000_create_moderation_application_tables` | `application_types`, `applications` (+ generated `open_key` unique), `application_answers`, `application_messages`, `application_status_history` (append-only trigger), `sanctions` (+ `chk_sanctions_target`), `sanction_appeals`, `mta_account_devices` (+ FK `factions/game_jobs.application_type_id`) | 8 |
| `2026_11_11_100000_create_settings_and_servers_tables` | `system_settings`, `mta_servers` | 2 |
| `2026_11_11_100100_create_content_tables` | `media`, `pages`, `page_sections`, `page_revisions`, `redirects`, `navigation_items`, `social_links`, `news_categories`, `news_posts`, `tags`, `news_post_tag`, `game_system_categories`, `game_systems`, `rule_categories`, `rules`, `rule_revisions`, `game_jobs`, `factions`, `faqs`, `community_events`, `academy_courses`, `academy_topics`, `academy_topic_rule`, `contact_messages` | 2 |

**Yayın blok kolonları** (yayınlanabilir tüm içeriklerde, `App\Support\Database\ContentColumns`):
`status` (`draft|in_review|scheduled|published|archived`), `published_at`, `sort`, `seo_title`, `meta_description`, `created_by`, `updated_by`, `created_at`, `updated_at`. Public'te yalnızca `status = published AND published_at <= now()` görünür.

**Kısıtlar (Faz 2):** `chk_navigation_target` (`route_name` veya `url` zorunlu). `factions.application_type_id` ve `game_jobs.application_type_id` FK'leri `application_types` tablosuyla birlikte Faz 8'de eklenir.

**Kimlik tabloları (Faz 3):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `users` | `ulid` (public kimlik), `username` (20, unique, harf duyarsız), `email` (254, unique), `password` (argon2id), `status` (`active\|inactive\|suspended\|pending_deletion\|anonymized`), `suspended_until`, `suspension_reason`, `two_factor_secret/recovery_codes/confirmed_at` (şifreli), `theme`, `reduced_effects`, `locale`, `last_login_at`, `deletion_requested_at` | |
| `user_security_events` | `user_id` (null olabilir), `event`, `ip_masked`, `ip_hash` (HMAC), `user_agent_summary`, `identifier_hash` (HMAC, başarısız girişte), `metadata` JSON, `created_at` | Uygulama yalnızca INSERT yapar; 180 gün saklama |
| `email_change_requests` | `user_id`, `new_email`, `token_hash` (unique), `expires_at`, `confirmed_at`, `cancelled_at` | Faz 6 akışı |
| `consents` | `user_id` / `anon_id`, `type` (`terms\|kvkk_ack\|marketing…`), `version`, `granted`, `ip_masked` | **Append-only** (UPDATE/DELETE trigger ile engelli); kayıtta `terms` + `kvkk_ack` |

**Yetki ve denetim tabloları (Faz 4):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `roles` | `slug` (unique), `name`, `description`, `color`, `priority` (hiyerarşi, super_admin=100), `is_staff`, `is_system` | 9 sistem rolü seed edilir (`config/roles.php`) |
| `permissions` | `key` (unique, ör. `users.read`), `group`, `description`, `is_sensitive` | 68 izin, kaynak `config/permissions.php` |
| `role_permissions` | PK (`role_id`, `permission_id`), `created_at` | CASCADE |
| `user_roles` | `user_id` (RESTRICT), `role_id` (RESTRICT), `assigned_by`, `expires_at` (geçici rol), unique (`user_id`,`role_id`) | `mrp:expire-roles` saatlik temizler |
| `branches` | `slug` (unique), `name`, `type` (`support\|moderation\|faction\|content\|economy\|game\|general`), `faction_id`, `parent_id` (escalation), `is_active` | Seed: `yonetim, destek-genel, destek-teknik, moderasyon, icerik, finans` |
| `branch_members` | `branch_id`, `user_id`, `is_lead`, `assigned_by`, `expires_at`, unique (`branch_id`,`user_id`) | Üye olmak için personel rolü şart |
| `audit_logs` | `ulid`, `actor_type` (`user\|system\|scheduler\|bridge\|payment_provider`), `actor_id`, `action`, `target_type/id`, `severity` (`info\|warning\|critical`), `changes` JSON (hassas alanlar `[REDACTED]`), `reason`, `ip_masked`, `user_agent_summary`, `request_id`, `prev_hash`, `hash` | **Append-only + hash zinciri**: `hash = SHA256(prev_hash ‖ canonical_json(satır))`, `GET_LOCK('audit_chain')` ile serileştirilir |
| `admin_activity_logs` | `user_id`, `route_name`, `method`, `target_type/id`, `ip_masked`, `created_at` | Tüm başarılı admin istekleri (görüntüleme dahil), 90 gün |

**CMS, Akademi ve mesajlaşma tabloları (Faz 5):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `announcements` | `ulid`, `title`, `body` (Markdown), `priority` (`info\|important\|critical`), `audience` (`all\|players`), `audience_role_ids` JSON, `channels` JSON (`public_banner\|panel\|email`), `starts_at`, `ends_at`, `email_dispatched_at` + yayın blok kolonları | E-posta kanalı yayında **bir kez** |
| `announcement_reads` | PK (`announcement_id`, `user_id`), `read_at` | Fan-out yok; okundu bilgisi |
| `academy_progress` | PK (`user_id`, `academy_topic_id`), `completed_at` | "Okudum" idempotent |
| `rule_acknowledgements` | `user_id`, `version`, `acknowledged_at`, unique (`user_id`,`version`) | + `consents(type=rules_ack)` |
| `message_templates` | `key`, `channel`, `locale`, `name`, `subject`, `body`, `variables` JSON (kod kaynaklı), `status`, `is_system`, `version`, `updated_by`, unique (`key`,`channel`,`locale`) | Seed: `lang/tr/messages.php` |
| `message_template_revisions` | `message_template_id`, `version`, `subject`, `body`, `changed_by` | Her kayıtta önceki sürüm |
| `message_dispatches` | `message_template_id`, `template_key`, `channel`, `user_id`, `recipient_masked` (`f***@g***.com`), `status` (`queued\|sent\|failed`), `error`, `attempts`, `queued_at`, `sent_at` | 180 gün |

Faz 5'te mevcut tablolar da kullanılmaya başladı: `page_revisions` (sayfa düzenlemesinde snapshot), `rule_revisions` (kural düzenlemesinde), `media` (WebP varyantları `variants` JSON: genişlik → yol).

**Destek ve bildirim tabloları (Faz 6):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `support_categories` | `slug`, `name`, `description`, `branch_id` (yönlendirme), `default_priority`, `requires_linked_account`, `is_active`, `sort` | Seed: hesap-ve-giris, hata-bildirimi, oyuncu-sikayeti, odeme, genel |
| `support_ticket_sequence` | `id` (=1), `n` | `UPDATE … SET n = LAST_INSERT_ID(n+1)` ile yarışsız `T-000123` |
| `support_tickets` | `ulid`, `number`, `user_id`, `category_id`, `branch_id`, `character_id` (FK Faz 7), `subject`, `status`, `priority`, `assigned_to`, `idempotency_key`, `last_message_at`, `last_player/staff_message_at`, `first_staff_response_at`, `player_unread`, `staff_unread`, `resolved_at`, `closed_at`, `closed_by`, `reopen_requested_at` | unique (`user_id`,`idempotency_key`); kuyruk indeksi (`branch_id`,`status`,`priority`,`last_message_at`) |
| `support_messages` | `ticket_id`, `author_id`, `author_type` (`player\|staff\|system`), `visibility` (`public\|internal`), `body` (Markdown), `edited_at`, `created_at` | Oyuncu sorguları **daima** `public` |
| `support_attachments` | `ulid`, `ticket_id`, `message_id`, `uploaded_by`, `disk`, `path`, `original_name`, `mime`, `size_bytes`, `sha256`, `scan_status` | Görünürlük mesajı izler |
| `support_ticket_events` | `ticket_id`, `actor_id`, `type`, `from_value`, `to_value`, `note`, `created_at` | `created, status_changed, assigned, …, auto_resolved, auto_closed` |
| `support_macros` | `title`, `body` (şablon söz dizimi), `category_id`, `branch_id`, `is_active` | |
| `user_notification_preferences` | PK (`user_id`,`category`,`channel`), `enabled` | Kayıt yoksa açık; güvenlik e-postaları tercihe bağlı değil |
| `notifications` | Laravel standart (`uuid`, `type`, `notifiable_*`, `data` JSON, `read_at`) | Panel bildirimleri |

**Köprü ve oyun tabloları (Faz 7):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `integration_credentials` | `mta_server_id`, `key_id` (unique, `mrp_live_…`), `secret` (**encrypted** cast), `scopes` JSON (`heartbeat, events, account_link, commands`), `last_used_at`, `expires_at`, `revoked_at`, `created_by` | Sunucu başına ≤ 2 aktif |
| `bridge_nonces` | PK `nonce` CHAR(64), `credential_id`, `created_at` | Replay koruması; 15 dk sonra silinir |
| `server_heartbeats` | `mta_server_id`, `player_count`, `max_players`, `payload` JSON (ad, mod, harita, sürüm, uptime), `received_at` | 30 gün |
| `server_stats_daily` | PK (`mta_server_id`, `date`), `peak_players`, `avg_players`, `uptime_ratio`, `unique_accounts` | `bridge:maintain --stats` |
| `mta_accounts` | `ulid`, `mta_server_id`, `mta_account_ref` (sunucu içinde unique), `account_name`, `last_seen_at`, `synced_at` | Yalnızca bağlanan hesaplar kaydedilir |
| `game_account_links` | `user_id`, `mta_account_id` (RESTRICT), `status` (`active\|unlink_requested\|unlinked\|revoked`), `verification_method` (`ingame_code\|admin_manual`), **generated** `active_mta_account_id` + unique `uq_gal_active_account` | Bir oyun hesabının DB seviyesinde tek aktif bağlantısı |
| `game_link_codes` | `user_id`, `purpose` (`account_link`), `code_hash` (HMAC-SHA256, APP_KEY türevi; düz kod saklanmaz), `attempts`, `expires_at` (+10 dk), `used_at`, `used_by_mta_account_id`, `cancelled_at` | 5 denemede iptal |
| `characters` | `ulid`, `mta_server_id`, `mta_account_id`, `mta_character_ref` (sunucu içinde unique), `name`, `status`, `faction_id`, `game_job_id`, `level`, `playtime_minutes`, `public_profile` JSON (allowlist), `is_profile_hidden`, `last_seen_at`, `synced_at` | Salt okunur kopya |
| `game_events` | `mta_server_id`, `event_id` (sunucu içinde unique), `type`, `mta_account_id`, `payload` (minimal), `status` (`received\|processed\|failed\|ignored`), `occurred_at`, `received_at`, `processed_at` | İdempotency defteri, 90 gün |
| `game_commands` | `ulid`, `mta_server_id`, `type` (allowlist), `target_mta_account_id`, `payload`, `idempotency_key` (unique), `status` (`pending\|dispatched\|succeeded\|failed\|expired\|cancelled`), `attempts`, `result`, `requested_by`, `source_type/id`, `expires_at` | Faz 7: yalnızca `account.link_revoked`; pull uçları Faz 8 |
**Başvuru, moderasyon ve cihaz tabloları (Faz 8):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `application_types` | `slug` (unique), `name`, `kind` (`faction\|staff\|special_role\|event\|whitelist`), `form_key` (`faction_default\|staff_v1\|whitelist_v1`), `faction_id`, `branch_id` (inceleyen branş, RESTRICT), `is_open`, `requires_linked_account`, `requires_character`, `min_account_age_days`, `cooldown_days`, `sort` | ACP CMS kaynağı |
| `applications` | `ulid`, `user_id`, `application_type_id`, `character_id`, `status` (`draft\|submitted\|in_review\|needs_info\|approved\|rejected\|cancelled`), **generated** `open_key` = açık durumlarda `user_id:type_id` + unique `uq_applications_open_key`, `assigned_to`, `submitted_at`, `decided_at`, `decided_by`, `decision_note_public` | Tür başına tek açık başvuru DB'de |
| `application_answers` | `application_id` (CASCADE), `field_key`, `question_label` (anlık kopya), `answer`, unique (`application_id`,`field_key`) | |
| `application_messages` | `application_id`, `author_id`, `visibility` (`public\|internal`), `body`, `created_at` | Oyuncu sorguları yalnızca `public` |
| `application_status_history` | `application_id` (RESTRICT), `from_status`, `to_status`, `actor_id`, `note_public`, `note_internal`, `created_at` | **Append-only** (UPDATE/DELETE trigger ile engelli) |
| `sanctions` | `ulid`, `user_id` / `mta_account_id` / `character_id` (en az biri, CHECK), `type` (`warning\|mute\|jail\|kick\|temp_ban\|perm_ban\|web_suspension`), `public_reason`, `internal_note`, `evidence_url`, `rule_id`, `starts_at`, `ends_at`, `status` (`active\|expired\|revoked`), `appealable`, `issued_by`, `revoked_by`, `revoked_at`, `revoke_reason` | `internal_note`/`evidence_url`/`issued_by` oyuncuya asla |
| `sanction_appeals` | `ulid`, `sanction_id` (**unique** — ceza başına 1), `user_id`, `body`, `status` (`submitted\|in_review\|accepted\|rejected`), `reviewed_by` (≠ veren), `decision_note_public`, `internal_note`, `decided_at` | |
| `mta_account_devices` | `mta_account_id`, `serial_hmac` CHAR(64) (HMAC, **ham serial yok**), `label`, `status` (`pending_approval\|active\|revoked`), `link_code_id`, `approved_by`, `approved_at`, `revoked_at`, unique (`mta_account_id`,`serial_hmac`) | |

`game_link_codes.purpose` artık `account_link` veya `device_link`. `game_commands` kaynakları: `sanction`, `manual`, `game_account_link`.
**Seed sırası:** `ServerSeeder` → `AccessControlSeeder` (izinler, 9 sistem rolü + matris, 6 branş, 5 destek kategorisi) → `MessageTemplateSeeder` (sistem e-posta şablonları) → `ContentSeeder` → (`local` ise) `DemoSeeder`. İlk süper admin seed ile oluşturulmaz (bkz. §2 Yönetim paneli).

---

## 5. Operasyon (Faz 11 R1)

Ayrıntı: [docs/OPERATIONS.md](docs/OPERATIONS.md). Örnek production dosyaları: [`deploy/`](deploy/) — `nginx/meridyen.conf`, `supervisor/meridyen-worker.conf`, `cron/meridyen`, `backup.sh` (şifreli günlük döküm + dosyalar).

| Komut | Zamanlama | Görev |
|---|---|---|
| `php artisan mrp:health-check [--json]` | 5 dk | Başarısız iş (24 sa), kuyruk gecikmesi > 10 dk, disk < %10, MTA heartbeat > 5 dk → `security` log; alarm varsa çıkış kodu 1 |
| `php artisan audit:verify` | 03:30 | Audit hash zinciri |
| `php artisan mrp:make-super-admin <kullanıcı>` | elle | Break-glass |

**Yayın kapısı testleri:** `tests/Feature/Security/ReleaseGateTest.php` (SQL injection, session fixation, `X-Forwarded-For` ile hız sınırı atlatma, yansıyan XSS, sayfa başına sorgu sınırı, sağlık kontrolü). `composer audit` ve `npm audit` 0 açık.

**Veritabanı kullanıcıları (production):** `meridyen_app` (yalnızca `SELECT/INSERT/UPDATE/DELETE`), `meridyen_migrate` (DDL + trigger, yalnızca dağıtımda), `meridyen_backup` (salt okunur + trigger dökümü). Uygulama kullanıcısı audit/consent trigger'larını değiştiremez.

## 6. Mimari notlar

- **Güvenlik başlıkları (global):** nonce'lu CSP (`unsafe-inline` / `unsafe-eval` yok), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `X-Frame-Options: DENY`, `X-Request-Id`. Production + HTTPS'te HSTS.
- **CSRF:** Laravel token + `VerifyOrigin` (cross-site `Sec-Fetch-Site` / `Origin` → 419). İstisna yalnızca `webhooks/*`.
- **Cache:** Laravel 13 cache'i güvenlik gereği nesne `unserialize` etmez. Cache'e **yalnızca düz dizi/skaler** yazılır (bkz. `PublicLayoutComposer`, `ServerStatusResolver`).
- **Markdown:** CommonMark (`html_input: escape`) + Symfony HtmlSanitizer allowlist → `SafeHtml` (Htmlable). Blade'de `{!! !!}` kullanılmaz (mimari testiyle zorlanır).
- **Tema:** `mrp_theme` / `mrp_effects` cookie'leri sunucuda okunur ve `<html data-theme data-effects>` olarak render edilir (flaş yok). `prefers-reduced-motion` ve düşük efekt modu animasyonları kapatır.
- **Tasarım sistemi:** [docs/DESIGN.md](docs/DESIGN.md) — "Gece Haritası". Token'lar `resources/css/app.css`, bileşenler `resources/views/components/{ui,brand}`.
