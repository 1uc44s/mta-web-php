# OPERATIONS — Dağıtım, yedekleme, izleme ve acil durum

> Faz 11 yayın kapısının (ROADMAP 11.4–11.10) operasyon tarafı. Örnek yapılandırma dosyaları [`deploy/`](../deploy/) altındadır. Buradaki komutlar Linux production sunucusu içindir (geliştirme ortamı Windows + XAMPP, bkz. kök README §1).

## 1. Production topolojisi

| Bileşen | Seçim | Not |
|---|---|---|
| Web sunucusu | Nginx (TLS 1.2+, HTTP/2) | `deploy/nginx/meridyen.conf`. HSTS uygulama tarafından da gönderilir (`SecurityHeaders`, yalnızca HTTPS + production) |
| PHP | PHP-FPM 8.4, OPcache açık, `expose_php=Off` | Eklentiler: `intl, gd, zip, sodium, pdo_mysql, mbstring, bcmath` |
| Veritabanı | MariaDB 10.11, `utf8mb4_unicode_ci` | Ayrı kullanıcılar (§1.1) |
| Kuyruk | `database` sürücüsü + Supervisor | `deploy/supervisor/meridyen-worker.conf` |
| Zamanlayıcı | cron → `php artisan schedule:run` her dakika | `deploy/cron/meridyen` |
| MTA | `mta-resource/web_bridge` | [mta-resource/web_bridge/README.md](../mta-resource/web_bridge/README.md) |

### 1.1 Veritabanı kullanıcıları (en az yetki)

```sql
-- Uygulama: yalnızca veri işlemleri (DDL ve trigger yetkisi YOK → audit/consent trigger'ları uygulama tarafından değiştirilemez)
CREATE USER 'meridyen_app'@'127.0.0.1' IDENTIFIED BY '<güçlü-parola>';
GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES ON meridyen.* TO 'meridyen_app'@'127.0.0.1';

-- Migration: yalnızca dağıtım sırasında kullanılır (DDL + trigger)
CREATE USER 'meridyen_migrate'@'127.0.0.1' IDENTIFIED BY '<farklı-güçlü-parola>';
GRANT ALL PRIVILEGES ON meridyen.* TO 'meridyen_migrate'@'127.0.0.1';

-- Yedek: salt okunur + trigger/rutin dökümü
CREATE USER 'meridyen_backup'@'127.0.0.1' IDENTIFIED BY '<farklı-güçlü-parola>';
GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT ON meridyen.* TO 'meridyen_backup'@'127.0.0.1';
```

`GET_LOCK` (audit zinciri) ek yetki gerektirmez. `audit_logs` ve `consents` UPDATE/DELETE'i trigger ile engellenir; trigger'ları yalnızca migration kullanıcısı değiştirebilir.

### 1.2 Ortam değişkenleri (production)

`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `SESSION_COOKIE=__Host-meridyen_session` (`__Host-` öneki: Secure + Path=/ + Domain yok), `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`, `QUEUE_CONNECTION=database`, `MAIL_*` gerçek SMTP, `BRIDGE_SERIAL_PEPPER` (32+ rastgele bayt). `.env` izni `640`, sahibi dağıtım kullanıcısı, grubu `www-data`; **repoda asla bulunmaz** (`.gitignore`).

## 2. Dağıtım

```bash
cd /var/www/meridyen
php artisan down --retry=30                 # yalnızca şema kırıcı migration varsa
git fetch --tags && git checkout <sürüm-etiketi>
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
DB_USERNAME=meridyen_migrate DB_PASSWORD="$(cat /etc/meridyen/migrate.pass)" php artisan migrate --force
php artisan optimize                        # config/route/view/event cache
php artisan queue:restart
php artisan up
php artisan mrp:health-check
```

**Doğrulama:** `/up` 200; `curl -I https://…` güvenlik başlıkları (CSP, HSTS, XFO, nosniff); ACP → Sistem sağlığı; MTA sunucusu `online`.

## 3. Geri alma

