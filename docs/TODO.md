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
- [x] **Faz 3** Kimlik doğrulama — 2026-11-11. Kanıt: 142 test / 790 assertion yeşil (`tests/Feature/Auth/AuthFlowTest` 30+ senaryo: kayıt, rezerv ad [harf duyarsız], zayıf/kimlik içeren parola, enumerasyonsuz kayıt + `auth.already_registered`, imzalı/oturumsuz doğrulama, kullanıcı adı veya e-posta ile giriş, 5/dk throttle → 429, askı mesajı + süresi dolan askı, oturumdayken askı → çıkış, `Clear-Site-Data`, enumerasyonsuz şifre sıfırlama, sıfırlamada tüm oturumların silinmesi, 2FA kurtarma kodu), Larastan 0, Pint temiz. Tarayıcıda uçtan uca: kayıt → log'daki doğrulama bağlantısı → giriş → `/panel` (375 px taşma yok, CSP ihlali yok). Kapsam: 3.7 `MessageDispatcher` v1 (`lang/tr/messages.php` gömülü şablonlar, katı değişken listesi, kuyruklu `TemplatedMailNotification` 3 deneme 1/5/15 dk); 3.8 `RedactSensitiveData` log tap'i. Bulgular: test sırasında `sha1` mimari kuralına takıldı → doğrulama hash'i `sha256(email)`; Fortify bilinmeyen e-postada farklı yanıt veriyordu → `FailedPasswordResetLinkRequestResponse` başarı yanıtına bağlandı; `--no-reload` ile başlatılan `artisan serve` .env değişikliğini görmez (yeniden başlat). Doğrulama bağlantısı `APP_URL` hostuyla imzalanır.
- [x] **Faz 4** RBAC + branşlar + ACP iskeleti — 2026-11-12. Kanıt: 176 test / 1833 assertion yeşil (`RoleMatrixTest`: config/roles.php = ADMIN §3 ve 9 rol × 68 izin pozitif+negatif; `AdminRouteProtectionTest`: 32 admin rotası × misafir/oyuncu(404)/2FA'sız personel/izinsiz personel(403)/süper admin; `AuditChainTest`: zincir, append-only trigger, sahte kayıt tespiti; `AccessControlTest`: hiyerarşi, son süper admin, son rol → branş/oturum temizliği, askı, PII maskeleme, süper-only ayarlar, branş lideri kuralları, `mrp:make-super-admin`, `mrp:expire-roles`, 30 dk idle, aktivite logu, finans audit kapsamı, personelin 2FA kapatamaması). Larastan 0, Pint temiz. Tarayıcıda: süper admin parola + 2FA (kurtarma kodu) ile giriş, 16 ACP/2FA sayfası 200, 375 px taşma yok, CSP ihlali yok. Kararlar (ADMIN §1'e işlendi): sistem rolleri ACP'de salt okunur; personel olmayana /admin 404. Bulgular: Fortify QR etiketi `login` niteliğini okuyordu → `User::twoFactorQrCodeUrl()` kullanıcı adıyla override; `{branch}/members/{user:ulid}` kapsamlı bağlama 500 veriyordu → `withoutScopedBindings()`; controller'larda DB facade yasağı → `SaveRole`, `HealthProbe`.
- [x] **Faz 5** CMS + Akademi + şablon motoru — 2026-11-12. Kanıt: 222 test yeşil (`CmsTest` 30: E2E-11 oluştur→taslak gizli→yayınla→public 200, editör yayınlayamaz/incelemeye gönderir, zamanlanmış yayın + `content:publish-scheduled`, geçersiz geçiş 422, arşiv → public 404, Türkçe slug + benzersizlik + rezerv/biçim, sistem sayfası silinemez, bölüm şema doğrulaması + şema dışı anahtar atma, sayfa revizyonu + geri yükleme, kural revizyonu, kural sürümü + oyuncu onayı (idempotent, consent), Akademi ilerlemesi, duyuru bandı/panel/e-posta işi tek sefer, e-posta yalnızca doğrulanmış hesaplara, şablon söz dizimi kayıtta red + revizyon + JSON önizleme, DB şablonundan e-posta + `message_dispatches`, medya WebP varyantları + SVG/alt metin reddi + kullanımda 409, 14 vektörlük Markdown XSS seti; `TemplateRendererTest` 20: filtreler, koşul, 12 SSTI/söz dizimi reddi, değer enjeksiyonu yok). `AdminRouteProtectionTest` artık 200+ admin rotasını tarıyor (süper admin 50+ GET sayfası 200). Larastan 0, Pint temiz. Tarayıcıda: süper admin ile 16 CMS ekranı 200, haber oluştur → yayınla → public 200, 375 px taşma yok. Bulgular: `news_posts.author_label` ve 4 Markdown alanı DB'de NOT NULL'du → alan `default` desteği + zorunlu kural (information_schema karşılaştırmasıyla tespit); rota `defaults('resource')` konumsal parametre olarak id'den sonra geldiği için controller kaynağı rotadan okuyor. Kapsam farkları docs/CMS.md §10'da (JSON bölüm editörü, kısa kodlar R2, `factions.update_own` Faz 8).
- [x] **Faz 6** Oyuncu paneli + destek + temel PWA — 2026-11-12. Kanıt: 253 test yeşil (`SupportTest` 22: E2E-8 ekli talep + `T-000001` + branş + olay, idempotency, doğrulama, doğrulanmamış e-posta engeli, 6 upload bypass vektörü (sahte MIME, çift uzantı, SVG, EXE, sahte PDF, 5 MB+), polyglot PNG → WebP yeniden kodlama (PHP yükü yok), IDOR 404 (talep, mesaj, ek), **iç not 3 katman** (scope + uç + görünüm, bildirim yok), branş kapsamı, E2E-9 makro değişkenli yanıt + panel/e-posta bildirimi + durum geçişleri, e-posta tercihi kapalıyken yalnız panel, E2E-10 kapat/yeniden aç (7 gün kuralı), escalation + audit + branş dışı atama reddi, otomatik resolved/closed; `PanelTest` 9: noindex/no-store, sayaç + ETag 304 + sahiplik, tema/efekt cookie, parola değişimi (mevcut parola, diğer oturumlar, e-posta), e-posta değişikliği (yeni adrese onay, GET değiştirmez, tek kullanım, eski adrese bilgi), oturum listesi maskeli IP + PC + sahiplik, hesap silme → 14 gün → anonimleştirme + audit, ACP anonimleştirme, PWA dosyaları). Larastan 0, Pint temiz. Tarayıcıda: oyuncu talep açtı (ek ile) → süper admin kuyrukta gördü, makrolu yanıt + iç not → oyuncu yanıtı gördü, iç notu görmedi, sayaç 1; panel sayfaları 375 px taşmasız, alt sekme çubuğu. Bulgular: kuyruk filtre placeholder'ı numara biçimiyle çakışıyordu; eksik dosyalı ek indirmesi 500 → 404; zaman yolculuğunda parola onayı `time()` yerine `now()`; Laravel bildirim sınıfı `attempts()` taşımaz → `increment`.
- [x] **Faz 7** MTA köprüsü + Lua resource — 2026-11-12. Kanıt: `BridgeSecurityTest` 20 (ortak HMAC vektörleri [RFC 4231 + 3 canonical] PHP'de doğrulandı, geçerli ping, yanlış imza/bilinmeyen anahtar/bozuk nonce/eksik başlık 401, ±300 sn penceresi, aynı nonce 409, geçersiz imza nonce yazmaz, iptal/süresi dolmuş anahtar, kapsam 403, 64 KB+ 413, şema 422, heartbeat + public durum `online`, event_id idempotency, bağlı olmayan hesap oluşturulmaz, karakter senkronu yalnız bağlı hesapta + profil allowlist + HTML sıyırma, 20+ hata → tek kritik audit, yanıtta e-posta/IP yok); `AccountLinkTest` 17 (Crockford kod + HMAC saklama + tek gösterim, tek aktif kod, 5/saat, bağlama + tek kullanım + audit + polling, süre dolumu, hesap başına 5 yanlış → 429, başka hesaba bağlı 409, DB unique, kullanıcı başına 3 sınırı, tekrar gelen event, kaldırma talebi → onay → `account.link_revoked` komutu, IDOR 404, karakter sahipliği, profil gizleme + audit, ACP anahtar tek gösterim + iptal, ≤ 2 aktif anahtar, izinsiz personel 403). `AdminRouteProtectionTest` yeni 12 admin rotasını kapsıyor. Larastan 0, Pint temiz. Tarayıcıda: oyuncu kod üretti → gerçek HTTP üzerinden imzalı ping/heartbeat/verify/characters.sync simülasyonu → panel 5 sn polling ile "bağlandı" gösterdi, karakter listesi doldu, 375 px taşma yok. Kararlar: HMAC Lua'da saf RFC 2104 (`hash("sha256")` + `bitXor`) ve başlangıçta `tests/vectors.lua` öz-testi (Q-2 spike'ı bu şekilde kapatıldı); bakım komutları `bridge:maintain` altında birleştirildi (ARCHITECTURE §10 güncellendi); komut kuyruğu uçları Faz 8, Lua tarafı `ENABLE_COMMANDS=false` ile hazır. Hesap anonimleştirme bağlantıları `revoked` yapar ve kodları siler (DATABASE §13 adım 3). Açık: U-6 (gamemode hesap kimliği) — varsayılan `getAccountID`, `config.lua` ile değiştirilebilir. Bulgular: rota katalog testi yeni `{link}` parametresi için fixture istedi; Git Bash yol dönüşümü (`MSYS_NO_PATHCONV=1`) simülatör argümanlarını bozuyordu.
- [ ] **Faz 11 (R1)** Sertleştirme ve yayın — 2026-11-12 yerel kısım tamamlandı; yayın için aşağıdaki açık maddeler kaldı.
  - [x] 11.2 Güvenlik testleri: brief §16.4'ün tamamı testle kapsanıyor. Yeni `tests/Feature/Security/ReleaseGateTest.php`: 4 SQL injection yükü × (giriş, public arama, rota parametresi, ACP aramaları) — kullanıcı tablosu sağlam; session fixation (girişte oturum kimliği değişir); çıkışta oturum geçersiz; `X-Forwarded-For` sahteciliğiyle giriş (5/dk) ve köprü heartbeat (6/dk) hız sınırı atlatılamaz; yansıyan XSS (public + ACP arama). Diğer maddeler: XSS (`CmsTest` 14 vektör), CSRF/Origin, IDOR (`SupportTest`, `AccountLinkTest`), brute force (`AuthFlowTest`), yetkisiz admin (`AdminRouteProtectionTest`), upload bypass (`SupportTest` 6 vektör + polyglot), HMAC replay (`BridgeSecurityTest`), webhook replay → Faz 9. `composer audit` ve `npm audit` temiz (0 açık).
  - [x] 11.3 (kısmi) Sorgu sayısı sınırları testte: ana sayfa ≤ 25, haberler/kurallar/fraksiyonlar ≤ 20, panel sayfaları ≤ 30. **Açık:** Lighthouse ve `server-status` 1000 eşzamanlı yük testi production benzeri ortamda.
  - [x] 11.4 (doküman) `docs/OPERATIONS.md` + `deploy/` (Nginx, Supervisor, cron, `backup.sh`), ayrı DB kullanıcıları (uygulama: DDL/trigger yok), `__Host-` oturum çerezi. **Açık:** gerçek sunucu kurulumu (U-5 / barındırma kararı).
  - [x] 11.5 Yedek + geri yükleme testi 2026-11-12 yerelde ✅ (OPERATIONS §4 tablosu). **Açık:** production'da tekrar.
  - [x] 11.6 `mrp:health-check` (5 dk): başarısız işler, kuyruk gecikmesi, disk < %10, MTA heartbeat > 5 dk → `security` log + çıkış kodu 1. Yerel çalıştırmada gerçek bulgu: geliştirme makinesinin diski %8,9 boş. **Açık:** harici uptime izleyicisi ve log iletimi (sağlayıcı seçimi).
  - [ ] 11.7 Hukuk: yasal metinlerin onayı (OD-08) — proje sahibi.
  - [ ] 11.8 Marka (OD-01), gerçek içerik ve ekran görüntüleri — proje sahibi.
  - [x] 11.9 Operasyon dokümantasyonu: dağıtım, geri alma, break-glass, rotasyon (OPERATIONS §2–7).
  - [ ] 11.10 Yayın sonrası 72 saat izleme — yayın tarihinde.
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
| `tests/Feature/Auth/*` | FR-AUTH-001..016 (kayıt, doğrulama, giriş, askı, sıfırlama, 2FA) | 3 ✅ |
| `tests/Unit/Messaging/DefaultTemplatesTest`, `tests/Unit/Logging/RedactSensitiveDataTest` | Katı şablon bağlamı · log redaksiyonu | 3 ✅ |
| `tests/Feature/Security/AdminRouteProtectionTest` | Her `admin.*` rotası × izinsiz personel / oyuncu | 4 ✅ |
| `tests/Feature/Security/RoleMatrixTest` | ADMIN §3 matrisi (veri sağlayıcılı) | 4 ✅ |
| `tests/Feature/Audit/AuditChainTest` | Hash zinciri + kurcalama tespiti | 4 ✅ |
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
| E2E-2 | Yeni oyuncu kayıt olur | 3 | [x] (tarayıcı, 2026-11-11) |
| E2E-3 | E-postasını doğrular (log mailer) | 3 | [x] (tarayıcı, 2026-11-11) |
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
