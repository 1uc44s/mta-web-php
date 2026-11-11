# TODO — Görevler, Test Adımları ve Açık Kararlar

> **Sürüm:** 1.1.0 · **Son güncelleme:** 2026-11-11 · **Aktif faz:** Faz 1 — Temel kurulum (kapanış için 1.3 commit onayı ve 1.6 hosts kaydı bekleniyor)
> **İlgili:** [ROADMAP](ROADMAP.md) · [RULES §12](RULES.md#12-definition-of-done)

Durum işaretleri: `[ ]` yapılacak · `[~]` devam ediyor · `[x]` tamamlandı (kanıtla) · `[!]` bloke (sebebiyle)

---

## 0. Proje sahibinin aksiyonları (geliştirmeyi bloke edenler)

| # | Aksiyon | Neden | Durum |
|---|---|---|---|
| U-1 | PRD §10 kararlarını (D-01..D-14) ve özellikle **D-12 yorumunu** (Akademi / mesaj şablonu / branş / PWA) onayla veya düzelt | Tüm doküman seti bu kararlara dayanıyor | [ ] |
| U-2 | OD-01: Nihai marka adı (değilse "Meridyen Roleplay" ile devam) | Logo ve metinler | [ ] |
| U-3 | `php.ini` eklentileri (`intl, gd, zip, sodium`) | SRS C-01 | [x] Faz 1'de yapıldı. Yedek: `C:\xampp\php\php.ini.bak-2026-11-11`. CLI'da aktif; **Apache'nin yeniden başlatılması gerekiyor** (XAMPP Control Panel → Apache Stop/Start) |
| U-4 | Composer kurulumu | Composer yoktu | [x] Composer 2.11.2, imza doğrulamalı resmi kurulum betiğiyle `C:\Users\<kullanıcı>\bin\composer.phar` + `composer` / `composer.bat` sarmalayıcıları |
| U-5 | **Yönetici olarak** `C:\Windows\System32\drivers\etc\hosts` dosyasına `127.0.0.1 roleplay-web.test` satırı eklenecek | Vhost için. Yönetici yetkisi gerektirir | [ ] — o zamana kadar `php artisan serve` (`http://127.0.0.1:8000`) kullanılır |
| U-6 | OD-02: MTA tarafında mevcut bir gamemode/hesap sistemi var mı? Varsa hesap kimliği (`mta_account_ref`) neye karşılık geliyor? | Faz 7 | [ ] |
| U-7 | OD-03 / OD-04: Cüzdan türleri ve ödeme sağlayıcısı | Faz 9 | [ ] |
| U-8 | OD-05: WhatsApp kanalı gerçekten isteniyor mu? | Faz 10 | [ ] |

## 1. Ortam durumu (2026-11-11 tespiti)

| Bileşen | Bulunan | Gereken | Durum |
|---|---|---|---|
| PHP | 8.4.27 (ZTS, x64) | ≥ 8.3 (Laravel 13) | ✅ |
| Argon2id | Mevcut (`PASSWORD_ARGON2ID`) | Mevcut | ✅ |
| PHP eklentileri | `pdo_mysql, mbstring, openssl, fileinfo, curl, bcmath, intl, gd, zip, sodium` | aynı | ✅ (Apache yeniden başlatma bekleniyor) |
| MariaDB | 10.11.16 | 10.11+ | ✅ |
| Composer | 2.11.2 | 2.x | ✅ |
| Node / npm | 24.20.0 / 11.19.0 | ≥ 20 | ✅ |
| Git | Repo `main` dalıyla oluşturuldu, henüz commit yok | | ⏳ Faz 1.3 |
| Laravel | 13.42.0 | 13.x | ✅ |

## 2. Aktif faz — Faz 1: Temel kurulum

### 2.1 Görevler

- [x] **1.1** Ön koşullar: U-3, U-4 tamamlandı. Kanıt: T1.1, `composer -V` → 2.11.2.
- [x] **1.2** Laravel 13.42.0 kuruldu. İskeletin `CLAUDE.md`/`AGENTS.md` (Laravel Boost önyükleme metni) projeye özel `CLAUDE.md` ile değiştirildi, `welcome.blade.php` ve SQLite dosyası kaldırıldı. Kurulum yöntemi: Proje kökü boş olmadığı (`docs/` var) için:
  ```bash
  composer create-project laravel/laravel:^13.0 C:/xampp/htdocs/_rw_tmp
  ```
  Ardından `_rw_tmp` içeriği `roleplay-web/` köküne taşınır (`docs/` korunur), `_rw_tmp` silinir.
- [~] **1.3** `git init -b main` ✅, `.gitattributes` (`eol=lf`) ✅, `.gitignore` (+ `config.lua`, `storage/app/private`) ✅, secret sızıntı kontrolü ✅. **İlk commit proje sahibinin onayını bekliyor**: `chore: laravel 13 iskeleti + docs`.
- [x] **1.4** `roleplay_web`, `roleplay_web_test` ve `roleplay_app` kullanıcısı oluşturuldu, migration'lar geçti. Komut:
  ```bash
  C:/xampp/mysql/bin/mysql.exe -u root -e "CREATE DATABASE roleplay_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE roleplay_web_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  ```
  Ayrı bir uygulama kullanıcısı (`roleplay_app`) oluşturulur. Parola rastgele üretilir ve **yalnızca `.env` dosyasına** yazılır. `DB_CONNECTION=mariadb`, PDO seçenekleri [DATABASE §1.1](DATABASE.md#11-motor-ve-karakter-seti).
- [x] **1.5** `.env.example` (secret'sız) + `.env` (APP_KEY, DB parolası, `BRIDGE_SERIAL_PEPPER` dolu). `App\Support\ConfigValidator` HTTP isteklerinde APP_KEY, DB sürücüsü, argon2id, pepper biçimi ve production'da `APP_DEBUG` kontrolü yapar (konsolda çalışmaz: ilk kurulumu engellemesin diye). Varsayılanlar: locale `tr`, timezone `UTC` (config), hash `argon2id`.
- [!] **1.6** Bloke: U-5 (hosts kaydı yönetici yetkisi ister). O zamana kadar `php artisan serve --host=127.0.0.1 --port=8000`. Apache vhost (`C:\xampp\apache\conf\extra\httpd-vhosts.conf`):
  ```apache
  <VirtualHost *:80>
      ServerName roleplay-web.test
      DocumentRoot "C:/xampp/htdocs/roleplay-web/public"
      <Directory "C:/xampp/htdocs/roleplay-web/public">
          AllowOverride All
          Require all granted
      </Directory>
  </VirtualHost>
  ```
  `httpd.conf` içinde `Include conf/extra/httpd-vhosts.conf` satırının açık olduğu kontrol edilir. Apache yeniden başlatılır. (U-5 gerekli.) PWA testi için opsiyonel mkcert + SSL vhost: [ARCHITECTURE §8.5](ARCHITECTURE.md#85-geliştirme-ortamı-notu).
- [x] **1.7** Kurulan sürümler (hepsi Laravel 13 ile uyumlu, `composer audit` temiz): `laravel/fortify` 1.42, `mallardduck/blade-lucide-icons` 2.x, `league/commonmark` 2.10, `symfony/html-sanitizer` 8.1, `intervention/image` **4.4** (doküman 3.x diyordu, ARCHITECTURE güncellendi), `pestphp/pest` 4.7 + laravel plugin 4.1, `larastan/larastan` 3.15, `laravel/pint` 1.27. Fortify kurulu ama **rotaları Faz 3'e kadar kapalı** (`Fortify::ignoreRoutes()`): yapılandırılmamış `/register` gibi uçlar açık kalmasın diye.
- [x] **1.8** npm (`npm audit` temiz): `@alpinejs/csp` 3.17.5, `@fontsource-variable/archivo` 5.3, `@fontsource-variable/jetbrains-mono` 5.3, Tailwind 4.3.5, Vite 8.3.8. `app.css` içinde DESIGN §3 token'larının tamamı (`:root`/`[data-theme]` ham değerler + `@theme inline` eşlemesi), temel katman, odak halkası, düşük efekt/azaltılmış hareket kuralları. Bunny font CDN'i `vite.config.js`'den kaldırıldı.
- [x] **1.9** `AssignRequestId` (global), `SecurityHeaders` (global, nonce'lu CSP), `VerifyOrigin` (web). JSON hata biçimi `App\Support\ApiErrorResponse`. Hata sayfaları 403/404/419/429/500/503 (ortak `errors/layout`, meridyen küre, istek kimliği). Ek bulgu: Laravel'in `local` disk `storage/{path}` (GET+PUT) rotaları kapatıldı (`'serve' => false`).
- [x] **1.10** `config/roleplay.php` (link kodu, köprü, durum eşikleri, komut allowlist'i), `config/permissions.php` (ADMIN §3'ten **üretildi**: 68 izin), `routes/{panel,admin,api,webhooks}.php` bağlandı (`panel`/`admin` = `web + auth`; `api` öneki `api/v1`).
- [x] **1.11** `pint.json` (laravel + `declare_strict_types`), `phpstan.neon` (seviye 6), Pest arch testleri (php + security presetleri, strict types, `env()` yasağı, final sınıflar, controller'da DB yasağı), Blade `{!!` ve satır içi `style`/`on*` yasağı testleri. Scriptler: `composer test | lint | lint:fix | analyse | dev`.
- [x] **1.12** `MAIL_MAILER=log` (Mailpit Faz 3'te e-posta doğrulama akışı için kurulacak).

### 2.2 Faz 1 test adımları

| # | Adım | Beklenen |
|---|---|---|
| T1.1 | `php -m` | `intl, gd, zip, sodium, pdo_mysql` listede |
| T1.2 | `php artisan about` | Laravel 13.x, PHP 8.4, driver `mariadb`, timezone UTC, locale tr |
| T1.3 | `php artisan migrate` (temiz DB) | Çerçeve tabloları oluşur, hata yok |
| T1.4 | Tarayıcı: `http://roleplay-web.test/up` | 200 |
| T1.5 | `curl -I http://roleplay-web.test/` | `Content-Security-Policy` (nonce'lu), `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, `X-Request-Id` başlıkları var |
| T1.6 | Var olmayan URL | Özel 404 sayfası (Türkçe) |
| T1.7 | `curl -H "Accept: application/json" http://roleplay-web.test/yok` | SRS §8 formatında JSON (`requestId` dahil), stack trace yok |
| T1.8 | `composer lint` · `composer analyse` · `composer test` · `npm run build` | Dördü de 0 çıkış kodu |
| T1.9 | `APP_DEBUG=false` ile 500 tetikle | Özel 500 sayfası, dosya yolu veya SQL sızıntısı yok |
| T1.10 | `git status` | `.env`, `vendor`, `node_modules`, `public/build` izlenmiyor |

### 2.3 Çıkış kanıtı (2026-11-11)

T1.4–T1.7 `php artisan serve` (`http://127.0.0.1:8000`) üzerinde yapıldı; vhost U-5'i bekliyor.

| # | Sonuç |
|---|---|
| T1.1 | ✅ `gd intl pdo_mysql sodium zip` |
| T1.2 | ✅ Laravel 13.42.0 · PHP 8.4.27 · UTC · tr · mariadb · session/cache/queue database |
| T1.3 | ✅ 3 çerçeve migration'ı (users, cache, jobs) geçti |
| T1.4 | ✅ `/up` → 200 |
| T1.5 | ✅ CSP `script-src 'self' 'nonce-…'` (unsafe-* yok), nosniff, Referrer-Policy, Permissions-Policy, COOP, X-Frame-Options, X-Request-Id (ULID) |
| T1.6 | ✅ 404 → "Bu koordinatta bir şey yok", istek kimliği görünür, `noindex` |
| T1.7 | ✅ `{"success":false,"error":{"code":"NOT_FOUND",…},"requestId":"01M7…"}` |
| T1.8 | ✅ `composer lint` passed · `composer analyse` 0 hata (seviye 6) · `composer test` 32/32 (425 assertion) · `npm run build` (CSS 7,2 KB, JS 23 KB gzip) |
| T1.9 | ✅ Otomatik testle: `ErrorRenderingTest` — `APP_DEBUG=false` iken 500 HTML/JSON yanıtında exception sınıfı, SQL ve dosya yolu yok |
| T1.10 | ✅ `.env`, `vendor`, `node_modules`, `public/build`, loglar ignore'da. Commit adaylarında `.env` secret'ı bulunmadı |
| Görsel | ✅ Ana sayfa ve 404 (375 px ve masaüstü): yatay taşma yok, yalnızca Latin + Latin-ext font dosyaları indiriliyor, konsolda CSP ihlali yok |

**Faz 1 kapanış koşulları:** (1) ilk commit için onay, (2) U-5 sonrası vhost ile T1.4–T1.7'nin `roleplay-web.test` üzerinde tekrarı, (3) Apache'nin yeniden başlatılması.

## 3. Sonraki fazlar — iş listesi

Ayrıntılı görev tabloları [ROADMAP.md](ROADMAP.md) içindedir. Faz başladığında görevler bu dosyanın §2 bölümüne taşınır ve alt adımlara bölünür.

- [x] **Faz 2** Tasarım sistemi + public site — 2026-11-11, commit `9fdb1fe`. Kanıt: 108 test / 622 assertion yeşil, Larastan 0 hata, Pint temiz; tüm public rotalar 200; 375 px'te yatay taşma yok (header'daki responsive sınıf çakışması bulunup düzeltildi). Kapsam notları: `system_settings`, `mta_servers` ve Akademi tabloları planlanandan erken (Faz 2) oluşturuldu çünkü public sayfalar bunlara ihtiyaç duyuyor; ACP düzenleme ekranları Faz 4–5'te. Kalıcı logo ve OG görseli OD-01'i bekliyor (geçici SVG favicon ve logo bileşeni mevcut).
- [ ] **Faz 3** Kimlik doğrulama (3.1–3.8)
- [ ] **Faz 4** RBAC + branşlar + ACP iskeleti (4.1–4.11)
- [ ] **Faz 5** CMS + Akademi + şablon motoru (5.1–5.9)
- [ ] **Faz 6** Oyuncu paneli + destek + temel PWA (6.1–6.9)
- [ ] **Faz 7** MTA köprüsü (7.1–7.9)
- [ ] **Faz 11 (R1)** Sertleştirme ve yayın
- [ ] **Faz 8** Başvuru + moderasyon + cihaz + komutlar
- [ ] **Faz 9** Ekonomi + ödeme
- [ ] **Faz 10** Reverb, Web Push, Discord, WhatsApp, Akademi sınavı
- [ ] **Faz 11 (R2)** Sertleştirme ve yayın

## 4. Test planı (fazlar boyunca biriken)

### 4.1 Otomatik test paketleri

| Paket | İçerik | İlk faz |
|---|---|---|
| `tests/Arch` | Katman kuralları, yasak fonksiyonlar, `{!!` allowlist, `strict_types` | 1 |
| `tests/Unit/Design/ContrastTest` | Token kontrast oranları (DESIGN §3.1) | 2 |
| `tests/Feature/Public/*` | Public sayfalar, SEO, sitemap, yayın durumu filtresi | 2 |
| `tests/Unit/Bridge/ServerStatusResolverTest` | online/stale/offline/maintenance sınırları (89 sn, 90 sn, 300 sn, 301 sn) | 2 |
| `tests/Feature/Auth/*` | FR-AUTH-001..020 | 3 |
| `tests/Feature/Security/AdminRouteProtectionTest` | Her `admin.*` rotası × izinsiz personel / oyuncu | 4 |
| `tests/Feature/Security/RoleMatrixTest` | ADMIN §3 matrisi (veri sağlayıcılı) | 4 |
| `tests/Unit/Audit/AuditChainTest` | Hash zinciri + kurcalama tespiti | 4 |
| `tests/Unit/Messaging/TemplateRendererTest` | Söz dizimi, filtreler, SSTI denemeleri, kanal escape | 5 |
| `tests/Unit/Content/MarkdownSanitizerTest` | XSS vektör seti | 5 |
| `tests/Feature/Support/*` | FR-SUP, iç not izolasyonu, IDOR, upload bypass | 6 |
| `tests/Feature/Bridge/*` | İmza, replay, zaman penceresi, idempotent olaylar, hesap bağlama | 7 |
| `tests/Unit/Wallet/LedgerServiceTest` | Idempotency, negatif bakiye, ters kayıt, eşzamanlılık | 9 |
| `tests/Feature/Payments/*` | Webhook tekrar, tutar uyuşmazlığı, iade dört göz | 9 |

### 4.2 Manuel / E2E senaryoları (brief §16.3)

| # | Senaryo | Faz | Durum |
|---|---|---|---|
| E2E-1 | Ziyaretçi ana sayfayı açar → "Nasıl Oynanır?" → `/start` | 2 | [ ] |
| E2E-2 | Yeni oyuncu kayıt olur | 3 | [ ] |
| E2E-3 | E-postasını doğrular (Mailpit) | 3 | [ ] |
| E2E-4 | Giriş yapar | 3 | [ ] |
| E2E-5 | Panelde bağlama kodu üretir | 7 | [ ] |
| E2E-6 | Oyunda `/webbagla` ile hesabı bağlar | 7 | [ ] |
| E2E-7 | Panelde bağlı hesabını ve karakterlerini görür | 7 | [ ] |
| E2E-8 | Destek talebi açar (ekli) | 6 | [ ] |
| E2E-9 | Destek yetkilisi yanıtlar + iç not ekler | 6 | [ ] |
| E2E-10 | Oyuncu yanıtı görür, iç notu **görmez** | 6 | [ ] |
| E2E-11 | Admin içerik yayınlar → public'te görünür | 5 | [ ] |
| E2E-12 | Finans yöneticisi bakiye hareketlerini görür ama rol yönetemez | 9 | [ ] |
| E2E-13 | MTA heartbeat public durum kartını günceller. MTA durunca 90 sn sonra "Doğrulanamıyor", 5 dk sonra "Çevrimdışı" gösterilir | 7 | [ ] |
| E2E-14 | Personel MFA kurmadan `/admin` alanına giremez | 4 | [ ] |
| E2E-15 | Mobil 360 px'te PWA kurulumu, offline sayfası, güncelleme toast'ı | 6 | [ ] |

### 4.3 Güvenlik test kontrol listesi (brief §16.4)
- [ ] SQL injection (arama, sıralama, filtre parametreleri)
- [ ] Stored / reflected XSS (ticket, içerik, şablon, kullanıcı adı)
- [ ] CSRF (token'sız POST, cross-site Origin). Not: Laravel `ValidateCsrfToken` birim testlerde kendini atladığı için token'sız POST → 419 kontrolü burada (E2E/manuel) yapılır. Cross-site Origin kontrolü `VerifyOriginTest` ile otomatik test edilmiştir.
- [ ] IDOR / BOLA (ULID tahmini, başka kullanıcının kaynakları, branş dışı kaynaklar)
- [ ] Brute force (giriş, 2FA, link kodu, şifre sıfırlama)
- [ ] Session fixation / hijacking (login sonrası ID değişimi, `HttpOnly`, `Secure`)
- [ ] Yetkisiz admin API çağrısı
- [ ] Dosya yükleme bypass (çift uzantı, MIME sahteciliği, polyglot, SVG)
- [ ] Webhook replay ve imza sahteciliği
- [ ] Köprü HMAC replay, eski zaman damgası, gövde değişikliği
- [ ] Rate limit bypass (`X-Forwarded-For` sahteciliği → `TrustProxies` doğru yapılandırılmış mı?)
- [ ] Şablon motoru SSTI

## 5. Açık sorular ve riskler (takip)

| # | Konu | Sahip | Son tarih | Not |
|---|---|---|---|---|
| Q-1 | Laravel 13 ile Fortify ve diğer paketlerin uyumluluğu | Geliştirici | Faz 1.7 | Uyumsuzluk varsa PRD'ye karar yazılır |
| Q-2 | MTA 1.6 `hash()` HMAC desteği | Geliştirici | Faz 7.1 | Spike |
| Q-3 | Production'da MySQL mi MariaDB mi? | Proje sahibi | Faz 11 | Şema ikisinde de çalışacak şekilde tasarlandı |
| Q-4 | Analitik aracı (çerezsiz/self-host tercih edilir) | Proje sahibi | Faz 2 | `analytics.enabled=false` varsayılan |
| Q-5 | Gerçek oyun içi ekran görüntüleri ve video | Proje sahibi | Faz 2 sonu | Yokken soyut yer tutucu kullanılır, sahte görsel yok |

## 6. Faz günlüğü

| Faz | Başlangıç | Bitiş | Tahmin (gün) | Gerçekleşen | Not |
|---|---|---|---|---|---|
| 0 | 2026-11-11 | 2026-11-11 | 1 | 1 | 12 doküman + README oluşturuldu |
| 1 | 2026-11-11 | (açık: commit + vhost) | 4 | ~1 (iş günü altında) | Composer/eklentiler ajan tarafından kuruldu. Faz 2'de dikkat: Tailwind yalnızca derleme anında var olan sınıfları üretir; yeni view'lardan sonra `npm run build` veya `npm run dev` gerekir |

## 7. Bilinen eksikler
_(Her faz sonunda tamamlanmamış veya bilerek ertelenmiş maddeler buraya yazılır.)_

- Brief'teki `docs/architecture.md, api.md, deployment.md, security.md, operations.md` yapısı bu set ile karşılandı. `deployment.md` ve `operations.md` içerikleri Faz 11'de **operasyon dokümantasyonu** olarak ayrıca yazılacak.