1. `git checkout <önceki-etiket>` → `composer install --no-dev -o` → `npm ci && npm run build` → `php artisan optimize` → `php artisan queue:restart`.
2. Sürüm migration içeriyorsa önce migration kullanıcısıyla `php artisan migrate:rollback --step=<n> --force` (her migration'ın `down()` yöntemi vardır). Veri kaybı riski olan rollback yerine §4 geri yüklemesi tercih edilir.
3. Geri alma tarih ve nedeniyle TODO.md'ye yazılır.

## 4. Yedekleme ve geri yükleme

- **Zamanlama:** günlük 03:00 tam döküm (`deploy/backup.sh`), 30 gün günlük + 12 ay aylık saklama. Dosyalar şifrelenir (`age`) ve sunucu dışına (S3 uyumlu depolama) kopyalanır. `storage/app/private` (destek ekleri) ve `storage/app/public` günlük `rsync`.
- **Döküm biçimi:** `mariadb-dump --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4`. Trigger'lar dökümde olmalıdır (audit append-only koruması).
- **Geri yükleme testi (her sürüm öncesi zorunlu):** dökümü boş bir veritabanına yükle, tablo/trigger sayılarını ve kritik tablo satır sayılarını kaynakla karşılaştır, `php artisan audit:verify` çalıştır.

| Tarih | Ortam | Sonuç |
|---|---|---|
| 2026-11-12 | Yerel (MariaDB 10.11.16) | ✅ 72 tablo, 4 trigger; `permissions` 68, `roles` 9, `pages` 6, `migrations` 10 kaynakla birebir. Geçici veritabanı silindi |

## 5. İzleme ve alarmlar

| Kontrol | Nasıl | Eşik |
|---|---|---|
| Uptime | Harici izleyici → `GET /up` ve `GET /api/v1/public/server-status` | 2 ardışık hata |
| Başarısız işler | `mrp:health-check` (5 dk) | Son 24 saatte ≥ 1 → warning |
| Kuyruk gecikmesi | `mrp:health-check` | En eski iş > 10 dk → critical |
| Disk | `mrp:health-check` | Boş alan < %10 → critical |
| MTA heartbeat | `mrp:health-check` (bakımda olmayan aktif sunucular) | > 5 dk sessiz → warning |
| Köprü imza hataları | `BridgeAlerts` | 5 dk'da > 20 → kritik audit `bridge.signature_alert` |
| Audit zinciri | `audit:verify` (günlük 03:30) | Kırılma → critical |
| Uygulama hataları | `storage/logs/laravel-*.log` (redaksiyonlu) | Yeni hata türü |

Alarm satırları `storage/logs/security-YYYY-MM-DD.log` dosyasına yazılır; log toplayıcı `critical`/`warning` düzeylerini e-posta veya Discord'a iletir. `mrp:health-check` çıkış kodu 1 = alarm var (`--json` ile ayrıntı).

## 6. Break-glass (acil yönetici erişimi)

- Tüm süper adminler kilitlenirse sunucuda `php artisan mrp:make-super-admin <kullanıcı-adı>` çalıştırılır (kritik audit kaydı üretir).
- 2FA cihazı kaybolan personel: başka bir süper admin ACP'den MFA sıfırlar (gerekçe zorunlu, audit `mfa.reset`). Tek süper admin kurtarma kodunu da kaybettiyse break-glass ile ikinci hesap yetkilendirilir.
- Break-glass sonrası 24 saat içinde: olay notu TODO.md'ye yazılır, kullanılan hesabın parolası ve oturumları sıfırlanır.

## 7. Kimlik bilgisi rotasyonu

| Gizli bilgi | Rotasyon | Etki |
|---|---|---|
| MTA köprü anahtarı | ACP → MTA entegrasyonu → yeni anahtar → `config.lua` → `restart web_bridge` → eski anahtarı **iptal et** | Sunucu başına 2 aktif anahtar kesintisiz geçiş sağlar |
| DB parolaları | `ALTER USER … IDENTIFIED BY …` → `.env` → `php artisan optimize` → `queue:restart` | Kısa bağlantı kesintisi |
| `APP_KEY` | **Planlı bakım.** Eski anahtar `APP_PREVIOUS_KEYS` içinde tutulur; şifreli kolonlar (`integration_credentials.secret`, 2FA sırları) yeniden kaydedilerek yeni anahtarla şifrelenir. Bekleyen bağlama kodları ve imzalı bağlantılar geçersizleşir | Tüm oturumlar düşer |
| `BRIDGE_SERIAL_PEPPER` | Değiştirilmez (serial HMAC eşleşmesi bozulur); sızıntıda cihaz kayıtları yeniden doğrulanır (Faz 8) | |
| SMTP / ödeme / Discord anahtarları | Sağlayıcı panelinden yenile → `.env` → `optimize` | |

Sızıntı şüphesinde sıra: iptal → yenile → etki analizi (audit + security log) → TODO.md'ye olay kaydı.

## 8. Yayın sonrası 72 saat

Saatlik: `mrp:health-check --json`, `failed_jobs`, `security` log, 5xx oranı. Günlük: `audit:verify`, yedek doğrulaması. Bulgular TODO.md'ye tarihli yazılır.
