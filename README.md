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
| 4 | RBAC, branşlar, yönetim paneli iskeleti | ⏳ |
| 5 | CMS, Akademi, mesaj şablon motoru | ⏳ |
| 6 | Oyuncu paneli, destek, PWA | ⏳ |
| 7 | MTA köprüsü + Lua resource | ⏳ |
| 8 | Başvurular, moderasyon, oyun komutları | ⏳ |
| 9 | Ekonomi ve ödeme | ⏳ |
| 10 | Gelişmiş kanallar | ⏳ |
| 11 | Sertleştirme ve yayın | ⏳ |

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

## 3. JSON API (`/api/v1`)

Hata biçimi (tüm JSON uçları):

```json
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "…", "details": [{ "field": "code", "message": "…" }] }, "requestId": "01J…" }
```

| Yöntem | URL | Kimlik doğrulama | Limit | Faz |
|---|---|---|---|---|
| GET | `/api/v1/public/server-status` | Yok | 60/dk/IP | 2 |

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
| `2026_11_11_100000_create_settings_and_servers_tables` | `system_settings`, `mta_servers` | 2 |
| `2026_11_11_100100_create_content_tables` | `media`, `pages`, `page_sections`, `page_revisions`, `redirects`, `navigation_items`, `social_links`, `news_categories`, `news_posts`, `tags`, `news_post_tag`, `game_system_categories`, `game_systems`, `rule_categories`, `rules`, `rule_revisions`, `game_jobs`, `factions`, `faqs`, `community_events`, `academy_courses`, `academy_topics`, `academy_topic_rule`, `contact_messages` | 2 |

**Yayın blok kolonları** (yayınlanabilir tüm içeriklerde, `App\Support\Database\ContentColumns`):
`status` (`draft|in_review|scheduled|published|archived`), `published_at`, `sort`, `seo_title`, `meta_description`, `created_by`, `updated_by`, `created_at`, `updated_at`. Public'te yalnızca `status = published AND published_at <= now()` görünür.

**Kısıtlar (Faz 2):** `chk_navigation_target` (`route_name` veya `url` zorunlu). `factions.application_type_id` ve `game_jobs.application_type_id` FK'leri `application_types` tablosuyla birlikte Faz 8'de eklenir.

**Kimlik tabloları (Faz 3):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `users` | `ulid` (public kimlik), `username` (20, unique, harf duyarsız), `email` (254, unique), `password` (argon2id), `status` (`active\|suspended\|banned\|pending_deletion`), `suspended_until`, `suspension_reason`, `two_factor_secret/recovery_codes/confirmed_at` (şifreli), `theme`, `reduced_effects`, `locale`, `last_login_at`, `deletion_requested_at` | |
| `user_security_events` | `user_id` (null olabilir), `event`, `ip_masked`, `ip_hash` (HMAC), `user_agent_summary`, `identifier_hash` (HMAC, başarısız girişte), `metadata` JSON, `created_at` | **Append-only** (UPDATE/DELETE trigger ile engelli) |
| `email_change_requests` | `user_id`, `new_email`, `token_hash` (unique), `expires_at`, `confirmed_at`, `cancelled_at` | Faz 6 akışı |
| `consents` | `user_id` / `anon_id`, `type` (`terms\|kvkk_ack\|marketing…`), `version`, `granted`, `ip_masked` | **Append-only**; kayıtta `terms` + `kvkk_ack` |

**Seed sırası:** `ServerSeeder` → `ContentSeeder` → (`local` ise) `DemoSeeder`.

---

## 5. Mimari notlar

- **Güvenlik başlıkları (global):** nonce'lu CSP (`unsafe-inline` / `unsafe-eval` yok), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `X-Frame-Options: DENY`, `X-Request-Id`. Production + HTTPS'te HSTS.
- **CSRF:** Laravel token + `VerifyOrigin` (cross-site `Sec-Fetch-Site` / `Origin` → 419). İstisna yalnızca `webhooks/*`.
- **Cache:** Laravel 13 cache'i güvenlik gereği nesne `unserialize` etmez. Cache'e **yalnızca düz dizi/skaler** yazılır (bkz. `PublicLayoutComposer`, `ServerStatusResolver`).
- **Markdown:** CommonMark (`html_input: escape`) + Symfony HtmlSanitizer allowlist → `SafeHtml` (Htmlable). Blade'de `{!! !!}` kullanılmaz (mimari testiyle zorlanır).
- **Tema:** `mrp_theme` / `mrp_effects` cookie'leri sunucuda okunur ve `<html data-theme data-effects>` olarak render edilir (flaş yok). `prefers-reduced-motion` ve düşük efekt modu animasyonları kapatır.
- **Tasarım sistemi:** [docs/DESIGN.md](docs/DESIGN.md) — "Gece Haritası". Token'lar `resources/css/app.css`, bileşenler `resources/views/components/{ui,brand}`.
