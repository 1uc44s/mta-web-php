# Roleplay Web Platformu (çalışma adı: Meridyen Roleplay)

MTA:SA (Multi Theft Auto: San Andreas) roleplay topluluğu için web platformu:

- **Public site** — tanıtım, kurallar, haberler, Akademi, fraksiyonlar, meslekler, etkinlikler.
- **Oyuncu paneli** (`/panel`) — hesap, oyun hesabı bağlama, karakterler, destek talepleri, başvurular, cezalar, cüzdan, bildirimler.
- **Yönetim paneli** (`/admin`) — rol/izin, branşlar, CMS, destek kuyruğu, moderasyon, ekonomi, marka, sistem sağlığı, denetim kaydı.
- **MTA köprüsü** (`/api/v1/game`) — HMAC imzalı oyun sunucusu ↔ web API'si ve `mta-resource/web_bridge` Lua kaynağı.

> **Marka adı kodda sabit değildir.** Site adı, kısa ad, slogan, logo ve sekme ikonu **ACP → Marka** ekranından değiştirilir (bkz. [§5](#5-marka-ad-logo-sekme-ikonu)).
>
> **Tek doğruluk kaynağı:** [`docs/`](docs/README.md). Her özellik önce dokümana işlenir, sonra kodlanır.

## İçindekiler

1. [Teknolojiler ve sürümler](#1-teknolojiler-ve-sürümler)
2. [Kurulum](#2-kurulum)
3. [Varsayılan yönetici hesabı](#3-varsayılan-yönetici-hesabı)
4. [Komutlar](#4-komutlar)
5. [Marka (ad, logo, sekme ikonu)](#5-marka-ad-logo-sekme-ikonu)
6. [Web rotaları (sayfalar ve formlar)](#6-web-rotaları-sayfalar-ve-formlar)
7. [API dokümantasyonu](#7-api-dokümantasyonu)
8. [Veritabanı](#8-veritabanı)
9. [Operasyon ve production](#9-operasyon-ve-production)
10. [Mimari notlar](#10-mimari-notlar)
11. [Faz durumu](#11-faz-durumu)

---

## 1. Teknolojiler ve sürümler

### 1.1 Diller

| Dil | Kullanım |
|---|---|
| **PHP 8.4** | Uygulama (Laravel), `declare(strict_types=1)` her dosyada |
| **Blade** (HTML) | Sunucu tarafı şablonlar — SPA yoktur |
| **CSS** (Tailwind CSS v4) | Tasarım token'ları `resources/css/app.css` |
| **JavaScript** (ES modülleri) | Küçük, framework'süz modüller + Alpine.js (CSP build) |
| **SQL** (MariaDB / MySQL) | Migration'lar, trigger'lar (append-only defterler) |
| **Lua 5.1** (MTA:SA) | Oyun sunucusu köprü kaynağı `mta-resource/web_bridge` |
| **Bash / Nginx / Supervisor yapılandırması** | `deploy/` örnek production dosyaları |

### 1.2 Sürümler (geliştirmede doğrulanmış)

| Bileşen | Gereken | Doğrulanan sürüm |
|---|---|---|
| PHP | 8.3+ (8.4 önerilir) | 8.4.27 |
| PHP eklentileri | `intl, gd, zip, sodium, pdo_mysql, mbstring, bcmath, openssl, fileinfo` | — |
| Laravel Framework | ^13.17 | 13.42.0 |
| Composer | 2.x | 2.11.2 |
| MariaDB / MySQL | MariaDB 10.11+ veya MySQL 8.4+ | MariaDB 10.11.16 |
| Node.js / npm | Node 20+ (22+ önerilir) | Node 24.20 / npm 11.19 |
| Vite | ^8 | 8.3.8 |
| Tailwind CSS | ^4 | 4.x |
| MTA:SA sunucu | 1.6.0+ | — |

### 1.3 Başlıca paketler

**PHP (composer):** `laravel/framework` 13, `laravel/fortify` (giriş, 2FA, parola sıfırlama), `laravel/reverb` (WebSocket, isteğe bağlı), `intervention/image` 4 (görsel yeniden kodlama), `league/commonmark` (Markdown), `symfony/html-sanitizer` (XSS temizliği), `minishlink/web-push` (Web Push), `mallardduck/blade-lucide-icons` (ikonlar).
**Geliştirme:** `pestphp/pest` 4 + `pest-plugin-laravel`, `larastan/larastan` 3 (seviye 6), `laravel/pint` (PSR-12), `phpunit/phpunit` 12.
**JavaScript (npm):** `@alpinejs/csp`, `laravel-echo` + `pusher-js` (yalnızca Reverb açıkken, tembel yüklenir), `@fontsource-variable/archivo`, `@fontsource-variable/jetbrains-mono`, `tailwindcss` 4, `vite` 8, `laravel-vite-plugin` 3.

---

## 2. Kurulum

### 2.1 Gereksinimler

- PHP 8.4 + yukarıdaki eklentiler, Composer 2
- MariaDB 10.11+ (veya MySQL 8.4+), `utf8mb4_unicode_ci`
- Node.js 20+ ve npm
- Git
- (İsteğe bağlı) MTA:SA 1.6 sunucusu — oyun köprüsü için

### 2.2 Yerel kurulum — Windows + XAMPP (adım adım)

1. **XAMPP** kur (PHP 8.4 içeren sürüm). `C:\xampp\php\php.ini` içinde şu satırların başındaki `;` işaretini kaldır: `extension=intl`, `extension=gd`, `extension=zip`, `extension=sodium`, `extension=pdo_mysql`, `extension=fileinfo`. XAMPP Control Panel'den **MySQL**'i başlat.
2. **Composer** ve **Node.js** kur. PATH'te `php` yoksa komutlarda `C:\xampp\php\php.exe` kullan (Git Bash'te `/c/xampp/php/php.exe`).
3. Depoyu klonla:
   ```bash
   git clone https://github.com/1uc44s/mta-web-php.git C:/xampp/htdocs/roleplay-web
   cd C:/xampp/htdocs/roleplay-web
   ```
4. Veritabanı ve kullanıcı oluştur (`C:\xampp\mysql\bin\mysql.exe -u root`):
   ```sql
   CREATE DATABASE roleplay_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE roleplay_web_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'roleplay_app'@'localhost' IDENTIFIED BY '<güçlü-parola>';
   GRANT ALL PRIVILEGES ON roleplay_web.* TO 'roleplay_app'@'localhost';
   GRANT ALL PRIVILEGES ON roleplay_web_test.* TO 'roleplay_app'@'localhost';
   ```
   Not: migration'lar trigger oluşturur; MariaDB'de ikili log açıksa `SET GLOBAL log_bin_trust_function_creators = 1;` gerekebilir.
5. Bağımlılıkları kur ve ortam dosyasını hazırla:
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```
6. `.env` içinde en az şunları doldur:
   - `APP_ENV=local`, `APP_URL=http://localhost:8000` (veya vhost adresin)
   - `DB_DATABASE=roleplay_web`, `DB_USERNAME=roleplay_app`, `DB_PASSWORD=<parola>`
   - `BRIDGE_SERIAL_PEPPER` — 64 karakter hex: `php -r "echo bin2hex(random_bytes(32));"`
7. Tabloları ve başlangıç verisini oluştur, ön yüzü derle:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   npm run build
   ```
   `APP_ENV=local` iken seed ayrıca örnek içerik (`DemoSeeder`) ve **varsayılan yönetici hesabını** (`DefaultAdminSeeder`, bkz. [§3](#3-varsayılan-yönetici-hesabı)) oluşturur.
8. Çalıştır:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```
   Tarayıcı: `http://127.0.0.1:8000` · Oyuncu paneli: `/panel` · Yönetim paneli: `/admin`.
9. (Önerilir) Arka plan işçileri — e-postalar, bildirimler ve ödeme olayları kuyruktan işlenir; zamanlanmış görevler zamanlayıcıyla çalışır:
   ```bash
   php artisan queue:work --queue=critical,default,mail,bulk
   php artisan schedule:work
   ```
   Sunucu, kuyruk, log ve Vite'ı tek komutla başlatmak için: `composer dev`.

**Apache vhost ile (`http://roleplay-web.test`):** `C:\Windows\System32\drivers\etc\hosts` dosyasına `127.0.0.1 roleplay-web.test`, `C:\xampp\apache\conf\extra\httpd-vhosts.conf` dosyasına `DocumentRoot "C:/xampp/htdocs/roleplay-web/public"` gösteren bir `<VirtualHost *:80>` ekle, Apache'yi yeniden başlat ve `.env` `APP_URL` değerini güncelle.

### 2.3 Yerel kurulum — Linux / macOS

```bash
git clone https://github.com/1uc44s/mta-web-php.git roleplay-web && cd roleplay-web
composer install && npm install
cp .env.example .env && php artisan key:generate
# .env: DB_*, BRIDGE_SERIAL_PEPPER, APP_ENV=local
php artisan migrate --seed
php artisan storage:link
npm run build
composer dev        # serve + queue + logs + vite
```

### 2.4 Ortam değişkenleri (`.env`)

| Değişken | Örnek / varsayılan | Açıklama |
|---|---|---|
| `APP_NAME` | `Meridyen Roleplay` | Yalnızca ilk değer; asıl ad ACP → Marka'dan gelir |
| `APP_ENV` | `local` / `production` | `local` örnek içerik ve varsayılan admin üretir |
| `APP_KEY` | `php artisan key:generate` | Şifreleme anahtarı (**gizli**) |
| `APP_DEBUG` | `true` (yerel) / `false` (production) | Production'da mutlaka `false` |
| `APP_URL` | `http://localhost:8000` | İmzalı bağlantılar bu adresle üretilir |
| `DB_CONNECTION` | `mariadb` | `mariadb` veya `mysql` |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` | |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | | **Parola gizli**, repoya girmez |
| `SESSION_DRIVER` | `database` | Oturumlar `sessions` tablosunda |
| `SESSION_SECURE_COOKIE` | `false` (http) / `true` (https) | |
| `CACHE_STORE` | `database` | Production'da `redis` olabilir |
| `QUEUE_CONNECTION` | `database` | İşçi: `queue:work` |
| `MAIL_MAILER` | `log` veya `smtp` | Yerelde Mailpit (`127.0.0.1:1025`) önerilir |
| `BRIDGE_SERIAL_PEPPER` | 64 hex | Cihaz serial HMAC'i için (**gizli**, sonradan değiştirilmez) |
| `PAYMENTS_PROVIDER` | `fake` | Gerçek sağlayıcı OD-04 sonrası |
| `BROADCAST_CONNECTION` | `log` | Gerçek zaman için `reverb` |
| `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST/PORT/SCHEME` | | Yalnızca Reverb açıkken |
| `VAPID_SUBJECT/PUBLIC_KEY/PRIVATE_KEY` | `php artisan mrp:vapid-keys` | Web Push (özel anahtar gizli) |
| `DISCORD_WEBHOOK_ANNOUNCEMENTS` / `DISCORD_WEBHOOK_OPS` | `https://discord.com/api/webhooks/…` | Gizli |
| `DEFAULT_ADMIN_USERNAME/EMAIL/PASSWORD` | `1uc44s` / `1uc44s@git.hub` / `lucasisthebest` | Yalnızca `APP_ENV=local` (§3) |

### 2.5 İsteğe bağlı özellikler

Çoğu modül **varsayılan kapalıdır** ve ACP → Ayarlar → *features* grubundan açılır: `features.applications` (başvurular), `features.sanctions` (cezalar/itirazlar), `features.devices` (cihaz bağlama), `features.wallet` / `features.payments` (cüzdan/ödeme, sahte sağlayıcı), `features.push` (tarayıcı bildirimi), `features.academy_quiz` (Akademi sınavları). Kapalı bir özelliğin rotaları 404 döner ve menüde görünmez.

| Özellik | Ek kurulum |
|---|---|
| Gerçek zaman (Reverb) | `.env` `BROADCAST_CONNECTION=reverb` + `REVERB_*`; `php artisan reverb:start` |
| Web Push | `php artisan mrp:vapid-keys` → `.env` `VAPID_*` → `features.push`. HTTPS ve service worker gerekir (Windows/XAMPP'ta komut için `OPENSSL_CONF=C:\xampp\apache\conf\openssl.cnf`) |
| Discord | `.env` `DISCORD_WEBHOOK_*` → ACP → Ayarlar → `discord.announcements` / `discord.ops_alerts` |
| Ödeme (test) | `features.wallet` + `features.payments` — yalnızca `FakeProvider` (production'da kullanılamaz) |

### 2.6 MTA:SA köprü kaynağı (`mta-resource/web_bridge`)

1. `mta-resource/web_bridge` klasörünü MTA sunucusunun `mods/deathmatch/resources/` dizinine kopyala.
2. `config.example.lua` → `config.lua` olarak kopyala.
3. Web'de **ACP → MTA entegrasyonu** → sunucuyu seç → **Yeni anahtar oluştur**; çıkan `key_id` ve `secret` değerlerini `config.lua` içindeki `KEY_ID` / `SECRET` alanlarına yaz (secret yalnızca bir kez gösterilir). `BASE_URL` sitenin adresi olmalı (production'da `https://`; yerelde `http://127.0.0.1:8000`).
4. `acl.xml` içinde kaynağa `function.fetchRemote` ve `function.kickPlayer` izni ver:
   ```xml
   <group name="WebBridge">
       <acl name="WebBridge" />
       <object name="resource.web_bridge" />
   </group>
   <acl name="WebBridge">
       <right name="function.fetchRemote" access="true" />
       <right name="function.kickPlayer" access="true" />
   </acl>
   ```
5. Sunucu konsolunda `start web_bridge`. Başlangıçta HMAC öz-testi çalışır; ardından `/ping` ile saat farkı ölçülür (±300 sn aşılırsa istekler reddedilir — sunucu saatini NTP ile eşitle).
6. Oyuncu oyunda `/webbagla KOD` yazarak web hesabını bağlar (kod oyuncu panelinde üretilir).

Ayrıntılar: [mta-resource/web_bridge/README.md](mta-resource/web_bridge/README.md).

### 2.7 Production kurulumu (özet)

Nginx + PHP-FPM 8.4 + TLS, Supervisor ile kuyruk işçisi (ve isteğe bağlı Reverb), cron ile `schedule:run`, ayrı veritabanı kullanıcıları (uygulama: DDL/trigger yetkisi yok). Adım adım: [docs/OPERATIONS.md](docs/OPERATIONS.md) ve [`deploy/`](deploy/).

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force          # production'da örnek içerik ve varsayılan admin OLUŞTURMAZ
php artisan mrp:make-super-admin admin@alanadi.com   # ilk süper admin (parola interaktif sorulur)
php artisan storage:link
php artisan optimize
```

### 2.8 Sorun giderme

| Belirti | Çözüm |
|---|---|
| Sayfa stilsiz | `npm run build` (yeni view sonrası da gerekir) |
| `Vite manifest not found` | `npm run build` veya `npm run dev` |
| Görseller görünmüyor | `php artisan storage:link` |
| `.env` değişikliği etkisiz | `php artisan optimize:clear`; `artisan serve` sürecini yeniden başlat |
| Testlerde "Access denied … roleplay_web_test" | Test veritabanını oluştur ve yetki ver (§2.2 adım 4); `pest --parallel` desteklenmez, `composer test` kullan |
| `/admin` 404 | Hesap personel değil; süper admin rolü ver (§3) |
| `/admin` 2FA sayfasına yönleniyor | Personel için 2FA zorunlu; Google Authenticator vb. ile kur |

---

## 3. Varsayılan yönetici hesabı

`APP_ENV=local` iken `php artisan migrate --seed` (veya `php artisan db:seed --class=DefaultAdminSeeder`) şu **süper admin** hesabını oluşturur:

| Alan | Değer |
|---|---|
| Kullanıcı adı | `1uc44s` |
| E-posta | `1uc44s@git.hub` |
| Parola | `lucasisthebest` |
| Rol | Süper Admin (tüm izinler) |
| Giriş adresi | `/login` (kullanıcı adı **veya** e-posta ile) → `/admin` |

- İlk `/admin` girişinde **iki adımlı doğrulama (2FA) kurulumu zorunludur** (personel güvenlik kuralı). Kurulumdaki kurtarma kodlarını sakla.
- Bilgiler `.env` ile değiştirilebilir: `DEFAULT_ADMIN_USERNAME`, `DEFAULT_ADMIN_EMAIL`, `DEFAULT_ADMIN_PASSWORD` (seed'den önce).
- Seeder idempotenttir; hesap zaten varsa yeniden oluşturmaz.

> ⚠️ **Güvenlik:** Bu bilgiler herkese açık depoda yazılıdır. `DefaultAdminSeeder` **production ortamında çalışmayı reddeder**. İnternete açık bir sunucuda bu hesap asla kullanılmamalıdır; orada ilk süper admin `php artisan mrp:make-super-admin {email}` ile, güçlü ve gizli bir parolayla oluşturulur. Yerel ortamı başkalarıyla paylaşırsan önce parolayı değiştir (Panel → Güvenlik).

---

## 4. Komutlar

### 4.1 Geliştirme

| Komut | Ne yapar |
|---|---|
| `composer dev` | Sunucu + kuyruk işçisi + log izleme + Vite (eşzamanlı) |
| `composer test` | Pest (Unit + Feature + Arch). Test veritabanı `roleplay_web_test` |
| `composer lint` / `composer lint:fix` | Laravel Pint (PSR-12 + `declare_strict_types`) |
| `composer analyse` | Larastan seviye 6 |
| `npm run dev` / `npm run build` | Vite geliştirme sunucusu / production derlemesi |
| `php artisan migrate:fresh --seed` | Veritabanını sıfırla ve yeniden seed et (**tüm veri silinir**) |
| `php artisan db:seed --class=ContentSeeder` | Sistem içeriklerini (ana sayfa, yasal sayfalar, kurallar, SSS, Akademi, menü) yeniden yükler |

### 4.2 Uygulama komutları

| Komut | Zamanlama | Görev |
|---|---|---|
| `php artisan mrp:make-super-admin {email}` | elle | Süper admin ata / oluştur (break-glass, parola interaktif) |
| `php artisan mrp:vapid-keys` | elle | Web Push VAPID anahtar çifti üret |
| `php artisan mrp:health-check [--json]` | 5 dk | Başarısız iş, kuyruk gecikmesi, disk, MTA heartbeat, ödeme olayı, oyun komutu alarmları |
| `php artisan content:publish-scheduled` | her dakika | Zamanlanmış içerikleri yayınla |
| `php artisan mrp:moderation-tick` | her dakika | Takılan oyun komutlarını yeniden kuyruğa al, süresi dolan cezaları kapat |
| `php artisan bridge:maintain [--stats]` | 15 dk / 00:10 | Nonce, heartbeat, olay ve kod temizliği; günlük sunucu istatistiği |
| `php artisan mrp:expire-roles` | saatlik | Süreli rol ve branş üyeliklerini bitir |
| `php artisan wallet:reconcile` | 02:00 | Cüzdan mutabakatı |
| `php artisan audit:verify` | 03:30 | Denetim kaydı hash zinciri doğrulaması |
| `php artisan reverb:start` | sürekli (isteğe bağlı) | WebSocket sunucusu |

Zamanlayıcı listesi: `php artisan schedule:list`. Production'da cron: `* * * * * php /var/www/meridyen/artisan schedule:run`.

---

## 5. Marka (ad, logo, sekme ikonu)

**ACP → Marka** (`/admin/branding`, görüntüleme `settings.read`, değiştirme `settings.update` + parola onayı):

- **Site adı** — sekme başlığı, menü, footer, e-posta imzası ve PWA (telefona eklenen uygulama) adı. Kodda `config('app.name')` her istekte bu ayardan gelir (`App\Support\Brand`).
- **Kısa ad** — logo yanındaki yazı ve ana sayfadaki dev arka plan kelimesi.
- **Slogan** — footer ve manifest açıklaması.
- **Logo** — PNG/JPG/WebP, ≤ 2 MB; sunucuda WebP'ye yeniden kodlanır (≤ 640×200). Yüklenmezse varsayılan işaret + kısa ad.
- **Sekme ikonu (favicon)** — PNG/JPG/WebP, ≥ 64×64, ≤ 1 MB; kare kırpılır ve 32/180/192/512 px PNG üretilir.
- **SVG kabul edilmez** (güvenlik). Yeni yüklemede eski dosya silinir; her değişiklik denetim kaydına yazılır.
- CMS metinlerinde `{site}` (tam ad) ve `{site_short}` (kısa ad) yazılabilir; ad değişince metin kendiliğinden güncellenir.
- Sunucu adı (oyun sunucusu kartı) ayrıdır: **ACP → MTA entegrasyonu**. Cüzdan birimi adı: **ACP → Ekonomi**.

---
## 6. Web rotaları (sayfalar ve formlar)

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

**Süper admin:** yerelde varsayılan hesap seed ile oluşur ([§3](#3-varsayılan-yönetici-hesabı)). Production'da veya ek süper admin için:

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

### Faz 9 — Cüzdan, ödeme, iade, mutabakat

Karar durumu: **OD-03** (cüzdan türleri) ve **OD-04** (ödeme sağlayıcısı) verilmediği için ROADMAP'e göre yalnızca `web_credit` (Meridyen Kredisi, MK) ve `FakeProvider` ile geliştirildi. `features.wallet` ve `features.payments` **varsayılan kapalı** (FR-PAY-006: kapalıyken tüm ödeme rotaları 404). `FakeProvider` production'da kullanılamaz (`config/roleplay.php payments.test_providers`).

**Oyuncu paneli:**

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/wallet` | `panel.wallet` | Türlere göre bakiye kartları + son işlemler. F:wallet |
| GET | `/panel/transactions` (`?direction=all\|credit\|debit`) | `panel.transactions` | Yalnızca kendi ledger kayıtları |
| GET | `/panel/top-up` | `panel.top-up` | Paketler. F:payments, doğrulanmış e-posta |
| POST | `/panel/payments/checkout` (`package_id`, `idempotency_key`) | `panel.payments.checkout` | 10/saat. **Tutar sunucuda paketten**; 303 → sağlayıcı |
| GET | `/panel/payments/{ulid}` | `panel.payments.show` | Dönüş sayfası — bakiye **yüklemez**; `pending` iken 5 sn polling |
| GET | `/panel/payments/{ulid}/status` | `panel.payments.status` | JSON `{status, pending}`; başkasının siparişi 404 |
| GET / POST | `/payments/fake/{ulid}` (imzalı URL) | `payments.fake.show/complete` | Yalnızca `PAYMENTS_PROVIDER=fake` ve production dışı; sahibine özel; sonuç gerçek webhook yolundan işlenir |
| GET | `/refund-policy` | `public.legal.refund-policy` | İade politikası (taslak, OD-08) |

**Webhook:** `POST /webhooks/payments/{provider}` (`webhooks.payments`, 300/dk/IP, F:payments). İmza doğrulama (`fake`: `X-Fake-Signature = hex(HMAC-SHA256(gövde, APP_KEY türevi))`) → normalize → `payment_events` INSERT (unique `provider + provider_event_id`) → `ProcessPaymentEvent` job (`critical` kuyruğu). Geçersiz imza: **400 ve kayıt yok**. Tekrar olay: `200 {"received":true,"duplicate":true}`.

**ACP:**

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/economy` | `admin.economy.index` | `wallet.read` — bugün/bu ay giriş-çıkış-gelir, toplam bakiye |
| GET | `/admin/economy/transactions` (`?q=kullanıcı\|ULID`) | `admin.economy.transactions` | `wallet.read` — cursor pagination |
| GET | `/admin/economy/wallets/{id}` | `admin.economy.wallets.show` | `wallet.read` |
| GET / POST | `/admin/economy/adjustments` (`username, direction, amount, reason ≥ 20`) | `admin.economy.adjustments.*` | listeleme `wallet.read`; talep `wallet.adjust.request` + PC |
| POST | `/admin/economy/adjustments/{ulid}/approve` · `/reject` | `admin.economy.adjustments.approve/reject` | `wallet.adjust.approve` (+ PC onayda). Eşik (`wallet.four_eyes_threshold`, 500) üstü → **farklı** onaylayıcı |
| POST | `/admin/economy/transactions/{ulid}/reversal-request` (`reason`) | `admin.economy.reversal-request` | `wallet.adjust.request` + PC; dört göz akışına girer, kayıt bir kez ters çevrilir |
| — | `/admin/economy/packages…` | `admin.economy.packages.*` | `payments.packages.manage` (CMS kaynak deseni; fiyat kuruş) |
| GET | `/admin/payments` · `/admin/payments/{ulid}` | `admin.payments.index/show` | `payments.read` — webhook geçmişi ve iadeler dahil |
| POST | `/admin/payments/{ulid}/refund-request` (`amount_minor`, `reason`) | `admin.payments.refund-request` | `payments.refund.request` + PC |
| POST | `/admin/refunds/{ulid}/approve` · `/reject` | `admin.refunds.*` | `payments.refund.approve` (+ PC); talep eden onaylayamaz |
| GET / POST | `/admin/payments/reconciliation`, `/run` | `admin.payments.reconciliation(.run)` | `payments.reconcile` |

**Defter kuralları (`App\Services\Wallet\LedgerService`):** tek yazım noktası (mimari testi: `tests/Arch/ArchitectureTest.php`); transaction + `SELECT … FOR UPDATE`; `idempotency_key` unique (aynı anahtar → mevcut kayıt); debit bakiyeyi negatife düşüremez (servis + `CHECK`); ledger append-only (trigger); düzeltme yalnızca ters kayıt (`uq_wt_reversal_of`).

**İade:** onayda iade edilecek kredi cüzdanda yoksa sağlayıcı çağrılmaz → `requires_review`. Başarılıysa ledger debit (`refund:{ulid}`), sipariş `refunded|partially_refunded`.

**Zamanlayıcı:** `wallet:reconcile` (02:00) — her cüzdan için `SUM(credit) − SUM(debit) = balance` ve son kayıt `balance_after = balance`; fark varsa kritik audit `wallet.reconciliation_mismatch` + `payments.reconcile` yetkililerine panel bildirimi; komut çıkış kodu 1.

**Gerçek sağlayıcı (OD-04 sonrası):** `App\Services\Payments\PaymentProvider` arayüzünü (`createCheckout`, `verifyWebhook`, `normalizeEvent`, `getPaymentStatus`, `refundPayment`) uygulayan adapter + `PaymentManager::PROVIDERS` eşlemesi + `.env` anahtarları.

### Faz 10 — Gerçek zaman, Web Push, Discord, Akademi sınavı, KVKK dökümü

> Düzeltme (Faz 10 tarayıcı kontrolünde bulundu): `POST /user/confirm-password` 500 veriyordu (giriş alanı `login` sanal olduğu için Fortify varsayılan doğrulaması `$user->login` okuyordu). `Fortify::confirmPasswordsUsing` ile doğrudan hash karşılaştırmasına alındı; regresyon testi `PushAndExportTest`.

Tümü **varsayılan kapalı**: `features.push`, `features.academy_quiz`, `discord.announcements`, `discord.ops_alerts` (ACP → Ayarlar); Reverb yalnızca `BROADCAST_CONNECTION=reverb` iken. WhatsApp kanalı **atlandı** (OD-05 kararı yok, R3).

**Oyuncu paneli:**

| Yöntem | URL | Ad | Not |
|---|---|---|---|
| GET | `/panel/academy/topics/{id}/quiz` | `panel.academy.quiz` | F:academy_quiz. Doğru seçenekler yalnızca sınav geçildikten sonra gösterilir |
| POST | `/panel/academy/topics/{id}/quiz` (`answers[soru_id]=seçenek_id`) | `panel.academy.quiz.submit` | 10/saat. Deneme sınırı + başarısızlık sonrası bekleme; geçerse konu tamamlanır. Aktif sınavlı konu "Okudum" ile tamamlanamaz |
| POST | `/panel/push/subscriptions` (JSON `{endpoint, keys:{p256dh, auth}, contentEncoding}`) | `panel.push.subscribe` | F:push, 20/saat. Yalnızca HTTPS endpoint, kullanıcı başına 10 |
| POST | `/panel/push/subscriptions/delete` (JSON `{endpoint}`) | `panel.push.unsubscribe` | Yalnızca kendi aboneliği |
| POST | `/panel/account/export` | `panel.account.export` | Parola onayı, 3/saat. JSON dosyası (`Cache-Control: no-store`); güvenlik olayı `data_exported` |
| POST | `/broadcasting/auth` (`socket_id`, `channel_name`) | — | Yalnızca Reverb açıkken anlamlı; web oturumu ile kanal yetkisi |

**Gerçek zaman kanalları (`routes/channels.php`):** `private-user.{id}` (yalnızca kendisi — panel bildirimleri, rozet anında artar), `private-support.ticket.{ulid}` (talep sahibi veya kapsamlı personel — `support.message` olayı, "yeni mesaj — yenile" bandı), `private-staff.branch.{id}` (branş üyesi veya `support.all_branches` — `support.queue`). Yayınlar yalnızca kimlik alanı taşır; mesaj gövdesi ve iç not **asla** yayınlanmaz. İstemci ayarları `<meta name="mrp-realtime">` (public anahtar, host, port, şema) — secret istemciye gitmez; CSP `connect-src` Reverb host'unu yalnızca etkinken ekler.

**Web Push:** `php artisan mrp:vapid-keys` → `.env` `VAPID_*` → `features.push`. Ayarlar sayfasında "Bu cihazda aç". Tercih matrisi (`NotificationPreferences::MATRIX`): destek (e-posta + push), duyurular (e-posta), başvurular / cezalar / ödemeler (push). Yük yalnızca başlık, özet, URL; 404/410 veya 5 ardışık hata → abonelik silinir. Service worker `sw.js` (`mrp-v2`) push + tıklama (yalnızca aynı origin).

**Discord:** `DISCORD_WEBHOOK_ANNOUNCEMENTS` / `DISCORD_WEBHOOK_OPS` (`.env`, yalnızca `https://discord.com/api/webhooks/…`). Duyuruda "Discord" kanalı seçilirse ve hedef `all` ise yayında **bir kez** gönderilir. Ops: `mrp:health-check` kritikleri, köprü imza alarmı, mutabakat farkı (30 dk tekilleştirme). Metin markdown/`@everyone` arındırılır, `allowed_mentions: {parse: []}`, ≤ 2000 karakter. Her gönderim `message_dispatches` (kanal `discord_webhook`).

**ACP:**

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| — | `/admin/academy-quizzes…` | `admin.academy-quizzes.*` | `academy.manage` (CMS kaynak deseni: konu (tekil), başlık, geçme eşiği %, deneme sınırı (0 = sınırsız), bekleme dk, aktif) |
| GET / POST | `/admin/academy-quizzes/{id}/questions` (`body`, `options[0..5]`, `correct`) | `admin.academy-quizzes.questions(.store)` | `academy.manage` — 2–6 seçenek, tam bir doğru |
| POST | `/admin/academy-quizzes/{id}/questions/{question}/delete` | `admin.academy-quizzes.questions.destroy` | `academy.manage` |

**Komutlar:** `php artisan mrp:vapid-keys` (VAPID anahtar çifti, yalnızca ekrana; Windows/XAMPP'ta EC anahtar üretimi için `OPENSSL_CONF=C:
mpp\apache\conf\openssl.cnf` gerekir), `php artisan reverb:start` (production: `deploy/supervisor/meridyen-reverb.conf`).

### Tasarım v2 ve marka yönetimi (2026-11-13)

Public site grandroleplay.net'in **yapısal** dilini izleyen yeni bir görsel dile geçti ("Gün Batımı Bulvarı", docs/DESIGN.md v2). Referans sitenin rengi, metinleri ve görselleri kullanılmadı. Değişenler: gün batımı turuncusu vurgu, yüzen cam hap menü, dev soluk arka plan kelimeleri, iki tonlu ağır başlık, kodla çizilmiş şehir ufku, cam kartlar, mavi (yasal) / kırmızı (yeraltı) seçim panelleri, ışımalı çağrı bandı, dev marka adlı footer ve bölüm belirme animasyonu (azaltılmış harekette kapalı, JS yoksa içerik görünür). Token'lar panel ve ACP'ye de yansır; kontrast `tests/Unit/Design/ContrastTest.php` ile denetlenir.

**Marka (ACP → Marka):** site adı, kısa ad, slogan, logo ve sekme ikonu. Kodda marka adı sabit değildir: `config('app.name')` açılışta `site.name` ayarından gelir (`App\Support\Brand`). CMS metinlerinde `{site}` / `{site_short}` yer tutucuları güncel adla doldurulur.

| Yöntem | URL | Ad | Yetki |
|---|---|---|---|
| GET | `/admin/branding` | `admin.branding` | `settings.read` |
| POST | `/admin/branding` (`name`, `short_name`, `tagline`, `logo`, `icon`) | `admin.branding.update` | `settings.update` + PC. PNG/JPG/WebP; logo ≤ 2 MB → WebP ≤ 640×200; ikon ≥ 64×64, ≤ 1 MB → PNG 32/180/192/512. **SVG kabul edilmez** |
| POST | `/admin/branding/{logo\|icon}/remove` | `admin.branding.remove` | `settings.update` + PC |
| GET | `/manifest.webmanifest` | `pwa.manifest` | Herkese açık; ad ve ikonlar marka ayarından (eski statik `public/manifest.webmanifest` kaldırıldı) |

Yeni ayarlar: `site.logo_path`, `site.icon_set` (yalnızca Marka ekranından; genel ayar formu bu anahtarları göstermez ve kabul etmez). `site.logo_media_id` kaldırıldı. Dosyalar `storage/app/public/branding/` altında; eski görsel yeni yüklemede silinir.

---

## 7. API dokümantasyonu

Temel adres: `{APP_URL}` (yerelde `http://127.0.0.1:8000`). Tüm JSON yanıtları UTF-8'dir ve her yanıtta `X-Request-Id` başlığı bulunur (destek / log eşleştirme için).

### 7.1 Genel kurallar

**Başarılı yanıt:**
```json
{ "success": true, "data": { "…": "…" }, "requestId": "01JC…" }
```
**Hata yanıtı:**
```json
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "…", "details": [ { "field": "players", "message": "…" } ] }, "requestId": "01JC…" }
```

| HTTP | `error.code` | Anlamı |
|---|---|---|
| 400 | `BAD_REQUEST` | Bozuk istek / geçersiz webhook imzası |
| 401 | `UNAUTHENTICATED` · `SIGNATURE_INVALID` | Oturum yok / köprü imzası, anahtar veya zaman damgası geçersiz |
| 403 | `FORBIDDEN` | Yetki veya köprü anahtarı kapsamı yok |
| 404 | `NOT_FOUND` | Kayıt yok / başkasına ait / özellik kapalı |
| 409 | `CONFLICT` · `REPLAY_DETECTED` · `ACCOUNT_ALREADY_LINKED` | Çakışma / aynı nonce tekrar kullanıldı |
| 413 | `PAYLOAD_TOO_LARGE` | Gövde > 64 KB (köprü) |
| 419 | `SESSION_EXPIRED` | CSRF belirteci süresi doldu (oturumlu uçlar) |
| 422 | `VALIDATION_ERROR` · `LINK_CODE_INVALID` | Alan doğrulaması |
| 429 | `RATE_LIMITED` | Hız sınırı (`Retry-After` başlığına bak) |
| 503 | `SERVICE_UNAVAILABLE` | Bakım modu |

**Hız sınırları:** public API 60/dk/IP · köprü 120/dk/anahtar (heartbeat 6/dk) · webhook 300/dk/IP · panel yoklama uçları 30/dk/kullanıcı. `X-Forwarded-For` sahteciliği sınırı aşmaz.

### 7.2 Public API (kimlik doğrulama yok)

| Yöntem | URL | Açıklama |
|---|---|---|
| GET | `/api/v1/public/server-status` | Birincil sunucunun durumu. `?server=main` ile sunucu seçilir. 15 sn önbellek + `ETag` |

```bash
curl -s http://127.0.0.1:8000/api/v1/public/server-status -H "Accept: application/json"
# Değişmediyse 304 almak için önceki yanıttaki ETag'i gönder:
curl -s -i http://127.0.0.1:8000/api/v1/public/server-status -H 'If-None-Match: "<önceki ETag>"'
```
```json
{
  "success": true,
  "data": {
    "server": "main", "status": "online", "players": 87, "max_players": 300,
    "last_heartbeat_at": "2026-11-11T18:42:10Z", "freshness_seconds": 12,
    "connect": { "host": "play.example.com", "port": 22003, "uri": "mtasa://play.example.com:22003" }
  }
}
```
`status`: `online | stale | offline | maintenance`.

Makine tarafından okunabilir diğer public dosyalar: `GET /sitemap.xml`, `GET /robots.txt`, `GET /feed.xml` (haber RSS), `GET /manifest.webmanifest` (PWA, marka ayarından), `GET /up` (sağlık kontrolü: 200).

### 7.3 MTA köprü API'si (`/api/v1/game/*`, HMAC imzalı)

Oyun sunucusu (Lua `web_bridge` kaynağı) ↔ web. Her istek ACP'de oluşturulan **anahtar** (`key_id` + `secret`) ile imzalanır. Tarayıcıdan çağrılmaz.

**İmza:**
```text
canonical = METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + NONCE + "\n" + hex(sha256(BODY))
signature = hex(hmac_sha256(secret, canonical))
```
- `METHOD` büyük harf (`GET`, `POST`); `PATH` sorgu dizesi olmadan istek yolu (`/api/v1/game/heartbeat`); GET'te `BODY` boş string.
- `TIMESTAMP` unix saniye (UTC), sunucu saatiyle farkı ≤ 300 sn olmalı. `NONCE` 32 bayt rastgele (64 hex), **her istekte yeni** (tekrarı `409 REPLAY_DETECTED`).

**Başlıklar:**

| Başlık | Değer |
|---|---|
| `X-Bridge-Key` | Anahtar kimliği (`key_id`) |
| `X-Bridge-Timestamp` | Unix saniye |
| `X-Bridge-Nonce` | 64 hex |
| `X-Bridge-Signature` | 64 hex HMAC-SHA256 |
| `Content-Type` / `Accept` | `application/json` |

**curl ile imzalı istek (Bash + OpenSSL):**
```bash
BASE=http://127.0.0.1:8000
KEY="mrp_live_xxxxxxxxxxxxxxxx"
SECRET="<64-hex-secret>"
URI=/api/v1/game/heartbeat
BODY='{"players":12,"max_players":300,"server_name":"Ana Sunucu","resource_version":"1.0.0","uptime_sec":3600}'

TS=$(date +%s)
NONCE=$(openssl rand -hex 32)
BODY_HASH=$(printf '%s' "$BODY" | openssl dgst -sha256 -hex | awk '{print $NF}')
SIG=$(printf 'POST\n%s\n%s\n%s\n%s' "$URI" "$TS" "$NONCE" "$BODY_HASH" | openssl dgst -sha256 -hmac "$SECRET" -hex | awk '{print $NF}')

curl -s -X POST "$BASE$URI" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-Bridge-Key: $KEY" -H "X-Bridge-Timestamp: $TS" \
  -H "X-Bridge-Nonce: $NONCE" -H "X-Bridge-Signature: $SIG" \
  --data "$BODY"
```

**PHP ile imzalı istek:**
```php
$body = json_encode(['players' => 12, 'max_players' => 300]);
$ts = (string) time();
$nonce = bin2hex(random_bytes(32));
$canonical = "POST\n/api/v1/game/heartbeat\n{$ts}\n{$nonce}\n".hash('sha256', $body);
$headers = [
    'Content-Type: application/json', 'Accept: application/json',
    'X-Bridge-Key: '.$keyId, "X-Bridge-Timestamp: {$ts}", "X-Bridge-Nonce: {$nonce}",
    'X-Bridge-Signature: '.hash_hmac('sha256', $canonical, $secret),
];
$ch = curl_init('http://127.0.0.1:8000/api/v1/game/heartbeat');
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true]);
echo curl_exec($ch);
```

**Uçlar** (anahtarın **kapsamı** — `heartbeat`, `events`, `account_link`, `commands` — ACP'de anahtar oluşturulurken seçilir):

| Yöntem | URL | Kapsam | Gövde (özet) | Yanıt `data` |
|---|---|---|---|---|
| GET | `/api/v1/game/ping` | herhangi | — | `{"server":"main","time":1762867200}` (saat farkı teşhisi) |
| POST | `/api/v1/game/heartbeat` | `heartbeat` | `{"players","max_players","server_name","game_mode","map","resource_version","uptime_sec"}` | `{"status":"online","maintenance":false}` |
| POST | `/api/v1/game/player/connected` | `events` | `{"event_id","occurred_at","account":{"ref","name"}}` | `{"duplicate":false}` |
| POST | `/api/v1/game/player/disconnected` | `events` | yukarıdaki + `"session_seconds"` | `{"duplicate":false}` |
| POST | `/api/v1/game/accounts/sync` | `events` | `{"event_id","accounts":[{"ref","name","last_seen_at"}]}` (≤ 100) | Yalnızca web'e bağlı mevcut hesaplar güncellenir |
| POST | `/api/v1/game/characters/sync` | `events` | `{"event_id","account_ref","characters":[{"ref","name","status","level","playtime_minutes","faction_key","job_key","public_profile","created_at","last_seen_at"}]}` | Bağlı değilse `{"ignored":true}` |
| POST | `/api/v1/game/account-link/verify` | `account_link` | `{"event_id","code":"K7Q4M2XD","account":{"ref","name"}}` | `{"linked":true,"web_username":"…"}` · `422 LINK_CODE_INVALID` · `409 ACCOUNT_ALREADY_LINKED` |
| POST | `/api/v1/game/device-link/verify` | `account_link` | `{"event_id","code","account":{"ref"},"serial":"<32 hex>"}` | `{"status":"pending_approval"}` (serial asla loglanmaz/döndürülmez) |
| GET | `/api/v1/game/commands/pending` | `commands` | — | `[{"id","type":"player.kick","payload":{"account_ref","reason"},"expires_at"}]` |
| POST | `/api/v1/game/commands/result` | `commands` | `{"id","status":"succeeded"\|"failed","detail":{"error":"player_offline"}}` | `{"id","status","duplicate":false}` |

- `event_id` (UUID) idempotency anahtarıdır: aynı olay iki kez gelirse `{"duplicate":true}` döner, işlem tekrarlanmaz.
- Komut türleri (allowlist): `player.warn`, `player.kick`, `player.mute`, `player.jail`, `player.ban` (`minutes`: sayı veya `null` = kalıcı), `player.unban`, `server.announce`, `account.link_revoked`.
- Ayrıntılı sözleşmeler: [docs/ROUTES.md §7.2](docs/ROUTES.md), protokol: [docs/ARCHITECTURE.md §7](docs/ARCHITECTURE.md).

### 7.4 Ödeme webhook'u

| Yöntem | URL | Açıklama |
|---|---|---|
| POST | `/webhooks/payments/{provider}` | Sağlayıcı imzası doğrulanır → `payment_events` gelen kutusu → kuyruk işi (`critical`). Aynı olay ikinci kez gelirse `{"received":true,"duplicate":true}`, kredi bir kez yüklenir |

Şu an yalnızca test sağlayıcısı `fake` vardır (`features.payments` açık ve production dışı). İmza: `X-Fake-Signature = hex(HMAC-SHA256(gövde, HMAC-SHA256("mrp.fake-payments.v1", APP_KEY)))`.

```bash
BODY='{"id":"evt_test_1","type":"charge.succeeded","order_id":"fake_<sipariş-ulid>","amount":4999,"currency":"TRY"}'
SECRET=$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo hash_hmac("sha256", "mrp.fake-payments.v1", config("app.key"));')
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | awk '{print $NF}')
curl -s -X POST http://127.0.0.1:8000/webhooks/payments/fake \
  -H "Content-Type: application/json" -H "X-Fake-Signature: $SIG" --data "$BODY"
```
`type`: `charge.succeeded | charge.failed`. Tutar sunucudaki siparişle eşleşmezse kredi yüklenmez (`amount_mismatch`). Geçersiz imza: `400`, kayıt oluşmaz. Normalde bu webhook'u sahte ödeme ekranı (`/payments/fake/{ulid}`) kendisi gönderir.

### 7.5 Oturumlu JSON uçları (tarayıcı, panel)

Bu uçlar **web oturumu çerezi** ile çalışır (API anahtarı yoktur). POST isteklerinde `X-CSRF-TOKEN` başlığı (sayfadaki `<meta name="csrf-token">`) gerekir; çerezler `credentials: 'same-origin'` ile gönderilir.

| Yöntem | URL | Ad | Yanıt / gövde |
|---|---|---|---|
| GET | `/panel/notifications/count` | `panel.notifications.count` | `{"unread":3}` + `ETag` (değişmediyse `304`). 30/dk |
| GET | `/panel/game-accounts/link-status` | `panel.game-accounts.link-status` | `{"state":"none\|pending\|linked\|expired","expires_in":540,"account_name":"…"}` |
| GET | `/panel/payments/{ulid}/status` | `panel.payments.status` | `{"status":"pending","pending":true}` (yalnızca kendi siparişi) |
| POST | `/panel/push/subscriptions` | `panel.push.subscribe` | Gövde: `{"endpoint":"https://…","keys":{"p256dh":"…","auth":"…"},"contentEncoding":"aes128gcm"}` → `201` |
| POST | `/panel/push/subscriptions/delete` | `panel.push.unsubscribe` | Gövde: `{"endpoint":"https://…"}` → `{"removed":true}` |
| POST | `/panel/account/export` | `panel.account.export` | KVKK veri dökümü — JSON **dosyası** (parola onayı gerekir, 3/saat) |
| GET | `/admin/support/counters` | `admin.support.counters` | `{"unassigned":2,"mine":1,"waiting_for_staff":4}` (`support.read`) |
| POST | `/broadcasting/auth` | — | Reverb özel kanal yetkisi: form `socket_id`, `channel_name` (`private-user.{id}`, `private-support.ticket.{ulid}`, `private-staff.branch.{id}`) |

Tarayıcıdan örnek:
```js
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const res = await fetch('/panel/push/subscriptions', {
  method: 'POST',
  credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
  body: JSON.stringify({ endpoint, keys: { p256dh, auth }, contentEncoding: 'aes128gcm' }),
});
```

Formlu sayfa uçları (giriş, kayıt, destek, başvuru, ACP işlemleri) klasik HTML form POST'larıdır (`_token` alanı ile) — tam liste [§6](#6-web-rotaları-sayfalar-ve-formlar).

### 7.6 Gerçek zaman kanalları (Reverb, isteğe bağlı)

`BROADCAST_CONNECTION=reverb` iken istemci sayfadaki `<meta name="mrp-realtime">` etiketinden (public anahtar, host, port, şema) bağlanır. Olaylar yalnızca kimlik alanı taşır, mesaj gövdesi taşımaz.

| Kanal | Kim dinler | Olay | Yük |
|---|---|---|---|
| `private-user.{id}` | Yalnızca o kullanıcı | Laravel bildirim yayını | Bildirim başlığı / URL |
| `private-support.ticket.{ulid}` | Talep sahibi + kapsamlı personel | `.support.message` | `{"ticket":"…","author":"player\|staff"}` |
| `private-staff.branch.{id}` | Branş üyeleri / `support.all_branches` | `.support.queue` | `{"branch":3}` |

---
## 8. Veritabanı

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
| `2026_11_13_090000_create_wallet_payment_tables` | `wallet_types`, `wallets` (+ `CHECK balance >= 0`), `wallet_transactions` (append-only trigger + CHECK'ler), `payment_packages`, `payment_orders` (+ status CHECK), `payment_events`, `refunds`, `wallet_adjustment_requests`, `wallet_reconciliation_runs` | 9 |
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
**Ekonomi ve ödeme tabloları (Faz 9):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `wallet_types` | `key` (unique; `web_credit`), `name`, `unit_label` (`MK`), `is_purchasable`, `is_active`, `sort` | Seed: Meridyen Kredisi |
| `wallets` | `user_id` + `wallet_type_id` (unique), `balance` BIGINT (`CHECK >= 0`), `version` (her post'ta +1), `status` (`active\|frozen`) | Yalnızca LedgerService yazar |
| `wallet_transactions` | `ulid`, `wallet_id`, `direction` (`credit\|debit`, CHECK), `amount` (`> 0`), `balance_before`, `balance_after` (`>= 0`), `reason` (`topup\|purchase\|admin_adjustment\|refund\|reversal\|promo\|game_sync`), `source_type/id`, `reversal_of_id` (unique), `idempotency_key` (unique), `description`, `created_by`, `created_at` | **Append-only** (UPDATE/DELETE trigger) |
| `payment_packages` | `wallet_type_id`, `name`, `credit_amount`, `bonus_amount`, `price_minor` (kuruş, `> 0`), `currency` (`TRY`), `is_active`, `sort` | Seed: Başlangıç 100 MK / 49,99 · Standart 250+25 / 119,99 · Destekçi 600+100 / 249,99 |
| `payment_orders` | `ulid`, `order_number` (`MRP-YYYYMMDD-XXXX`), `user_id`, `wallet_id`, `payment_package_id`, `provider`, `provider_order_id`, `amount_minor`, `currency`, `credit_amount` (anlık kopya), `status` (`created\|pending\|requires_action\|succeeded\|failed\|cancelled\|refunded\|partially_refunded\|disputed`), `refunded_minor`, `idempotency_key` (unique), `failure_reason`, `expires_at`, `paid_at` | unique (`provider`,`provider_order_id`) |
| `payment_events` | `provider`, `provider_event_id` (unique ikili), `payment_order_id`, `event_type` (`payment.succeeded\|payment.failed…`), `signature_valid`, `payload` (temizlenmiş), `status` (`received\|processed\|ignored\|failed`), `error` (`amount_mismatch`, `order_not_found`…), `received_at`, `processed_at` | Webhook gelen kutusu |
| `refunds` | `ulid`, `payment_order_id`, `amount_minor`, `reason`, `status` (`requested\|approved\|processing\|succeeded\|failed\|rejected\|requires_review`), `provider_refund_id`, `wallet_transaction_id`, `requested_by`, `approved_by` (≠ talep eden) | |
| `wallet_adjustment_requests` | `ulid`, `wallet_id`, `direction`, `amount`, `reason` (≥ 20), `status` (`pending\|approved\|rejected\|applied\|cancelled`), `requires_second_approval`, `reversal_of_id`, `requested_by`, `reviewed_by`, `review_note`, `wallet_transaction_id` | Dört göz |
| `wallet_reconciliation_runs` | `ulid`, `triggered_by`, `wallets_checked`, `mismatches`, `total_balance`, `ledger_net`, `details` JSON, `started_at`, `finished_at` | FR-WAL-007 raporu |
**Push ve Akademi sınavı tabloları (Faz 10):**

| Tablo | Önemli kolonlar | Not |
|---|---|---|
| `push_subscriptions` | `user_id` (CASCADE), `endpoint` TEXT, `endpoint_hash` CHAR(64) (unique), `public_key`, `auth_token` (**şifreli**), `content_encoding` (`aes128gcm\|aesgcm`), `user_agent_summary`, `failures`, `last_used_at` | Hesap anonimleştirmesinde silinir |
| `academy_quizzes` | `academy_topic_id` (**unique**, CASCADE), `title`, `pass_percent` (70), `max_attempts` (3; 0 = sınırsız), `cooldown_minutes` (60), `is_active` | Konu başına tek sınav |
| `academy_questions` | `academy_quiz_id` (CASCADE), `body` (≤ 500), `sort` | |
| `academy_options` | `academy_question_id` (CASCADE), `body` (≤ 255), `is_correct`, `sort` | `is_correct` oyuncuya geçmeden gönderilmez |
| `academy_attempts` | `ulid`, `academy_quiz_id`, `user_id`, `score_percent`, `passed`, `answers` JSON (`{soru_id: seçenek_id}`), `created_at`; index (`user_id`,`academy_quiz_id`,`created_at`) | Geçen deneme `academy_progress` yazar |

`user_notification_preferences.channel` artık `push` da alır; `user_security_events.event` için `data_exported` eklendi.

**Seed sırası:** `ServerSeeder` → `AccessControlSeeder` (izinler, 9 sistem rolü + matris, 6 branş, 5 destek kategorisi) → `MessageTemplateSeeder` (sistem e-posta şablonları) → `ContentSeeder` → `EconomySeeder` (web_credit + 3 paket) → (`local` ise) `DemoSeeder` + `DefaultAdminSeeder` (varsayılan süper admin, bkz. [§3](#3-varsayılan-yönetici-hesabı)). Production'da ilk süper admin `php artisan mrp:make-super-admin {email}` ile oluşturulur.

---

---

## 9. Operasyon ve production

Ayrıntı: [docs/OPERATIONS.md](docs/OPERATIONS.md). Örnek production dosyaları: [`deploy/`](deploy/) — `nginx/meridyen.conf` (Faz 10: `/app/` wss proxy), `supervisor/meridyen-worker.conf`, `supervisor/meridyen-reverb.conf` (Faz 10), `cron/meridyen`, `backup.sh` (şifreli günlük döküm + dosyalar).

| Komut | Zamanlama | Görev |
|---|---|---|
| `php artisan mrp:health-check [--json]` | 5 dk | Başarısız iş (24 sa), kuyruk gecikmesi > 10 dk, disk < %10, MTA heartbeat > 5 dk, **R2:** işlenmemiş ödeme olayı > 15 dk (kritik), başarısız oyun komutu (24 sa) → `security` log (+ `discord.ops_alerts` açıksa Discord); alarm varsa çıkış kodu 1 |
| `php artisan audit:verify` | 03:30 | Audit hash zinciri |
| `php artisan mrp:make-super-admin <kullanıcı>` | elle | Break-glass |

**Yayın kapısı testleri:** `tests/Feature/Security/ReleaseGateTest.php` (R1: SQL injection, session fixation, `X-Forwarded-For` ile hız sınırı atlatma, yansıyan XSS, sayfa başına sorgu sınırı, sağlık kontrolü) ve `ReleaseGateR2Test.php` (R2: **tüm panel rotalarında genel IDOR taraması** — başka oyuncunun başvuru/ceza/cihaz/ödeme/karakter/talep/ek/bildirim/oturum kaynağı → 403/404 ve veri değişmez; R2 ACP aramalarında SQL injection + yansıyan XSS; KVKK dökümü hız sınırı `X-Forwarded-For` ile atlatılamaz; sahte imzalı / bilinmeyen sağlayıcılı webhook kayıt oluşturmaz; R2 panel/ACP sayfalarında sorgu sınırı; `.env.example` gizli değer içermez; R2 sağlık alarmları). `composer audit` ve `npm audit` 0 açık.

**Veritabanı kullanıcıları (production):** `meridyen_app` (yalnızca `SELECT/INSERT/UPDATE/DELETE`), `meridyen_migrate` (DDL + trigger, yalnızca dağıtımda), `meridyen_backup` (salt okunur + trigger dökümü). Uygulama kullanıcısı audit/consent trigger'larını değiştiremez.

---

## 10. Mimari notlar

- **Güvenlik başlıkları (global):** nonce'lu CSP (`unsafe-inline` / `unsafe-eval` yok), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `X-Frame-Options: DENY`, `X-Request-Id`. Production + HTTPS'te HSTS.
- **CSRF:** Laravel token + `VerifyOrigin` (cross-site `Sec-Fetch-Site` / `Origin` → 419). İstisna yalnızca `webhooks/*`.
- **Cache:** Laravel 13 cache'i güvenlik gereği nesne `unserialize` etmez. Cache'e **yalnızca düz dizi/skaler** yazılır (bkz. `PublicLayoutComposer`, `ServerStatusResolver`).
- **Markdown:** CommonMark (`html_input: escape`) + Symfony HtmlSanitizer allowlist → `SafeHtml` (Htmlable). Blade'de `{!! !!}` kullanılmaz (mimari testiyle zorlanır).
- **Tema:** `mrp_theme` / `mrp_effects` cookie'leri sunucuda okunur ve `<html data-theme data-effects>` olarak render edilir (flaş yok). `prefers-reduced-motion` ve düşük efekt modu animasyonları kapatır.
- **Tasarım sistemi:** [docs/DESIGN.md](docs/DESIGN.md) — "Gece Haritası". Token'lar `resources/css/app.css`, bileşenler `resources/views/components/{ui,brand}`.

---

## 11. Faz durumu

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
| 9 | Ekonomi ve ödeme | ✅ FakeProvider ile (OD-03/OD-04 bekliyor; `features.wallet/payments` varsayılan kapalı) |
| 10 | Gelişmiş kanallar (Reverb, Web Push, Discord, Akademi sınavı, KVKK dökümü) | ✅ özellikler varsayılan kapalı; WhatsApp atlandı (OD-05, R3) |
| 11 | Sertleştirme ve yayın | 🟡 R1 + R2 yerel kısım ✅ (güvenlik testleri, audit, yedek/geri yükleme testi, izleme, operasyon dokümanı); production kurulumu, hukuk ve marka onayı açık |
