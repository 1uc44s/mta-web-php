# SRS — Yazılım Gereksinimleri Spesifikasyonu

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [PRD](PRD.md) · [DATABASE](DATABASE.md) · [ROUTES](ROUTES.md) · [ARCHITECTURE](ARCHITECTURE.md) · [RULES](RULES.md)

---

## 1. Giriş

### 1.1 Amaç
Bu doküman Meridyen Roleplay platformunun **test edilebilir** gereksinimlerini tanımlar. Her gereksinimin benzersiz bir kimliği vardır ve testler bu kimliklere referans verir (`it('FR-AUTH-003: ...')`).

### 1.2 Kapsam
Public web sitesi, Oyuncu Paneli (UCP, `/panel`), Yönetim Paneli (ACP, `/admin`), MTA Köprüsü (`/api/v1/game`) ve ödeme webhook'ları.

### 1.3 Tanımlar ve kısaltmalar

| Terim | Anlam |
|---|---|
| Web hesabı | `users` tablosundaki, e-posta + parola ile giriş yapılan hesap |
| Oyun hesabı | MTA sunucusundaki oyuncu hesabı. Web'de `mta_accounts` ile temsil edilir |
| Karakter | Oyun hesabına bağlı roleplay kimliği (`characters`) |
| Personel / yetkili | En az bir `is_staff=true` rolü olan kullanıcı |
| Branş | Yetkili birimi (Destek, Moderasyon, LSPD Fraksiyonu…). Kapsamlı yetkiyi belirler |
| Köprü (Bridge) | MTA Lua resource'u `web_bridge` + backend `/api/v1/game/*` uçları |
| Ledger | Silinemez bakiye hareket defteri (`wallet_transactions`) |
| Dört göz | Talep eden ile onaylayanın farklı kişi olması zorunluluğu |
| Taze / bayat / ölü | Heartbeat yaşı < 90 sn / 90 sn–5 dk / > 5 dk |
| MoSCoW | M=Must, S=Should, C=Could, W=Won't (bu sürüm) |

### 1.4 Referanslar
Kaynak brief, OWASP ASVS 5.0 (L2 hedef), WCAG 2.2 AA, KVKK (6698), MTA Wiki (fetchRemote, getPlayerSerial, Account).

## 2. Genel tanım

### 2.1 Ürün perspektifi
Laravel 13 monolith. Tarayıcı ile sunucu aynı origin'dedir. MTA sunucusu yalnızca **dışarı doğru** HTTPS isteği yapar; backend MTA'ya bağlanmaz (komutlar pull modeliyle alınır). Ayrıntı: [ARCHITECTURE.md](ARCHITECTURE.md).

### 2.2 Kullanıcı sınıfları
Ziyaretçi (anonim) · Oyuncu (doğrulanmamış / doğrulanmış) · Personel (rol + branş) · Süper Admin · Sistem aktörleri (MTA köprüsü, ödeme sağlayıcısı, zamanlayıcı).

### 2.3 Çalışma ortamı

| Ortam | Bileşenler |
|---|---|
| Geliştirme | Windows 11, XAMPP (Apache 2.4, PHP 8.4.x, MariaDB 10.11), Node 24, Composer 2, Mailpit (veya `log` mailer) |
| Production (hedef) | Linux VPS, Nginx + PHP-FPM 8.4/8.5, MySQL 8.4 LTS veya MariaDB 10.11+, Redis (opsiyonel), Supervisor (queue + Reverb), cron (scheduler), TLS (Let's Encrypt) |
| İstemci | Son 2 sürüm: Chrome, Edge, Firefox, Safari (iOS 17+), Samsung Internet. JS kapalıyken temel okuma ve form işlevleri çalışır |

### 2.4 Kısıtlar
- C-01: PHP eklentileri: `pdo_mysql, mbstring, openssl, fileinfo, curl, intl, gd, zip, sodium, bcmath` zorunludur (XAMPP'te `intl, gd, zip, sodium` php.ini'den açılmalı; bkz. [TODO](TODO.md)).
- C-02: Kullanıcı tarafından yazılmış Blade/PHP şablonu **çalıştırılamaz**. Şablon motoru mantıksızdır ([CMS §6](CMS.md)).
- C-03: Oyun veritabanına doğrudan bağlantı yoktur.
- C-04: Tüm zamanlar DB'de UTC saklanır ve arayüzde `Europe/Istanbul` ile gösterilir.

### 2.5 Varsayımlar
- A-01: MTA sunucusu dışarıya HTTPS isteği yapabilir (`fetchRemote` ile).
- A-02: MTA tarafında oyuncu hesabı için kalıcı, benzersiz bir referans (`mta_account_ref`) vardır.
- A-03: Tek MTA sunucusu ile başlanır, model çoklu sunucuya hazırdır.

---

## 3. Fonksiyonel gereksinimler

Sütunlar: **ID · Gereksinim · Öncelik · Faz**. Faz numaraları [ROADMAP](ROADMAP.md) ile aynıdır.

### 3.1 Public site (FR-PUB)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-PUB-001 | Header menüsü `navigation_items(location=header)` tablosundan üretilir. Görünürlük `all/guest/auth` kurallarına uyar. | M | 2 |
| FR-PUB-002 | Header'da sunucu durum rozeti, Panel/Giriş butonu, isteğe bağlı Discord bağlantısı ve tema anahtarı bulunur. Mobilde erişilebilir hamburger menü (focus trap, `Esc` ile kapanma) kullanılır. | M | 2 |
| FR-PUB-003 | Ana sayfa, `pages(slug=home)` kaydının sıralı `page_sections` bölümlerinden render edilir. Bölüm tipleri [CMS §4](CMS.md) kayıt defterindedir. | M | 2 |
| FR-PUB-004 | "Oyuna Katıl" adres panelini açar: `IP:PORT` kopyalama, `mtasa://IP:PORT` bağlantısı ve `/start` bağlantısı. | M | 2 |
| FR-PUB-005 | Sunucu durum kartı FR-BRG-010'daki durum makinesini kullanır. Bayat veri "X dk önce" etiketiyle gösterilir, ölü veride sayı gizlenir. | M | 2/7 |
| FR-PUB-006 | `/systems`, `/systems/{slug}`: kategoriye göre gruplanmış ve yalnızca yayında + `is_live_in_game` olan sistemler. | M | 2 |
| FR-PUB-007 | `/jobs`, `/jobs/{slug}`: sınıf filtresi (legal/özel/etkinlik). Aktif olmayan meslek "Şu an kapalı" etiketiyle gösterilir veya gizlenir (ayar). | M | 2 |
| FR-PUB-008 | `/factions`, `/factions/{slug}`: tür filtresi, başvuru durumu, minimum gereksinimler, ilgili kural kategorisine bağlantı. | M | 2 |
| FR-PUB-009 | `/start`: Akademi'nin `is_onboarding=true` dersinin konularından üretilen adım adım rehber. | M | 2/5 |
| FR-PUB-010 | `/rules`: kategori → kural hiyerarşisi, `#KOD` derin bağlantı, istemci tarafı arama, "son güncelleme". | M | 2 |
| FR-PUB-011 | `/news`: sayfalı liste (12/sayfa), kategori filtresi, `/news/{slug}` detay. Zamanlanmış içerik `published_at <= now()` olunca görünür. | M | 2 |
| FR-PUB-012 | `/faq`: gruplu akordeon ve `FAQPage` JSON-LD. | S | 2 |
| FR-PUB-013 | `/contact`: ad, e-posta, konu, mesaj, honeypot alanı, minimum doldurma süresi (3 sn), `throttle:contact`. | M | 2 |
| FR-PUB-014 | Yasal sayfalar `pages(template=legal)` ile sürümlenir. Sayfada sürüm numarası ve yürürlük tarihi görünür. | M | 2 |
| FR-PUB-015 | `/sitemap.xml` yayındaki public içerikten dinamik üretilir (cache 1 saat). `/robots.txt` `/panel` ve `/admin` yollarını engeller. | M | 2 |
| FR-PUB-016 | Her public sayfada title, meta description, canonical, Open Graph ve Twitter card bulunur. `Organization` ve `WebSite` JSON-LD layout'tadır, `Article` haber detayındadır. | M | 2 |
| FR-PUB-017 | Özel hata sayfaları: 403, 404, 419 (oturum süresi doldu), 429, 500, 503 (bakım). | M | 1 |
| FR-PUB-018 | Bakım modu (ayar) açıkken public sayfalarda üst banner gösterilir. "Tam bakım" seçeneği seçiliyse personel dışındakilere 503 sayfası döner. | S | 4 |
| FR-PUB-019 | Etkinlikler (`community_events`) ana sayfada "Yaklaşan etkinlikler" bölümünde listelenir. | C | 5 |
| FR-PUB-020 | Çerez banner'ı: zorunlu çerezler her zaman açıktır, analitik yalnızca açık onayla çalışır. Tercih `consents` tablosuna (girişliyse) ve cookie'ye yazılır. | M | 2 |

### 3.2 Kimlik doğrulama (FR-AUTH)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-AUTH-001 | Kayıt alanları ve doğrulama kuralları PRD US-AUTH-01'deki gibidir. Kullanıcı adı rezerv listesine (`admin, root, system, support, moderator, meridyen…`) karşı kontrol edilir. | M | 3 |
| FR-AUTH-002 | Parola politikası: min 10, maks 128 karakter, `uncompromised()`, kullanıcı adı veya e-postayı içeremez. | M | 3 |
| FR-AUTH-003 | Hash: Argon2id (`memory=65536 KiB, time=4, threads=1`). Giriş sırasında `needsRehash` olursa yeniden hashlenir. | M | 3 |
| FR-AUTH-004 | Kayıt anında `consents` tablosuna `terms` ve `kvkk_ack` kayıtları sürüm numarasıyla yazılır. | M | 3 |
| FR-AUTH-005 | E-posta doğrulama: imzalı URL (HMAC, `APP_KEY`), 60 dk geçerli, `throttle:6,1`. Doğrulanmış kullanıcıda bağlantı etkisizdir. | M | 3 |
| FR-AUTH-006 | `verified` middleware'i olmayan kullanıcı yalnızca `/panel` (bilgilendirme), `/panel/security` ve doğrulama ekranlarına erişebilir. | M | 3 |
| FR-AUTH-007 | Giriş tanımlayıcısı e-posta veya kullanıcı adıdır (`@` içeriyorsa e-posta kabul edilir). | M | 3 |
| FR-AUTH-008 | Giriş rate limit: `tanımlayıcı+IP` için 5/dk. Ayrıca IP başına 30/saat. Aşımda 429 ve "X sn sonra tekrar deneyin" mesajı. | M | 3 |
| FR-AUTH-009 | Başarılı girişte session ID yenilenir (fixation koruması), `last_login_at` güncellenir, `user_security_events(login_success)` yazılır. | M | 3 |
| FR-AUTH-010 | Başarısız girişte `user_security_events(login_failed)` yazılır (kullanıcı bulunamazsa `user_id=null`, tanımlayıcı hashlenmiş). | M | 3 |
| FR-AUTH-011 | `status=suspended` olan kullanıcı giriş yapamaz. Askı bitiş tarihi geçmişse zamanlanmış görev durumu `active` yapar. Aktif oturumdaki kullanıcı askıya alınırsa sonraki isteğinde oturumu sonlandırılır (`EnsureAccountActive`). | M | 3 |
| FR-AUTH-012 | "Beni hatırla" cookie'si 30 gün geçerlidir. Parola değişince tüm remember token'ları geçersizleşir. | M | 3 |
| FR-AUTH-013 | Şifre sıfırlama: genel yanıt, `password_reset_tokens` (hash), 60 dk, tek kullanım, `throttle:3,10` (e-posta+IP). Başarıda diğer oturumlar silinir ve güvenlik e-postası gönderilir. | M | 3 |
| FR-AUTH-014 | Oturum listesi `sessions` tablosundan okunur. IP maskeli gösterilir (`85.105.x.x`, IPv6 için /48). UA özeti "Chrome · Windows" biçimindedir. | M | 3 |
| FR-AUTH-015 | Oturum kapatma (tekil/diğerleri/hepsi) `password.confirm` gerektirir ve güvenlik olayı yazar. | M | 3 |
| FR-AUTH-016 | TOTP 2FA (Fortify): QR + manuel anahtar, onay kodu ile etkinleştirme, 8 kurtarma kodu, kodları yeniden üretme. Giriş sırasında `two-factor-challenge`. | M | 4 |
| FR-AUTH-017 | Personel rolü olan kullanıcı `two_factor_confirmed_at` olmadan `/admin/*` alanına giremez. `/panel/security/two-factor` sayfasına yönlendirilir. | M | 4 |
| FR-AUTH-018 | E-posta değişikliği: `password.confirm`, `email_change_requests` (token hash, 60 dk). Eski adrese bildirim gider. Değişiklik onaylanınca uygulanır ve `email_verified_at` korunur (yeni adres doğrulanmış sayılır). | S | 6 |
| FR-AUTH-019 | Parola değiştirme: mevcut parola zorunludur. Başarıda diğer oturumlar sonlandırılır ve güvenlik e-postası gönderilir. | M | 6 |
| FR-AUTH-020 | Hesap silme talebi: 14 gün bekleme, ardından anonimleştirme ([DATABASE §13](DATABASE.md#13-anonimleştirme-hesap-silme)). Finansal kayıtlar yasal süre boyunca korunur. | S | 6 |

### 3.3 Oyun hesabı ve karakter (FR-ACC)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-ACC-001 | Bağlama kodu: 8 karakter, Crockford Base32 alfabesi (`0-9A-Z` hariç `I L O U`), gösterimde `XXXX-XXXX`. DB'de `HMAC-SHA256(code, APP_KEY türevi)` saklanır. | M | 7 |
| FR-ACC-002 | Kullanıcı başına aynı anda en fazla 1 aktif kod bulunur. Yeni kod üretmek eskisini iptal eder. Üretim `throttle:5,60`. | M | 7 |
| FR-ACC-003 | Kod 10 dk geçerlidir. Doğrulama denemesi 5'i aşarsa kod iptal edilir. | M | 7 |
| FR-ACC-004 | Köprü `POST /api/v1/game/account-link/verify` ile `{code, mta_account_ref, mta_account_name, server}` gönderir. Backend kodu, süreyi ve tek kullanımı kontrol eder, `mta_accounts` kaydını upsert eder ve `game_account_links(status=active)` oluşturur. Tüm adımlar tek DB transaction içindedir. | M | 7 |
| FR-ACC-005 | Bir `mta_account` aynı anda yalnızca bir aktif bağlantıya sahip olabilir (DB seviyesinde unique, [DATABASE §5.2](DATABASE.md#52-game_account_links)). Bir web hesabı en fazla `settings.accounts.max_links_per_user` (varsayılan 3) oyun hesabına bağlanabilir. | M | 7 |
| FR-ACC-006 | Bağlantı kaldırma oyuncu talebiyle `unlink_requested` durumuna geçer. `game_accounts.manage` yetkilisi onaylarsa `unlinked`. Tüm geçişler audit'e yazılır. | M | 7 |
| FR-ACC-007 | Panelde bekleyen kod ekranı 5 sn aralıkla `GET /panel/game-accounts/link-status` sorgular. Bağlantı kurulunca başarı durumu gösterilir. | M | 7 |
| FR-ACC-008 | Karakter listesi yalnızca kullanıcının **aktif bağlantılı** hesaplarının karakterlerini içerir (policy). | M | 7 |
| FR-ACC-009 | Karakter detayında yalnızca `config/roleplay.php: character_public_fields` allowlist'indeki alanlar gösterilir. | M | 7 |
| FR-ACC-010 | Cihaz/serial ilişkilendirme ([ARCHITECTURE §7.6](ARCHITECTURE.md)): `game_link_codes(purpose=device_link)` akışı, ham serial asla API yanıtına veya arayüze çıkmaz, `serial_hmac` saklanır. | S | 8 |

### 3.4 Oyuncu paneli (FR-UCP)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-UCP-001 | Dashboard kartları PRD US-UCP-01'deki gibidir. Tüm sayılar tek bir sorgu servisiyle (N+1 olmadan) 60 sn kullanıcı bazlı cache'lenir. | M | 6 |
| FR-UCP-002 | Bildirimler: Laravel `notifications` + duyurular (`announcements` hedef kitle eşleşmesi + `announcement_reads`). Sayfa başına 20 kayıt. | M | 6 |
| FR-UCP-003 | Üst bardaki okunmamış sayaç 60 sn'de bir `GET /panel/notifications/count` ile güncellenir (sekme görünürken, ETag destekli). | M | 6 |
| FR-UCP-004 | Tema/düşük efekt tercihi `users.theme`, `users.reduced_effects` alanlarında saklanır. Misafir için cookie kullanılır. | S | 6 |
| FR-UCP-005 | Akademi: ders listesi, konu okuma, "Okudum" (`academy_progress`), ilerleme yüzdesi. | M | 5 |
| FR-UCP-006 | Kural onayı: `settings.rules.current_version` kullanıcının son `rule_acknowledgements.version` değerinden büyükse panelde engelleyici olmayan bir banner ve onay butonu gösterilir. | S | 5 |
| FR-UCP-007 | Tüm panel sayfaları `X-Robots-Tag: noindex, nofollow` ve `Cache-Control: no-store` döner. | M | 6 |

### 3.5 Destek (FR-SUP)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-SUP-001 | Ticket oluşturma: kategori (aktif), konu 5–120 karakter, açıklama 20–5000 karakter, isteğe bağlı karakter (kullanıcıya ait olmalı), en fazla 3 ek. | M | 6 |
| FR-SUP-002 | Ek kuralları: uzantı + MIME (finfo) + boyut (≤ 5 MB) kontrolü. İzinli türler: `jpg, jpeg, png, webp, pdf, txt`. Görseller yeniden kodlanır (EXIF silinir). Dosya adı rastgele (ULID) verilir. Dosyalar `private` diske kaydedilir. İndirme yetki kontrollü controller ile `Content-Disposition: attachment` başlığıyla yapılır. | M | 6 |
| FR-SUP-003 | Form idempotency: gizli `idempotency_key` (UUID) alanı. Aynı anahtarla gelen ikinci istek ilk ticket'a yönlendirilir. | M | 6 |
| FR-SUP-004 | Ticket numarası `T-` + 6 haneli sıra (`T-000123`) biçimindedir. URL'de `ulid` kullanılır. | M | 6 |
| FR-SUP-005 | Durum makinesi: `open → in_progress → waiting_for_player ⇄ waiting_for_staff → resolved → closed`. Oyuncu yanıtı `waiting_for_player` → `waiting_for_staff` geçişi yapar. Yetkili public yanıtı → `waiting_for_player`. 7 gün yanıtsız kalan `waiting_for_player` ticket'ı otomatik `resolved` olur. `resolved` 7 gün sonra `closed` olur. | M | 6 |
| FR-SUP-006 | Mesaj görünürlüğü `public` veya `internal`. Oyuncu sorguları **her zaman** `visibility=public` filtresi uygular. Bu kural model scope + policy + test ile üç katmanda güvence altındadır. | M | 6 |
| FR-SUP-007 | Yetkili kuyruğu: kullanıcının branşlarına ait ticket'lar. `support.all_branches` izni varsa tümü. Filtreler: durum, öncelik, kategori, atanan (ben / atanmamış / herkes), arama (numara/konu). | M | 6 |
| FR-SUP-008 | Atama, öncelik değişimi, branşa iletme (escalation) `support_ticket_events` kaydı oluşturur. Escalation audit'e de yazılır. | M | 6 |
| FR-SUP-009 | Makrolar: değişkenli metin (`{{player.username}}`, `{{ticket.number}}`) şablon motoruyla render edilir ([CMS §6](CMS.md)). | S | 6 |
| FR-SUP-010 | Yeni public yetkili yanıtında oyuncuya panel bildirimi + (tercihe bağlı) e-posta gider. Yeni oyuncu mesajında atanan yetkiliye panel bildirimi gider. | M | 6 |
| FR-SUP-011 | Kapanan ticket'a 7 gün içinde yeniden açma talebi gönderilebilir (`reopen_requested_at`). Yetkili onaylarsa durum `open` olur. | S | 6 |

### 3.6 Başvurular (FR-APP) — R2

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-APP-001 | Başvuru türleri `application_types`: açık/kapalı, uygunluk kuralları (`requires_linked_account`, `requires_character`, `min_account_age_days`, `cooldown_days`). | M | 8 |
| FR-APP-002 | MVP'de form alanları kod içindeki sabit şemalardan gelir (`app/Support/ApplicationForms/*.php`). Cevaplar `application_answers` tablosuna soru etiketinin anlık kopyasıyla yazılır. | M | 8 |
| FR-APP-003 | Durum makinesi: `draft → submitted → in_review → (needs_info ⇄ in_review) → approved \| rejected`. Oyuncu `draft/submitted/needs_info` durumlarında iptal edebilir. | M | 8 |
| FR-APP-004 | Kullanıcı başına tür başına aynı anda 1 açık başvuru olabilir. Ret sonrası `cooldown_days` süresi uygulanır. | M | 8 |
| FR-APP-005 | İnceleme yetkisi: `applications.review` + başvuru türünün branşına üyelik (veya `applications.all_branches`). | M | 8 |
| FR-APP-006 | Karar herkese açık not (oyuncu görür) + iç not (görmez) içerir. Her geçiş `application_status_history` tablosuna ve audit'e yazılır. | M | 8 |

### 3.7 Moderasyon (FR-MOD) — R2

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-MOD-001 | Ceza türleri: `warning, mute, jail, kick, temp_ban, perm_ban, web_suspension`. Hedef: web kullanıcısı, oyun hesabı ve/veya karakter. | M | 8 |
| FR-MOD-002 | `perm_ban` için `sanctions.ban_permanent` izni gerekir. Diğerleri için `sanctions.create`. | M | 8 |
| FR-MOD-003 | Oyun içi etkili cezalar (`kick, temp_ban, perm_ban, jail, mute`) `game_commands` kuyruğuna idempotent komut olarak eklenir. Sonuç (`succeeded/failed`) cezaya bağlanır. | M | 8 |
| FR-MOD-004 | Oyuncu `/panel/sanctions` sayfasında `public_reason`, tür, tarih ve süreyi görür. `internal_note` ve `issued_by` görmez (issued_by yalnızca "Yönetim Ekibi" olarak gösterilir). | M | 8 |
| FR-MOD-005 | İtiraz: ceza başına 1 itiraz, `appealable=true` ve ceza bitişinden önce. İnceleyen kişi cezayı veren kişi olamaz. | M | 8 |
| FR-MOD-006 | `web_suspension` cezası `users.status=suspended` ve `suspended_until` alanlarını ayarlar. Sona erince zamanlayıcı geri alır. | M | 8 |

### 3.8 Ekonomi ve ödeme (FR-WAL / FR-PAY) — R2

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-WAL-001 | Cüzdan türleri `wallet_types` tablosundadır. Kullanıcı başına tür başına 1 cüzdan oluşturulur (ilk ihtiyaçta lazy create). | M | 9 |
| FR-WAL-002 | Her bakiye değişimi `LedgerService::post()` üzerinden yapılır: DB transaction + `SELECT … FOR UPDATE` + `wallet_transactions` insert + `wallets.balance` güncellemesi + `version++`. Başka hiçbir kod `wallets.balance` alanına yazamaz. | M | 9 |
| FR-WAL-003 | `wallet_transactions` append-only'dir. DB trigger'ı UPDATE ve DELETE işlemlerini engeller. Düzeltme `reversal_of_id` ile ters kayıt olarak yapılır. | M | 9 |
| FR-WAL-004 | Her ledger kaydının benzersiz bir `idempotency_key` değeri vardır. Tekrar gelen anahtar yeni kayıt oluşturmaz, mevcut kaydı döner. | M | 9 |
| FR-WAL-005 | Debit işlemi bakiyeyi negatife düşüremez (`balance_after >= 0` CHECK kısıtı + servis kontrolü). | M | 9 |
| FR-WAL-006 | Manuel düzeltme: `wallet.adjust.request` ile talep (tutar, yön, gerekçe ≥ 20 karakter). `settings.wallet.four_eyes_threshold` üstündeki tutarlarda `wallet.adjust.approve` yetkisi olan **farklı** bir kişi onaylar. Eşik altı tutarlar talep edenin onayıyla uygulanabilir (ayar ile kapatılabilir). | M | 9 |
| FR-WAL-007 | Günlük mutabakat görevi her cüzdan için `SUM(credit) - SUM(debit) = balance` eşitliğini doğrular. Fark varsa `critical` audit kaydı ve yönetici bildirimi üretir. | M | 9 |
| FR-PAY-001 | `PaymentProvider` arayüzü: `createCheckout`, `verifyWebhook`, `getPaymentStatus`, `refundPayment`, `normalizeEvent`. Geliştirmede `FakeProvider` kullanılır. | M | 9 |
| FR-PAY-002 | Checkout: paket seçilir, `payment_orders(status=created)` kaydı açılır, sağlayıcı URL'ine 303 yönlendirme yapılır. Tutar **her zaman** sunucudaki paket kaydından alınır; istemciden gelen tutar yok sayılır. | M | 9 |
| FR-PAY-003 | Webhook: imza doğrulama → `payment_events` insert (unique `provider+provider_event_id`) → kuyruğa işleme. Tutar, para birimi ve sipariş eşleşmesi kontrol edilir. `succeeded` olunca ledger credit (`idempotency_key = pay:{order_ulid}`) yazılır. | M | 9 |
| FR-PAY-004 | Dönüş (success) sayfası bakiye yüklemez. Yalnızca sipariş durumunu gösterir ve `pending` ise 5 sn'de bir yeniler. | M | 9 |
| FR-PAY-005 | İade: `payments.refund.request` ile talep, `payments.refund.approve` yetkisi olan farklı bir kişi tarafından onay. Sağlayıcı API'si çağrılır. Başarılı olursa ledger debit (bakiye yetmiyorsa negatif bakiyeye izin verilmez, durum `requires_review` olur). | M | 9 |
| FR-PAY-006 | Ödeme modülü `settings.features.payments=false` iken tüm ödeme rotaları 404 döner, menüde görünmez. | M | 9 |

### 3.9 İçerik yönetimi (FR-CMS)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-CMS-001 | Yönetilen içerik türleri ve alanları [CMS.md §3](CMS.md) tablosundadır. Her türde durum, sıralama, slug, SEO alanları, oluşturan/güncelleyen ve zaman damgaları bulunur. | M | 5 |
| FR-CMS-002 | Durum akışı: `draft → in_review → scheduled/published → archived`. `content.publish` izni olmayan kişi `in_review` durumuna kadar ilerletebilir. | M | 5 |
| FR-CMS-003 | Slug: Türkçe karakterler dönüştürülür (`ş→s, ı→i`…), küçük harf, tire, max 120 karakter, tür içinde benzersiz. Yayınlanmış içeriğin slug'ı değişirse eski slug `redirects` tablosuna 301 olarak yazılır. | M | 5 |
| FR-CMS-004 | Zengin metin Markdown (CommonMark + GFM tablo) olarak saklanır. Render sırasında HTML girdisi escape edilir, güvensiz bağlantılar engellenir ve çıktı `symfony/html-sanitizer` ile temizlenir. Render sonucu cache'lenir. | M | 5 |
| FR-CMS-005 | Medya kütüphanesi: yükleme (jpg/png/webp/avif/svg yasak), max 8 MB, WebP varyantları (480/960/1600 px), zorunlu `alt_text` (dekoratif ise açıkça işaretlenir). | M | 5 |
| FR-CMS-006 | Önizleme: taslak içerik imzalı ve 30 dk geçerli `?preview=` URL'iyle yalnızca `content.read` yetkilisine render edilir. | S | 5 |
| FR-CMS-007 | Sayfa ve kural kayıtları her yayında `*_revisions` tablosuna anlık görüntü yazar. ACP'de revizyonlar arası fark görüntülenebilir ve geri alınabilir. | S | 5 |
| FR-CMS-008 | Zamanlayıcı her dakika `scheduled` içerikleri `published_at <= now()` olduğunda yayına alır ve ilgili cache etiketlerini temizler. | M | 5 |
| FR-CMS-009 | Akademi: `academy_courses` (ders) → `academy_topics` (konu). Konu ↔ kural ilişkisi kurulabilir. Ayrıntı [CMS §5](CMS.md). | M | 5 |
| FR-CMS-010 | Mesaj şablonları: `message_templates` (anahtar + kanal + dil). Sistem şablonları silinemez, yalnızca düzenlenebilir. Değişkenler şablon başına allowlist ile sınırlıdır. Ayrıntı [CMS §6](CMS.md). | M | 5 |

### 3.10 Yönetim paneli (FR-ADM)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-ADM-001 | `/admin` erişimi: `auth + verified + active + staff + mfa + admin.idle`. | M | 4 |
| FR-ADM-002 | Menü öğeleri yalnızca kullanıcının izni olan modülleri gösterir. Ancak güvenlik **her rotada** backend `can:` kontrolüyle sağlanır. | M | 4 |
| FR-ADM-003 | Admin oturumu 30 dk hareketsizlikte yeniden parola onayı ister (`admin.idle`). Oyuncu oturumu 120 dk'dır. | M | 4 |
| FR-ADM-004 | Kritik işlemler (`roles.*`, `staff.manage`, `settings.update`, `integrations.manage`, `wallet.adjust.*`, `payments.refund.*`, `sanctions.ban_permanent`) `password.confirm` (10 dk) gerektirir. | M | 4 |
| FR-ADM-005 | Kullanıcı yönetimi: arama (kullanıcı adı, e-posta, ULID, MTA hesap adı), filtre (durum, doğrulama, rol, kayıt tarihi), detay sekmeleri (Profil, Oyun hesapları, Destek, Başvurular, Cezalar, Güvenlik\*, Finans\*). \* ayrı izin gerektirir. | M | 4 |
| FR-ADM-006 | E-posta adresi yalnızca `users.read_pii` izniyle tam gösterilir, diğer yetkililere maskeli gösterilir (`f***@g***.com`). | M | 4 |
| FR-ADM-007 | Rol, izin ve branş yönetimi ile atama kuralları [ADMIN.md](ADMIN.md) belgesindedir. | M | 4 |
| FR-ADM-008 | Audit log listesi, filtreleri ve hash zinciri doğrulama komutu (`php artisan audit:verify`). | M | 4 |
| FR-ADM-009 | Sistem ayarları gruplu formlardan yönetilir. `is_secret` ayarları yalnızca "ayarlı / ayarlı değil" olarak gösterilir. Değiştirmek yeni değer girmeyi gerektirir. | M | 4 |
| FR-ADM-010 | Dashboard: brief §7.3'teki kartlar ve grafikler. Her metrik `{değer, kaynak, hesaplandığı zaman}` üçlüsüyle gösterilir. Ağır metrikler zamanlanmış görevde (5 dk) hesaplanıp cache'lenir. | M | 4 |
| FR-ADM-011 | Duyurular: hedef kitle (`all, players, linked_players, roles`), kanallar (`public_banner, panel, email`), başlangıç/bitiş, öncelik. E-posta kanalı kuyrukla ve 100'lük batch'ler halinde gönderilir. | M | 5 |
| FR-ADM-012 | MTA entegrasyon ekranı: sunucular, son heartbeat, credential'lar (key id görünür, secret yalnızca oluşturulduğu anda bir kez gösterilir), rotasyon, iptal. Yalnızca `integrations.manage`. | M | 7 |

### 3.11 MTA köprüsü (FR-BRG)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-BRG-001 | Tüm köprü istekleri şu başlıkları taşır: `X-Bridge-Key`, `X-Bridge-Timestamp` (unix sn), `X-Bridge-Nonce` (≥ 16 byte, hex), `X-Bridge-Signature` (hex HMAC-SHA256). | M | 7 |
| FR-BRG-002 | Canonical metin: `METHOD\nPATH\nTIMESTAMP\nNONCE\nhex(SHA256(BODY))`. Sabit zamanlı karşılaştırma (`hash_equals`) kullanılır. | M | 7 |
| FR-BRG-003 | Zaman penceresi ±300 sn. Nonce `bridge_nonces` tablosuna unique insert edilir. Çakışma replay sayılır, 409 döner ve loglanır. Nonce kayıtları 15 dk sonra silinir. | M | 7 |
| FR-BRG-004 | Credential, `integration_credentials` tablosunda bir `mta_server` kaydına bağlıdır. Secret `encrypted` cast ile saklanır. Rotasyon için aynı anda en fazla 2 aktif anahtar bulunur. | M | 7 |
| FR-BRG-005 | Gövde ≤ 64 KB ve JSON olmalıdır. Her uç kendi FormRequest şemasıyla doğrulanır. Bilinmeyen alanlar yok sayılır ve loglanmaz. | M | 7 |
| FR-BRG-006 | Rate limit: anahtar başına 120/dk (heartbeat hariç ayrı limit 6/dk). | M | 7 |
| FR-BRG-007 | Olay uçları `event_id` (UUID) taşır. `game_events (mta_server_id, event_id)` unique olduğundan tekrar gelen olay 200 + `{duplicate:true}` döner, yan etki üretmez. | M | 7 |
| FR-BRG-008 | Başarısız doğrulamalar (`bridge.signature_invalid`, `bridge.replay`, `bridge.stale_timestamp`) `security` log kanalına secret içermeden yazılır. 5 dk'da 20'yi aşarsa yönetici bildirimi gönderilir. | M | 7 |
| FR-BRG-009 | Heartbeat: `{players, max_players, server_name, game_mode, map, resource_version, uptime_sec}`. `server_heartbeats` insert + `mta_servers` özet alanları güncellenir. | M | 7 |
| FR-BRG-010 | Durum makinesi: `maintenance` (admin bayrağı) > `online` (yaş < 90 sn) > `stale` (90 sn–5 dk) > `offline` (> 5 dk veya hiç heartbeat yok). Public API yalnızca bu hesaplanmış durumu döner. | M | 7 |
| FR-BRG-011 | Komut kuyruğu (pull): `GET /commands/pending` en fazla 20 komut döner ve bunları `dispatched` işaretler. `POST /commands/result` sonucu yazar. 2 dk içinde sonuç gelmeyen komut tekrar `pending` olur (max 3 deneme), sonra `failed`. Komut türleri allowlist'tedir ([ARCHITECTURE §7.5](ARCHITECTURE.md)). | S | 8 |
| FR-BRG-012 | Köprü yanıtları hiçbir zaman ham serial, e-posta, IP veya token içermez. | M | 7 |

### 3.12 Bildirim ve mesajlaşma (FR-NTF)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-NTF-001 | Tüm sistem mesajları `MessageDispatcher` üzerinden şablon anahtarıyla gönderilir. Kod içinde düz metin e-posta gövdesi yazılmaz. | M | 5 |
| FR-NTF-002 | Kanallar: `mail` (R1), `panel` (R1), `discord_webhook` (R2), `whatsapp` (R3). Her gönderim `message_dispatches` tablosuna loglanır. | M | 5 |
| FR-NTF-003 | Transactional e-postalar (doğrulama, sıfırlama, güvenlik) tercihten bağımsız gönderilir. Diğerleri `user_notification_preferences` ayarına uyar. | M | 6 |
| FR-NTF-004 | WhatsApp yalnızca doğrulanmış numara + `consents(whatsapp_optin, granted=true)` varsa ve yalnızca Meta onaylı şablonlarla gönderilir. Opt-out anında uygulanır. | C | 10 |
| FR-NTF-005 | Tüm e-postalar kuyruktan gönderilir. Başarısız gönderim 3 kez üstel geri çekilmeyle (1, 5, 15 dk) tekrar denenir. | M | 3 |

### 3.13 PWA (FR-PWA)

| ID | Gereksinim | Ö | Faz |
|---|---|---|---|
| FR-PWA-001 | `manifest.webmanifest`: ad, kısa ad, ikonlar (192/512 + maskable), `start_url=/panel?source=pwa`, `display=standalone`, tema rengi. | S | 6 |
| FR-PWA-002 | Service worker yalnızca güvenli bağlamda (HTTPS veya localhost) kaydedilir. Yaşam döngüsü ve cache stratejileri [ARCHITECTURE §8](ARCHITECTURE.md) belgesindedir. | S | 6 |
| FR-PWA-003 | Kimliği doğrulanmış HTML (`/panel`, `/admin`) ve tüm `/api` yanıtları **asla** cache'lenmez. | M | 6 |
| FR-PWA-004 | Yeni SW sürümü beklemedeyken kullanıcıya "Yeni sürüm hazır — Yenile" toast'ı gösterilir. Onaylanırsa `SKIP_WAITING` gönderilir ve sayfa yenilenir. | S | 6 |
| FR-PWA-005 | Çıkışta SW'ye `CLEAR_RUNTIME_CACHE` mesajı gönderilir. Logout yanıtı `Clear-Site-Data: "cache"` başlığını taşır. | M | 6 |

---

## 4. Fonksiyonel olmayan gereksinimler

### 4.1 Performans (NFR-PERF)

| ID | Gereksinim |
|---|---|
| NFR-PERF-001 | Public sayfalar: p75 LCP ≤ 2,5 sn, INP ≤ 200 ms, CLS ≤ 0,1 (mobil, 4G). |
| NFR-PERF-002 | Sunucu yanıtı (TTFB): cache'li public sayfa p95 ≤ 200 ms, panel sayfası p95 ≤ 400 ms, admin liste sayfası p95 ≤ 600 ms (production donanımında). |
| NFR-PERF-003 | Public sayfa başına ilk yüklemede JS ≤ 60 KB (gzip), CSS ≤ 40 KB (gzip). Ağır bileşenler (grafik, editör) yalnızca kullanıldıkları sayfada yüklenir. |
| NFR-PERF-004 | Sayfa başına SQL sorgusu ≤ 15. N+1 sorgular geliştirmede `Model::preventLazyLoading()` ile hata verir. |
| NFR-PERF-005 | Görseller WebP/AVIF formatında, `srcset/sizes`, `loading=lazy` (hero hariç, hero `fetchpriority=high`), boyutları belirtilmiş (CLS önleme) sunulur. |
| NFR-PERF-006 | Statik varlıklar hash'li adla ve `Cache-Control: public, max-age=31536000, immutable` ile sunulur. |
| NFR-PERF-007 | Tüm listeler sayfalıdır (varsayılan 20, max 100). Admin tablolarında büyük veri için cursor pagination kullanılır. |
| NFR-PERF-008 | `/api/v1/public/server-status` yanıtı 15 sn cache'lenir. 1000 eşzamanlı istekte p95 ≤ 100 ms. |

### 4.2 Erişilebilirlik (NFR-A11Y) — WCAG 2.2 AA

| ID | Gereksinim |
|---|---|
| NFR-A11Y-001 | Tüm etkileşimler klavyeyle yapılabilir. Görünür odak halkası (≥ 2 px, kontrast ≥ 3:1) bulunur. Her sayfanın başında "İçeriğe geç" bağlantısı vardır. |
| NFR-A11Y-002 | Metin kontrastı ≥ 4.5:1 (büyük metin ≥ 3:1). Hem dark hem light temada doğrulanır. |
| NFR-A11Y-003 | Durumlar yalnızca renkle anlatılmaz (ikon + metin). |
| NFR-A11Y-004 | Modal ve drawer bileşenlerinde focus trap, `Esc` ile kapanma ve odağın açan elemana dönmesi sağlanır. `aria-modal` kullanılır. |
| NFR-A11Y-005 | Form hataları alanla `aria-describedby` ile ilişkilendirilir. Gönderimde hata özeti gösterilir ve odak ilk hatalı alana taşınır. |
| NFR-A11Y-006 | `prefers-reduced-motion: reduce` ve "Düşük efekt" modunda dekoratif animasyonlar, parallax ve otomatik oynatılan videolar kapatılır. |
| NFR-A11Y-007 | Dokunma hedefleri ≥ 44×44 px (WCAG 2.5.8 için min 24 px, hedefimiz 44 px). |
| NFR-A11Y-008 | `lang="tr"` kullanılır. Sayfa başlıkları benzersizdir ve heading hiyerarşisi atlanmaz. |

### 4.3 Uyumluluk ve duyarlılık (NFR-RESP)
- NFR-RESP-001: 360, 390–430, 768, 1024, 1366 ve 1920 px genişliklerde kırılma, yatay sayfa kaydırması veya üst üste binen öğe yoktur.
- NFR-RESP-002: Admin tabloları < 768 px'te kart görünümüne geçer. Hover'a bağlı hiçbir işlem yoktur.
- NFR-RESP-003: %200 tarayıcı zoom'unda içerik kaybı olmaz.

### 4.4 Güvenilirlik ve kullanılabilirlik (NFR-REL)

| ID | Gereksinim |
|---|---|
| NFR-REL-001 | Hedef çalışma süresi %99,5/ay (planlı bakım hariç). |
| NFR-REL-002 | MTA bağlantısı yokken public site, giriş, destek ve CMS çalışmaya devam eder. Oyun verisi gerektiren aksiyonlar açıklamalı biçimde devre dışı kalır. |
| NFR-REL-003 | Kuyruk işleri idempotent yazılır. Başarısız işler `failed_jobs` tablosuna düşer ve admin dashboard'unda sayaç gösterilir. |
| NFR-REL-004 | Yedekleme: günlük tam DB dökümü (şifreli, uygulama sunucusu dışında, 30 gün saklama). Saatlik binlog/artımlı yedek (production). Aylık geri yükleme testi yapılır. |
| NFR-REL-005 | RPO ≤ 24 saat (R1), ≤ 1 saat (R2+). RTO ≤ 4 saat. |

### 4.5 Bakım kolaylığı (NFR-MNT)
- NFR-MNT-001: Kod standartları [RULES.md](RULES.md) belgesindedir. Pint, Larastan (seviye 6+) ve Pest CI'da zorunludur.
- NFR-MNT-002: İş mantığı controller'da değil, Action/Service sınıflarındadır. Controller'lar ince tutulur.
- NFR-MNT-003: Kritik iş mantığı (ledger, imza doğrulama, link kodu, durum makineleri, izin kontrolleri) için birim testi kapsamı ≥ %90, genel kapsam ≥ %70.

### 4.6 Gözlemlenebilirlik (NFR-OBS)
- NFR-OBS-001: Her istekte `X-Request-Id` üretilir veya taşınır. Loglarda ve hata yanıtlarında yer alır.
- NFR-OBS-002: Log kanalları: `daily` (uygulama, 14 gün), `security` (90 gün), `bridge` (30 gün), `payments` (365 gün). Yapılandırılmış JSON formatı kullanılır.
- NFR-OBS-003: Loglarda parola, token, secret, ham serial, tam kart verisi veya tam IP **yer almaz** (log processor ile maskelenir).
- NFR-OBS-004: Production'da hata izleme (Sentry uyumlu, opsiyonel) ve uptime izleme kullanılır.

### 4.7 Uluslararasılaştırma (NFR-I18N)
- NFR-I18N-001: Varsayılan dil `tr`. Tüm arayüz metinleri `lang/tr/*.php` ve `lang/tr.json` dosyalarındadır, Blade içinde sabit metin yazılmaz (R3'te `en` eklenebilsin).
- NFR-I18N-002: Tarihler `Europe/Istanbul`, `d MMMM yyyy HH:mm` biçimindedir. Göreli zaman ("3 dk önce") ve sayılar `intl` ile biçimlendirilir.

### 4.8 SEO (NFR-SEO)
Brief §14.1 maddelerinin tümü. Ek olarak: UCP/ACP `noindex`, sayfalı listelerde `rel=prev/next` yerine canonical'a ilk sayfa, Türkçe slug normalizasyonu.

---

## 5. Güvenlik gereksinimleri (SEC)

### 5.1 Kimlik ve oturum

| ID | Gereksinim |
|---|---|
| SEC-001 | Session driver `database`. Cookie: `HttpOnly`, `Secure` (prod), `SameSite=Lax`. Production'da adı `__Host-mrp_session` olur (Path=/, Domain yok). |
| SEC-002 | Session ID girişte, 2FA doğrulamasında, rol değişiminde ve parola değişiminde yenilenir. Çıkışta oturum geçersizleşir ve CSRF token yenilenir. |
| SEC-003 | Oyuncu idle timeout 120 dk, mutlak oturum ömrü 7 gün (remember hariç). Admin alanında 30 dk idle sonrası yeniden doğrulama istenir. |
| SEC-004 | Parola onayı (`password.confirm`) 10 dk geçerlidir. |
| SEC-005 | Hesap enumerasyonu önlenir: kayıt, giriş ve sıfırlama yanıtları hesap varlığını ifşa etmez. Yanıt süreleri dengelenir (sahte hash doğrulaması). |

### 5.2 Yetkilendirme

| ID | Gereksinim |
|---|---|
| SEC-010 | Her admin rotası `can:<izin>` middleware'i **veya** controller'da `Gate::authorize()` taşır. CI'da "korumasız admin rotası" testi tüm `admin.*` rotalarını tarar ve izin tanımı olmayan rotada testi başarısız yapar. |
| SEC-011 | Oyuncu kaynakları Policy ile sahiplik kontrolünden geçer. Route model binding'de `ulid` kullanılır, ID enumerasyonu engellenir. |
| SEC-012 | Branş kapsamlı izinler (destek, başvuru, fraksiyon) Policy içinde branş üyeliğiyle birlikte kontrol edilir. |
| SEC-013 | Rol hiyerarşisi: kullanıcı yalnızca kendi en yüksek rol önceliğinden **düşük** öncelikli rolleri atayabilir veya kaldırabilir. Kendi rollerini değiştiremez. |
| SEC-014 | Mass assignment: tüm modellerde `$fillable` açıkça tanımlanır. `$guarded = []` yasaktır. |

### 5.3 Girdi, çıktı ve tarayıcı güvenliği

| ID | Gereksinim |
|---|---|
| SEC-020 | Tüm girdiler FormRequest ile doğrulanır. Doğrulanmamış `$request->all()` kullanımı yasaktır (`$request->validated()`). |
| SEC-021 | SQL: Eloquent/Query Builder (PDO prepared statements, `PDO::ATTR_EMULATE_PREPARES=false`). Ham SQL yalnızca binding ile yazılır. Dinamik `ORDER BY` ve kolon adları allowlist'ten gelir ([RULES §5](RULES.md)). |
| SEC-022 | XSS: Blade `{{ }}` ile escape edilir. `{!! !!}` yalnızca `SafeHtml` value object'inden (sanitize edilmiş) gelen içerik için kullanılabilir. |
| SEC-023 | CSRF: tüm state değiştiren web istekleri `POST` (veya spoofed `PATCH/DELETE`) + token gerektirir. İstisnalar yalnızca HMAC/imza ile korunan `/api/v1/game/*` ve `/webhooks/*` rotalarıdır. |
| SEC-024 | CSP (nonce tabanlı): `default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self' <ödeme sağlayıcısı>; object-src 'none'`. `unsafe-inline` ve `unsafe-eval` yasaktır (Alpine **CSP build** zorunlu). |
| SEC-025 | Güvenlik başlıkları: `Strict-Transport-Security: max-age=31536000; includeSubDomains` (prod), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `Cross-Origin-Opener-Policy: same-origin`. |
| SEC-026 | Açık yönlendirme yoktur: `redirect`/`intended` parametreleri yalnızca göreli ve aynı origin olabilir. |
| SEC-027 | Dosya yükleme: FR-SUP-002 ve FR-CMS-005 kuralları geçerlidir. SVG yüklemesi yasaktır. Yükleme dizininde PHP çalıştırılamaz (Apache/Nginx kuralı). |

### 5.4 Rate limit tablosu

| Limiter | Kapsam | Limit |
|---|---|---|
| `login` | tanımlayıcı + IP | 5/dk; IP 30/saat |
| `register` | IP | 5/saat |
| `password-reset` | e-posta + IP | 3/10 dk |
| `verification` | kullanıcı | 6/saat |
| `two-factor` | session | 5/dk |
| `contact` | IP | 5/saat |
| `link-code` | kullanıcı | 5/saat |
| `support-create` | kullanıcı | 10/saat |
| `support-message` | kullanıcı | 30/saat |
| `uploads` | kullanıcı | 30/saat |
| `bridge` | API anahtarı | 120/dk |
| `bridge-heartbeat` | API anahtarı | 6/dk |
| `webhooks` | sağlayıcı + IP | 300/dk |
| `public-api` | IP | 60/dk |
| `poll` | kullanıcı | 30/dk |

### 5.5 Kriptografi ve secret yönetimi
- SEC-040: Secret'lar `.env` (geliştirme) veya ortam değişkeni / secret manager (production) içindedir. `.env` dosyası repoya girmez, `.env.example` girer.
- SEC-041: DB'deki hassas kolonlar (`two_factor_secret`, `integration_credentials.secret`, `user_contact_channels.address`) Laravel `encrypted` cast (AES-256-CBC + MAC) ile saklanır.
- SEC-042: Token ve kodlar düz metin saklanmaz: `SHA-256` (yüksek entropili token) veya `HMAC-SHA256` (düşük entropili kod, anahtar `APP_KEY` türevi).
- SEC-043: IP adresleri güvenlik olaylarında `ip_hash` (HMAC) + `ip_masked` olarak saklanır. Ham IP yalnızca `sessions` tablosunda, oturum ömrü boyunca tutulur.
- SEC-044: `APP_KEY` rotasyonu için `APP_PREVIOUS_KEYS` kullanılır.

### 5.6 Ödeme güvenliği
Brief §9.4 ve §12.4 maddelerinin tümü. Ek olarak SEC-050: Webhook uçları yalnızca imza doğrulamasından sonra DB'ye yazar. Doğrulanmamış payload `payment_events(signature_valid=false)` olarak saklanır, ancak işlenmez.

### 5.7 Bağımlılık güvenliği
SEC-060: `composer audit` ve `npm audit --omit=dev` CI'da her build'de çalışır. Yüksek/kritik açık build'i durdurur.

---

## 6. Veri gereksinimleri

### 6.1 Veri sahipliği (source of truth)

| Veri | Ana kaynak | Web'deki yaklaşım |
|---|---|---|
| Web hesabı, e-posta, parola hash | Web DB | Web yönetir |
| MTA oyun hesabı | MTA | `mta_accounts` = referans + senkron özet |
| Karakter | MTA | `characters` = salt okunur özet (köprüyle güncellenir) |
| Oyun içi para | MTA | Karakter özetinde salt okunur. Web ledger'ına girmez |
| Web kredisi / premium puan | Web DB | Ledger |
| Ticket, başvuru, CMS, duyuru | Web DB | Web yönetir |
| Ceza (web) | Web DB | Web yönetir. Oyun içi etkisi komutla uygulanır |
| Sunucu durumu | MTA | Heartbeat |

### 6.2 Saklama süreleri

| Veri | Süre | Sonra |
|---|---|---|
| `sessions` | Oturum ömrü (max 30 gün) | Silinir |
| `user_security_events` | 180 gün | Silinir |
| `server_heartbeats` | 30 gün (ham) | Silinir. Günlük özet `server_stats_daily` tablosunda 2 yıl tutulur |
| `bridge_nonces` | 15 dk | Silinir |
| `game_events` | 90 gün | Silinir |
| `admin_activity_logs` | 90 gün | Silinir |
| `audit_logs` | 5 yıl | Arşiv |
| `wallet_transactions`, `payment_*`, `refunds` | 10 yıl (VUK/TTK — hukuk teyidi gerekli) | Arşiv |
| `contact_messages` | 1 yıl | Silinir |
| Ticket ve ekler | Kapanıştan 2 yıl sonra | Ekler silinir, içerik anonimleştirilir |
| Silinen hesap | 14 gün bekleme | Anonimleştirme |

Saklama görevleri `php artisan data:prune` ile zamanlanır (günlük 03:30).

### 6.3 KVKK
- Veri minimizasyonu: kayıtta doğum tarihi, telefon veya ad-soyad alınmaz.
- Aydınlatma metni ve açık rıza ayrımı yapılır. Rızaya dayalı işlemler (analitik çerez, WhatsApp, pazarlama e-postası) `consents` tablosunda sürümlü tutulur ve geri alınabilir.
- İlgili kişi başvuruları için `/kvkk` sayfasında başvuru kanalı belirtilir. Veri dökümü (JSON export) R2'de eklenir.

## 7. Harici arayüzler

| Sistem | Yön | Protokol | Kimlik doğrulama |
|---|---|---|---|
| MTA `web_bridge` | MTA → Web | HTTPS JSON (`fetchRemote`) | HMAC-SHA256 + timestamp + nonce |
| Ödeme sağlayıcısı | Web ↔ Sağlayıcı | HTTPS (redirect checkout + webhook) | Sağlayıcı imzası + API anahtarı |
| SMTP / e-posta API | Web → | SMTP/TLS veya HTTP API | Kimlik bilgisi `.env` |
| Discord webhook (R2) | Web → | HTTPS | Webhook URL secret |
| WhatsApp Cloud API (R3) | Web ↔ | HTTPS Graph API | Erişim token'ı + webhook doğrulama |
| Have I Been Pwned (k-anon) | Web → | HTTPS | Yok (yalnızca 5 karakter hash öneki gönderilir) |

## 8. Hata yönetimi

- Web: Kullanıcı dostu hata sayfaları (FR-PUB-017). Form hatalarında girilen veri korunur (`old()`). Parola alanları hariç.
- JSON API hata formatı (köprü, public API, polling uçları):

```json
{
  "success": false,
  "error": { "code": "VALIDATION_ERROR", "message": "Gönderilen bilgiler kontrol edilmeli.", "details": [ { "field": "code", "message": "Kod geçersiz veya süresi dolmuş." } ] },
  "requestId": "01JC9Z…"
}
```

- Hata kodları: `VALIDATION_ERROR (422)`, `UNAUTHENTICATED (401)`, `FORBIDDEN (403)`, `NOT_FOUND (404)`, `CONFLICT (409)`, `REPLAY_DETECTED (409)`, `RATE_LIMITED (429)`, `SIGNATURE_INVALID (401)`, `FEATURE_DISABLED (404)`, `SERVER_ERROR (500)`.
- Production'da stack trace, SQL, dosya yolu veya secret asla döndürülmez (`APP_DEBUG=false`, CI kontrolü).

## 9. İzlenebilirlik matrisi

| Kullanıcı hikayesi | Fonksiyonel gereksinimler | Test dosyası (planlanan) |
|---|---|---|
| US-PUB-01..02 | FR-PUB-003, 004 | `tests/Feature/Public/HomePageTest.php` |
| US-PUB-03 | FR-PUB-005, FR-BRG-009, 010 | `tests/Unit/Bridge/ServerStatusResolverTest.php` |
| US-PUB-04..10 | FR-PUB-006..016 | `tests/Feature/Public/*` |
| US-AUTH-01..05 | FR-AUTH-001..015 | `tests/Feature/Auth/*` |
| US-AUTH-06 | FR-AUTH-016, 017 | `tests/Feature/Admin/MfaEnforcementTest.php` |
| US-ACC-01..03 | FR-ACC-001..009, FR-BRG-001..008 | `tests/Feature/Bridge/AccountLinkTest.php` |
| US-UCP-01..03 | FR-UCP-001..007 | `tests/Feature/Panel/*` |
| US-SUP-01..05 | FR-SUP-001..011 | `tests/Feature/Support/*` |
| US-APP-01..03 | FR-APP-001..006 | `tests/Feature/Applications/*` |
| US-MOD-01..03 | FR-MOD-001..006, FR-BRG-011 | `tests/Feature/Moderation/*` |
| US-WAL-01..04 | FR-WAL-001..007, FR-PAY-001..006 | `tests/Unit/Wallet/LedgerServiceTest.php`, `tests/Feature/Payments/*` |
| US-ADM-01..07 | FR-ADM-001..012 | `tests/Feature/Admin/*`, `tests/Feature/Security/AdminRouteProtectionTest.php` |
| US-BRG-01..03 | FR-BRG-001..012 | `tests/Feature/Bridge/*` |
