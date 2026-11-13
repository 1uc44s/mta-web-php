# DATABASE — Veritabanı Şeması

> **Sürüm:** 1.0.1 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [ERD](ERD.md) · [SRS §6](SRS.md#6-veri-gereksinimleri) · [RULES §5](RULES.md)

Bu doküman **migration'ların kaynağıdır**. Şemadaki her değişiklik önce burada yapılır, sonra migration yazılır. Migration'daki bir kolon burada yoksa hata kabul edilir.

---

## 1. Genel kurallar

### 1.1 Motor ve karakter seti
- Motor: **InnoDB**. Karakter seti `utf8mb4`, collation `utf8mb4_unicode_ci`.
- Laravel sürücüsü: `mariadb` (geliştirme, MariaDB 10.11). Production'da MySQL 8.4 kullanılırsa sürücü `mysql` olur. Şema iki motorda da birebir çalışır: yalnızca ortak özellikler (CHECK, STORED generated column, JSON alias, trigger) kullanılır.
- Bağlantı ayarları (`config/database.php`, `mariadb` ve `mysql` bağlantıları):
  - `options`: `PDO::ATTR_EMULATE_PREPARES => false`, `PDO::ATTR_STRINGIFY_FETCHES => false`. Bu iki seçenek Laravel'in `array_filter([...])` bloğunun **dışında** tutulur; aksi halde `false` değerler sessizce silinir.
  - `'timezone' => '+00:00'`: Laravel bağlantıda `SET time_zone` çalıştırır.
  - `'strict' => true`: Laravel `ONLY_FULL_GROUP_BY, STRICT_TRANS_TABLES, NO_ZERO_IN_DATE, NO_ZERO_DATE, ERROR_FOR_DIVISION_BY_ZERO, NO_ENGINE_SUBSTITUTION` modlarını ayarlar.
  - `'engine' => 'InnoDB'`.
- Geliştirme veritabanları: `roleplay_web` (uygulama) ve `roleplay_web_test` (Pest). Kullanıcı: `roleplay_app` (yalnızca bu iki şemada yetkili). Parola yalnızca `.env` içindedir.

### 1.2 İsimlendirme
- Tablolar: çoğul `snake_case` (`support_tickets`). Pivot: anlamlı ad (`user_roles`, `role_permissions`, `branch_members`).
- Kolonlar: `snake_case`. FK: `<tekil_tablo>_id`. Boolean: `is_`/`has_`/`requires_` öneki. Zaman: `_at` soneki. Para: `_minor` soneki.
- Index adları: `idx_<tablo>_<kolonlar>`, unique: `uq_<tablo>_<kolonlar>`, FK: `fk_<tablo>_<kolon>`.

### 1.3 Tip standartları

| Kavram | Tip | Not |
|---|---|---|
| Primary key | `BIGINT UNSIGNED AUTO_INCREMENT` | Laravel `id()` |
| Dışa açık kimlik | `CHAR(26)` ULID, unique | URL'de görünen varlıklar. Route key budur (D-09) |
| Enum benzeri alan | `VARCHAR(32)` | PHP backed enum cast ile. DB `ENUM` tipi kullanılmaz (migration esnekliği için). Finansal tablolarda ek `CHECK` kısıtı bulunur |
| Zaman | `DATETIME` (UTC) | `TIMESTAMP` kullanılmaz (2038 sorunu) |
| Para | `BIGINT` minor unit + `CHAR(3)` ISO 4217 | Float/DECIMAL kullanılmaz |
| Kredi/puan | `BIGINT` | Tam sayı |
| JSON | `JSON` | MariaDB'de `LONGTEXT + CHECK(JSON_VALID)` aliası. Sorgu filtresi gereken alan JSON'da tutulmaz |
| Uzun metin | `TEXT` (≤ 64 KB) / `MEDIUMTEXT` | Markdown içerik `MEDIUMTEXT` |
| Hash | `CHAR(64)` | SHA-256 / HMAC-SHA256 hex |
| Şifreli değer | `TEXT` | Laravel `encrypted` cast (base64 payload) |
| Maskeli IP | `VARCHAR(64)` | `85.105.x.x`, `2a02:e0:12::/48` |

### 1.4 Ortak kolonlar
- `created_at`, `updated_at`: `DATETIME NULL` (Laravel `timestamps()` yerine `datetimes()` kullanılır; yoksa `dateTime('created_at')->nullable()`).
- Append-only tablolarda `updated_at` **yoktur**.
- `deleted_at` (soft delete) yalnızca belirtilen tablolarda bulunur.

### 1.5 Silme ve cascade politikası

| Kural | Uygulama |
|---|---|
| **Kullanıcılar hard-delete edilmez.** Hesap silme = anonimleştirme (§8). | `users` tablosuna referans veren FK'ler varsayılan olarak `RESTRICT` |
| Bileşen (composition) ilişkileri ebeveynle birlikte silinir | `CASCADE` (ör. `page_sections → pages`, `role_permissions → roles`) |
| İsteğe bağlı referanslar | `SET NULL` (ör. `assigned_to`, `cover_media_id`) |
| Finansal, audit ve moderasyon kayıtları | **Asla silinmez**. FK `RESTRICT`. Append-only trigger (§7) |
| Lookup/sözlük tabloları (kategori, tür) | Kullanımdaysa silinemez (`RESTRICT`). Pasifleştirme için `is_active=false` |

---

## 2. Laravel çerçeve tabloları

Laravel'in varsayılan migration'larıyla oluşturulur. Burada yalnızca projeye özel notlar vardır.

| Tablo | Not |
|---|---|
| `sessions` | `id VARCHAR(255) PK, user_id BIGINT UNSIGNED NULL INDEX, ip_address VARCHAR(45), user_agent TEXT, payload LONGTEXT, last_activity INT INDEX`. Oturum yönetimi ekranı (FR-AUTH-014) bu tabloyu okur. FK **yoktur** (Laravel davranışı); kullanıcı anonimleştirilince oturumları silinir |
| `password_reset_tokens` | `email PK, token (bcrypt hash), created_at`. Laravel broker tek kullanım ve süreyi yönetir |
| `cache`, `cache_locks` | Database cache store (geliştirme). Production'da Redis kullanılabilir |
| `jobs`, `job_batches`, `failed_jobs` | Database queue |
| `notifications` | `id CHAR(36) PK, type, notifiable_type, notifiable_id, data JSON, read_at DATETIME NULL, created_at, updated_at`. Index: `(notifiable_type, notifiable_id, read_at)` |

---

## 3. Kimlik, erişim ve personel

### 3.1 `users`
Web hesabı.

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | AI | |
| ulid | CHAR(26) | | | Route key |
| username | VARCHAR(20) | | | `[A-Za-z0-9_.]{3,20}`. ci collation sayesinde büyük/küçük harf duyarsız benzersiz |
| email | VARCHAR(254) | | | Küçük harfe normalize edilerek saklanır |
| email_verified_at | DATETIME | ✓ | | |
| password | VARCHAR(255) | | | Argon2id hash |
| status | VARCHAR(32) | | `active` | `active, inactive, suspended, pending_deletion, anonymized` |
| suspended_until | DATETIME | ✓ | | `NULL` + `suspended` = süresiz |
| suspension_reason | VARCHAR(500) | ✓ | | Kullanıcıya gösterilen gerekçe |
| two_factor_secret | TEXT | ✓ | | Şifreli (Fortify) |
| two_factor_recovery_codes | TEXT | ✓ | | Şifreli (Fortify) |
| two_factor_confirmed_at | DATETIME | ✓ | | |
| remember_token | VARCHAR(100) | ✓ | | |
| theme | VARCHAR(16) | | `system` | `system, dark, light` |
| reduced_effects | BOOLEAN | | 0 | |
| locale | VARCHAR(8) | | `tr` | |
| last_login_at | DATETIME | ✓ | | |
| deletion_requested_at | DATETIME | ✓ | | 14 gün sonra anonimleştirme |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_users_ulid`, `uq_users_username`, `uq_users_email`.
- Index: `idx_users_status`, `idx_users_created_at`, `idx_users_last_login_at`.

### 3.2 `roles`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| slug | VARCHAR(64) | | | `super_admin, admin, …` (bkz. [ADMIN §2](ADMIN.md)) |
| name | VARCHAR(100) | | | Görünen ad |
| description | VARCHAR(500) | ✓ | | |
| color | VARCHAR(16) | ✓ | | Rozet rengi token adı (`accent`, `amber`…) |
| priority | SMALLINT UNSIGNED | | 0 | Hiyerarşi. Yüksek = güçlü. `super_admin=100` |
| is_staff | BOOLEAN | | 1 | `false` ise rol kozmetiktir (ör. "Destekçi" rozeti), `/admin` açmaz |
| is_system | BOOLEAN | | 0 | Sistem rolleri silinemez ve slug'ı değiştirilemez |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_roles_slug`.

### 3.3 `permissions`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| key | VARCHAR(100) | | `users.read` gibi. Kaynağı `config/permissions.php`. Seeder senkronlar |
| group | VARCHAR(50) | | ACP'de gruplama (`users`, `support`…) |
| description | VARCHAR(255) | | |
| is_sensitive | BOOLEAN | | Atanırken ek onay ister |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_permissions_key`.

### 3.4 `role_permissions` (pivot)
`role_id` FK → roles **CASCADE**, `permission_id` FK → permissions **CASCADE**, `created_at`. PK: `(role_id, permission_id)`.

### 3.5 `user_roles`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| user_id | BIGINT UNSIGNED | | FK → users **RESTRICT** |
| role_id | BIGINT UNSIGNED | | FK → roles **RESTRICT** (rol kullanımdayken silinemez) |
| assigned_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| expires_at | DATETIME | ✓ | Geçici rol. Zamanlayıcı süresi dolanı kaldırır ve audit yazar |
| created_at | DATETIME | ✓ | |

- Unique: `uq_user_roles_user_role (user_id, role_id)`. Index: `idx_user_roles_expires_at`.

### 3.6 `branches` (Branş / birim)

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| slug | VARCHAR(64) | | | `destek-genel`, `moderasyon`, `fraksiyon-lspd` |
| name | VARCHAR(100) | | | |
| type | VARCHAR(32) | | | `support, moderation, faction, content, economy, game, general` |
| faction_id | BIGINT UNSIGNED | ✓ | | FK → factions **SET NULL** (`type=faction` için) |
| parent_id | BIGINT UNSIGNED | ✓ | | FK → branches **SET NULL** (escalation hedefi) |
| description | VARCHAR(500) | ✓ | | |
| is_active | BOOLEAN | | 1 | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_branches_slug`. Index: `idx_branches_type`.

### 3.7 `branch_members`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| branch_id | BIGINT UNSIGNED | | | FK → branches **CASCADE** |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| is_lead | BOOLEAN | | 0 | Branş lideri: üye ekleyip çıkarabilir (`branches.assign_members`) |
| assigned_by | BIGINT UNSIGNED | ✓ | | FK → users **SET NULL** |
| expires_at | DATETIME | ✓ | | |
| created_at | DATETIME | ✓ | | |

- Unique: `uq_branch_members (branch_id, user_id)`. Index: `idx_branch_members_user (user_id)`.

### 3.8 `user_security_events`
Append-only. Saklama 180 gün.

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| user_id | BIGINT UNSIGNED | ✓ | FK → users **CASCADE** (anonimleştirmede silinir) |
| event | VARCHAR(48) | | `login_success, login_failed, logout, password_changed, password_reset, email_change_requested, email_changed, two_factor_enabled, two_factor_disabled, recovery_code_used, sessions_revoked, suspicious_login, account_linked, account_unlinked` |
| identifier_hash | CHAR(64) | ✓ | Başarısız girişte denenen tanımlayıcının HMAC'i |
| ip_hash | CHAR(64) | ✓ | HMAC(IP) |
| ip_masked | VARCHAR(64) | ✓ | |
| user_agent_summary | VARCHAR(120) | ✓ | "Chrome 142 · Windows" |
| metadata | JSON | ✓ | Olay detayları (hassas veri yok) |
| created_at | DATETIME | | |

- Index: `idx_use_user_created (user_id, created_at)`, `idx_use_event_created (event, created_at)`, `idx_use_ip_hash_created (ip_hash, created_at)`.

### 3.9 `email_change_requests`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| user_id | BIGINT UNSIGNED | | FK → users **CASCADE** |
| new_email | VARCHAR(254) | | |
| token_hash | CHAR(64) | | SHA-256 |
| expires_at | DATETIME | | +60 dk |
| confirmed_at | DATETIME | ✓ | |
| cancelled_at | DATETIME | ✓ | |
| created_at | DATETIME | ✓ | |

- Unique: `uq_ecr_token_hash`. Index: `idx_ecr_user (user_id)`.

### 3.10 `consents`
Append-only rıza/onay geçmişi. Son kayıt geçerlidir.

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| user_id | BIGINT UNSIGNED | ✓ | FK → users **RESTRICT**. Misafir çerez tercihi için `NULL` + `anon_id` |
| anon_id | CHAR(26) | ✓ | Misafir çerez kimliği |
| type | VARCHAR(32) | | `terms, kvkk_ack, cookies_analytics, marketing_email, whatsapp_optin, rules_ack` |
| version | VARCHAR(32) | | Metin sürümü (`2026-11-01`) |
| granted | BOOLEAN | | |
| ip_masked | VARCHAR(64) | ✓ | |
| created_at | DATETIME | | |

- Index: `idx_consents_user_type (user_id, type, created_at)`.

### 3.11 `user_notification_preferences`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| user_id | BIGINT UNSIGNED | | | FK → users **CASCADE** |
| category | VARCHAR(32) | | | `support, applications, announcements, payments, sanctions` (security tercihe kapalıdır) |
| channel | VARCHAR(32) | | | `mail, panel, discord, whatsapp` |
| enabled | BOOLEAN | | 1 | |
| updated_at | DATETIME | ✓ | | |

- PK: `(user_id, category, channel)`.

### 3.12 `user_contact_channels` (R2/R3)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| user_id | BIGINT UNSIGNED | | FK → users **CASCADE** |
| channel | VARCHAR(32) | | `whatsapp, discord` |
| address | TEXT | | Şifreli (E.164 telefon / Discord kullanıcı ID) |
| address_hash | CHAR(64) | | HMAC: benzersizlik ve arama için |
| verified_at | DATETIME | ✓ | |
| opted_in_at | DATETIME | ✓ | |
| opted_out_at | DATETIME | ✓ | |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_ucc_channel_hash (channel, address_hash)`, `uq_ucc_user_channel (user_id, channel)`.

---

## 4. Sistem ayarları ve entegrasyon

### 4.1 `system_settings`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| key | VARCHAR(100) PK | | `site.name`, `server.connect_host`, `features.payments` |
| group | VARCHAR(50) | | `site, server, features, accounts, wallet, mail, social, maintenance, rules` |
| value | JSON | ✓ | Tipli değer |
| type | VARCHAR(16) | | `string, int, bool, json, url, secret` |
| is_public | BOOLEAN | | Frontend'e (Blade global) paylaşılır |
| is_secret | BOOLEAN | | `value` şifreli tutulur, asla gösterilmez |
| updated_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| updated_at | DATETIME | ✓ | |

Ayarlar `SettingsRepository` ile okunur (cache, tag `settings`, değişiklikte temizlenir). Varsayılan anahtar listesi: [ADMIN §7](ADMIN.md).

### 4.2 `mta_servers`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| slug | VARCHAR(64) | | | `main` |
| name | VARCHAR(100) | | | |
| connect_host | VARCHAR(255) | | | Public bağlantı adresi (IP veya alan adı) |
| connect_port | SMALLINT UNSIGNED | | 22003 | |
| max_players | SMALLINT UNSIGNED | ✓ | | Son heartbeat'ten |
| game_mode | VARCHAR(100) | ✓ | | |
| map_name | VARCHAR(100) | ✓ | | |
| last_heartbeat_at | DATETIME | ✓ | | |
| last_player_count | SMALLINT UNSIGNED | ✓ | | |
| resource_version | VARCHAR(32) | ✓ | | `web_bridge` sürümü |
| is_maintenance | BOOLEAN | | 0 | Admin bayrağı |
| maintenance_note | VARCHAR(255) | ✓ | | |
| is_primary | BOOLEAN | | 0 | Public sayfada gösterilen sunucu |
| is_active | BOOLEAN | | 1 | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_mta_servers_slug`.

### 4.3 `integration_credentials`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| mta_server_id | BIGINT UNSIGNED | | FK → mta_servers **CASCADE** |
| key_id | VARCHAR(32) | | Public anahtar kimliği (`mrp_live_8f3a…`). `X-Bridge-Key` başlığında gönderilir |
| secret | TEXT | | Şifreli. Oluşturma anında bir kez gösterilir |
| scopes | JSON | | `["heartbeat","events","account_link","commands"]` |
| last_used_at | DATETIME | ✓ | |
| expires_at | DATETIME | ✓ | |
| revoked_at | DATETIME | ✓ | |
| created_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| created_at | DATETIME | ✓ | |

- Unique: `uq_ic_key_id`. Index: `idx_ic_server_revoked (mta_server_id, revoked_at)`.

### 4.4 `bridge_nonces`

| Kolon | Tip | Açıklama |
|---|---|---|
| nonce | CHAR(64) PK | Hex nonce. PK çakışması = replay |
| credential_id | BIGINT UNSIGNED | FK → integration_credentials **CASCADE** |
| created_at | DATETIME | Index. 15 dk sonra `bridge:prune-nonces` ile silinir |

### 4.5 `server_heartbeats`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| mta_server_id | BIGINT UNSIGNED | | FK → mta_servers **CASCADE** |
| player_count | SMALLINT UNSIGNED | | |
| max_players | SMALLINT UNSIGNED | | |
| payload | JSON | ✓ | `uptime_sec, map, game_mode, resource_version` |
| received_at | DATETIME | | |

- Index: `idx_sh_server_received (mta_server_id, received_at)`. Saklama 30 gün.

### 4.6 `server_stats_daily`

| Kolon | Tip | Açıklama |
|---|---|---|
| mta_server_id | BIGINT UNSIGNED | FK → mta_servers **CASCADE** |
| date | DATE | Europe/Istanbul gününe göre |
| peak_players | SMALLINT UNSIGNED | |
| avg_players | DECIMAL(6,2) | Para değil; istatistik |
| uptime_ratio | DECIMAL(5,4) | Taze heartbeat oranı (M7) |
| unique_accounts | INT UNSIGNED | `player.connected` olaylarından |

- PK: `(mta_server_id, date)`.

---

## 5. MTA hesapları, karakterler ve köprü olayları

### 5.1 `mta_accounts`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| mta_server_id | BIGINT UNSIGNED | | FK → mta_servers **RESTRICT** |
| mta_account_ref | VARCHAR(64) | | Oyun tarafındaki kalıcı hesap kimliği (OD-02) |
| account_name | VARCHAR(64) | | Oyun içi kullanıcı adı |
| last_seen_at | DATETIME | ✓ | `player.disconnected`/heartbeat |
| synced_at | DATETIME | ✓ | |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_mta_accounts_ulid`, `uq_mta_accounts_ref (mta_server_id, mta_account_ref)`. Index: `idx_mta_accounts_name (account_name)`.

### 5.2 `game_account_links`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| mta_account_id | BIGINT UNSIGNED | | | FK → mta_accounts **RESTRICT** |
| status | VARCHAR(32) | | `active` | `active, unlink_requested, unlinked, revoked` |
| verification_method | VARCHAR(32) | | | `ingame_code, admin_manual` |
| active_mta_account_id | BIGINT UNSIGNED | ✓ | *generated* | `AS (IF(status IN ('active','unlink_requested'), mta_account_id, NULL)) STORED` |
| linked_at | DATETIME | | | |
| unlink_requested_at | DATETIME | ✓ | | |
| unlink_reason | VARCHAR(500) | ✓ | | Oyuncunun gerekçesi |
| unlinked_at | DATETIME | ✓ | | |
| unlinked_by | BIGINT UNSIGNED | ✓ | | FK → users **SET NULL** |
| created_at / updated_at | DATETIME | ✓ | | |

- **Unique: `uq_gal_active_account (active_mta_account_id)`** → bir oyun hesabı aynı anda yalnızca bir aktif bağlantıya sahip olabilir (NULL'lar unique'i bozmaz). Bu, MariaDB/MySQL'de partial index olmamasının standart çözümüdür.
- Index: `idx_gal_user_status (user_id, status)`.

### 5.3 `game_link_codes`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| user_id | BIGINT UNSIGNED | | | FK → users **CASCADE** |
| purpose | VARCHAR(32) | | `account_link` | `account_link, device_link` |
| code_hash | CHAR(64) | | | HMAC-SHA256 (normalize edilmiş kod) |
| mta_server_id | BIGINT UNSIGNED | ✓ | | FK → mta_servers **CASCADE**. NULL = herhangi bir sunucu |
| target_mta_account_id | BIGINT UNSIGNED | ✓ | | `device_link` için hedef hesap |
| attempts | TINYINT UNSIGNED | | 0 | Hatalı deneme. 5'te iptal |
| expires_at | DATETIME | | | +10 dk |
| used_at | DATETIME | ✓ | | |
| used_by_mta_account_id | BIGINT UNSIGNED | ✓ | | FK → mta_accounts **SET NULL** |
| cancelled_at | DATETIME | ✓ | | |
| created_at | DATETIME | ✓ | | |

- Unique: `uq_glc_code_hash`. Index: `idx_glc_user_purpose (user_id, purpose, used_at, cancelled_at)`, `idx_glc_expires (expires_at)`.

### 5.4 `characters`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | |
| mta_server_id | BIGINT UNSIGNED | | | FK → mta_servers **RESTRICT** |
| mta_account_id | BIGINT UNSIGNED | | | FK → mta_accounts **RESTRICT** |
| mta_character_ref | VARCHAR(64) | | | Oyun içi karakter ID |
| name | VARCHAR(64) | | | "Ad Soyad" |
| status | VARCHAR(32) | | `active` | `active, inactive, dead, banned` |
| faction_id | BIGINT UNSIGNED | ✓ | | FK → factions **SET NULL** (web fraksiyon eşlemesi) |
| game_job_id | BIGINT UNSIGNED | ✓ | | FK → game_jobs **SET NULL** |
| level | SMALLINT UNSIGNED | ✓ | | |
| playtime_minutes | INT UNSIGNED | ✓ | | |
| public_profile | JSON | ✓ | | Allowlist alanlar (`config/roleplay.php`). Oyun içi para buradaysa salt okunurdur |
| is_profile_hidden | BOOLEAN | | 0 | `characters.review` yetkilisi web'de profil alanlarını gizleyebilir (brief §7.5). Gizliyse yalnızca ad ve durum gösterilir |
| last_seen_at | DATETIME | ✓ | | |
| created_in_game_at | DATETIME | ✓ | | |
| synced_at | DATETIME | | | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_characters_ulid`, `uq_characters_ref (mta_server_id, mta_character_ref)`. Index: `idx_characters_account (mta_account_id)`, `idx_characters_name (name)`.

### 5.5 `mta_account_devices` (R2, serial)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| mta_account_id | BIGINT UNSIGNED | | FK → mta_accounts **RESTRICT** |
| serial_hmac | CHAR(64) | | HMAC(serial, `BRIDGE_SERIAL_PEPPER`). Ham serial saklanmaz (OD-07) |
| label | VARCHAR(64) | ✓ | "Cihaz 2" (kullanıcının verdiği ad) |
| status | VARCHAR(32) | | `pending_approval, active, revoked` |
| link_code_id | BIGINT UNSIGNED | ✓ | FK → game_link_codes **SET NULL** |
| approved_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| approved_at / revoked_at | DATETIME | ✓ | |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_mad_account_serial (mta_account_id, serial_hmac)`.

### 5.6 `game_events`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| mta_server_id | BIGINT UNSIGNED | | FK → mta_servers **CASCADE** |
| event_id | CHAR(36) | | MTA'nın ürettiği UUID (idempotency) |
| type | VARCHAR(48) | | `player.connected, player.disconnected, character.synced, account.synced, wallet.event` |
| mta_account_id | BIGINT UNSIGNED | ✓ | FK → mta_accounts **SET NULL** |
| payload | JSON | ✓ | Sanitize edilmiş. Serial/IP içermez |
| status | VARCHAR(16) | | `received, processed, failed, ignored` |
| error | VARCHAR(500) | ✓ | |
| occurred_at | DATETIME | | Oyun tarafındaki zaman |
| received_at | DATETIME | | |
| processed_at | DATETIME | ✓ | |

- Unique: `uq_game_events (mta_server_id, event_id)`. Index: `idx_ge_type_received (type, received_at)`, `idx_ge_account (mta_account_id, occurred_at)`.

### 5.7 `game_commands`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | Köprüye giden komut kimliği |
| mta_server_id | BIGINT UNSIGNED | | | FK → mta_servers **RESTRICT** |
| type | VARCHAR(32) | | | Allowlist: `player.warn, player.kick, player.ban, player.unban, player.jail, player.mute, server.announce, account.link_revoked` |
| target_mta_account_id | BIGINT UNSIGNED | ✓ | | FK → mta_accounts **RESTRICT** |
| payload | JSON | | | Türe özgü, şema doğrulamalı parametreler |
| idempotency_key | VARCHAR(100) | | | |
| status | VARCHAR(16) | | `pending` | `pending, dispatched, succeeded, failed, expired, cancelled` |
| attempts | TINYINT UNSIGNED | | 0 | Max 3 |
| result | JSON | ✓ | | Köprünün döndüğü sonuç |
| requested_by | BIGINT UNSIGNED | ✓ | | FK → users **RESTRICT** |
| source_type / source_id | VARCHAR(64) / BIGINT UNSIGNED | ✓ | | Polimorfik (ör. `sanction`) |
| dispatched_at / completed_at | DATETIME | ✓ | | |
| expires_at | DATETIME | | | +1 saat |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_gc_ulid`, `uq_gc_idempotency`. Index: `idx_gc_server_status (mta_server_id, status, created_at)`.

---

## 6. Ekonomi ve ödeme

### 6.1 `wallet_types`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| key | VARCHAR(32) | | `web_credit`, `premium_points` (OD-03) |
| name | VARCHAR(64) | | "Meridyen Kredisi" |
| unit_label | VARCHAR(16) | | "MK" |
| is_purchasable | BOOLEAN | | Ödemeyle yüklenebilir mi |
| is_active | BOOLEAN | | |
| sort | SMALLINT | | |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_wallet_types_key`.

### 6.2 `wallets`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| wallet_type_id | BIGINT UNSIGNED | | | FK → wallet_types **RESTRICT** |
| balance | BIGINT | | 0 | **Yalnızca `LedgerService` yazar**. `CHECK (balance >= 0)` |
| version | INT UNSIGNED | | 0 | Her post'ta +1 |
| status | VARCHAR(16) | | `active` | `active, frozen` (frozen: debit yasak) |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_wallets_user_type (user_id, wallet_type_id)`.

### 6.3 `wallet_transactions` (ledger — append-only)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | Kullanıcıya gösterilen işlem no |
| wallet_id | BIGINT UNSIGNED | | FK → wallets **RESTRICT** |
| direction | VARCHAR(8) | | `credit, debit`. `CHECK (direction IN ('credit','debit'))` |
| amount | BIGINT | | `CHECK (amount > 0)` |
| balance_before | BIGINT | | |
| balance_after | BIGINT | | `CHECK (balance_after >= 0)` |
| reason | VARCHAR(32) | | `topup, purchase, admin_adjustment, refund, reversal, promo, game_sync` |
| source_type | VARCHAR(64) | ✓ | `payment_order, wallet_adjustment_request, refund…` |
| source_id | BIGINT UNSIGNED | ✓ | |
| reversal_of_id | BIGINT UNSIGNED | ✓ | FK → wallet_transactions **RESTRICT** |
| idempotency_key | VARCHAR(100) | | |
| description | VARCHAR(255) | | Kullanıcıya gösterilen açıklama |
| created_by | BIGINT UNSIGNED | ✓ | FK → users **RESTRICT** (sistem işlemi = NULL) |
| created_at | DATETIME | | |

- Unique: `uq_wt_ulid`, `uq_wt_idempotency`, `uq_wt_reversal_of (reversal_of_id)` (bir kayıt yalnızca bir kez ters çevrilebilir).
- Index: `idx_wt_wallet_created (wallet_id, created_at)`, `idx_wt_source (source_type, source_id)`.
- Trigger: §7.

### 6.4 `payment_packages`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| wallet_type_id | BIGINT UNSIGNED | | FK → wallet_types **RESTRICT** |
| name | VARCHAR(64) | | |
| credit_amount | BIGINT | | |
| bonus_amount | BIGINT | | 0 |
| price_minor | BIGINT | | `CHECK (price_minor > 0)` |
| currency | CHAR(3) | | `TRY` |
| is_active | BOOLEAN | | |
| sort | SMALLINT | | |
| created_at / updated_at | DATETIME | ✓ | |

### 6.5 `payment_orders`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | |
| order_number | VARCHAR(32) | | | `MRP-20261111-7K2Q` |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| wallet_id | BIGINT UNSIGNED | | | FK → wallets **RESTRICT** |
| payment_package_id | BIGINT UNSIGNED | ✓ | | FK → payment_packages **RESTRICT** |
| provider | VARCHAR(32) | | | `fake, iyzico, paytr, stripe…` |
| provider_order_id | VARCHAR(128) | ✓ | | |
| amount_minor | BIGINT | | | Sunucuda paketten hesaplanır |
| currency | CHAR(3) | | | |
| credit_amount | BIGINT | | | Kredi + bonus anlık görüntüsü |
| status | VARCHAR(24) | | `created` | `created, pending, requires_action, succeeded, failed, cancelled, refunded, partially_refunded, disputed` |
| refunded_minor | BIGINT | | 0 | |
| idempotency_key | VARCHAR(100) | | | Checkout formu |
| failure_reason | VARCHAR(255) | ✓ | | |
| expires_at | DATETIME | ✓ | | |
| paid_at | DATETIME | ✓ | | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_po_ulid`, `uq_po_order_number`, `uq_po_idempotency`, `uq_po_provider_order (provider, provider_order_id)`.
- Index: `idx_po_user_created (user_id, created_at)`, `idx_po_status_created (status, created_at)`.
- `CHECK (status IN (...))`.

### 6.6 `payment_events` (webhook gelen kutusu)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| provider | VARCHAR(32) | | |
| provider_event_id | VARCHAR(128) | | |
| payment_order_id | BIGINT UNSIGNED | ✓ | FK → payment_orders **RESTRICT** |
| event_type | VARCHAR(64) | | Normalize edilmiş: `payment.succeeded, payment.failed, refund.succeeded…` |
| signature_valid | BOOLEAN | | |
| payload | JSON | | Kişisel/kart verisi temizlenmiş |
| status | VARCHAR(16) | | `received, processed, ignored, failed` |
| error | VARCHAR(500) | ✓ | |
| received_at | DATETIME | | |
| processed_at | DATETIME | ✓ | |

- Unique: `uq_pe_provider_event (provider, provider_event_id)` → aynı webhook ikinci kez işlenemez.

### 6.7 `refunds`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| payment_order_id | BIGINT UNSIGNED | | FK → payment_orders **RESTRICT** |
| amount_minor | BIGINT | | `CHECK (amount_minor > 0)` |
| reason | VARCHAR(500) | | |
| status | VARCHAR(24) | | `requested, approved, processing, succeeded, failed, rejected, requires_review` |
| provider_refund_id | VARCHAR(128) | ✓ | |
| wallet_transaction_id | BIGINT UNSIGNED | ✓ | FK → wallet_transactions **RESTRICT** |
| requested_by | BIGINT UNSIGNED | | FK → users **RESTRICT** |
| approved_by | BIGINT UNSIGNED | ✓ | FK → users **RESTRICT**. `≠ requested_by` (servis kontrolü) |
| approved_at / completed_at | DATETIME | ✓ | |
| created_at / updated_at | DATETIME | ✓ | |

### 6.8 `wallet_adjustment_requests`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| wallet_id | BIGINT UNSIGNED | | FK → wallets **RESTRICT** |
| direction | VARCHAR(8) | | `credit, debit` |
| amount | BIGINT | | `CHECK (amount > 0)` |
| reason | VARCHAR(1000) | | ≥ 20 karakter |
| status | VARCHAR(16) | | `pending, approved, rejected, applied, cancelled` |
| requires_second_approval | BOOLEAN | | Eşik üstü mü (talep anında hesaplanır) |
| reversal_of_id | BIGINT UNSIGNED | ✓ | FK → wallet_transactions **RESTRICT**. Ters kayıt talebi (`admin.economy.reversal-request`) ise ters çevrilecek kayıt (Faz 9) |
| requested_by | BIGINT UNSIGNED | | FK → users **RESTRICT** |
| reviewed_by | BIGINT UNSIGNED | ✓ | FK → users **RESTRICT** |
| reviewed_at | DATETIME | ✓ | |
| review_note | VARCHAR(1000) | ✓ | |
| wallet_transaction_id | BIGINT UNSIGNED | ✓ | FK → wallet_transactions **RESTRICT** |
| created_at / updated_at | DATETIME | ✓ | |

### 6.9 `wallet_reconciliation_runs` (Faz 9)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| triggered_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** (zamanlayıcı = NULL) |
| wallets_checked | INT UNSIGNED | | |
| mismatches | INT UNSIGNED | | |
| total_balance | BIGINT | | `SUM(wallets.balance)` |
| ledger_net | BIGINT | | `SUM(credit) - SUM(debit)` |
| details | JSON | ✓ | Fark bulunan cüzdanlar: `[{wallet_id, balance, ledger_net}]` (en fazla 100) |
| started_at / finished_at | DATETIME | | |

- Index: `idx_wrr_started (started_at)`. FR-WAL-007 raporu ACP'de bu tablodan okunur; fark varsa `wallet.reconciliation_mismatch` (critical) audit kaydı.

---

## 7. Append-only koruması (trigger)

`wallet_transactions`, `audit_logs`, `consents`, `application_status_history` tablolarında UPDATE ve DELETE engellenir. Migration'da `DB::unprepared()` ile oluşturulur:

```sql
CREATE TRIGGER trg_wallet_transactions_no_update
BEFORE UPDATE ON wallet_transactions FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'wallet_transactions is append-only';

CREATE TRIGGER trg_wallet_transactions_no_delete
BEFORE DELETE ON wallet_transactions FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'wallet_transactions is append-only';
```

- `audit_logs` saklama süresi dolduğunda (5 yıl) arşivleme, trigger'ı geçici olarak kaldıran ayrı bir **DBA prosedürüyle** yapılır. Uygulama kullanıcısının `TRIGGER` yetkisi production'da yoktur.
- Production DB kullanıcısı: `SELECT, INSERT, UPDATE, DELETE` (+ migration için ayrı kullanıcı: `ALTER, CREATE, DROP, INDEX, REFERENCES, TRIGGER`).

---

## 8. Destek

### 8.1 `support_categories`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| slug | VARCHAR(64) | | | |
| name | VARCHAR(100) | | | "Hesap ve giriş", "Hata bildirimi", "Oyuncu şikayeti"… |
| description | VARCHAR(255) | ✓ | | Form ipucu |
| branch_id | BIGINT UNSIGNED | | | FK → branches **RESTRICT** (yönlendirme) |
| default_priority | VARCHAR(16) | | `normal` | |
| requires_linked_account | BOOLEAN | | 0 | |
| is_active | BOOLEAN | | 1 | |
| sort | SMALLINT | | 0 | |
| created_at / updated_at | DATETIME | ✓ | | |

### 8.2 `support_tickets`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | Route key |
| number | VARCHAR(16) | | | `T-000123` |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| category_id | BIGINT UNSIGNED | | | FK → support_categories **RESTRICT** |
| branch_id | BIGINT UNSIGNED | | | FK → branches **RESTRICT** (kategoriden kopyalanır, escalation ile değişir) |
| character_id | BIGINT UNSIGNED | ✓ | | FK → characters **SET NULL** |
| subject | VARCHAR(120) | | | |
| status | VARCHAR(24) | | `open` | `open, in_progress, waiting_for_player, waiting_for_staff, resolved, closed` |
| priority | VARCHAR(16) | | `normal` | `low, normal, high, urgent` |
| assigned_to | BIGINT UNSIGNED | ✓ | | FK → users **SET NULL** |
| idempotency_key | CHAR(36) | | | |
| last_message_at | DATETIME | ✓ | | |
| last_player_message_at | DATETIME | ✓ | | |
| last_staff_message_at | DATETIME | ✓ | | |
| first_staff_response_at | DATETIME | ✓ | | M5 metriği |
| player_unread | BOOLEAN | | 0 | |
| staff_unread | BOOLEAN | | 1 | |
| resolved_at / closed_at | DATETIME | ✓ | | |
| closed_by | BIGINT UNSIGNED | ✓ | | FK → users **SET NULL** |
| reopen_requested_at | DATETIME | ✓ | | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_st_ulid`, `uq_st_number`, `uq_st_user_idempotency (user_id, idempotency_key)`.
- Index: `idx_st_queue (branch_id, status, priority, last_message_at)`, `idx_st_user (user_id, status, updated_at)`, `idx_st_assigned (assigned_to, status)`.
- `number` üretimi: `support_ticket_sequence` (tek satırlık sayaç tablosu, `UPDATE … SET n = LAST_INSERT_ID(n+1)`) ile yarış koşulu olmadan yapılır.

### 8.3 `support_messages`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ticket_id | BIGINT UNSIGNED | | | FK → support_tickets **CASCADE** |
| author_id | BIGINT UNSIGNED | ✓ | | FK → users **RESTRICT** (sistem mesajı = NULL) |
| author_type | VARCHAR(16) | | | `player, staff, system` |
| visibility | VARCHAR(16) | | `public` | `public, internal`. Oyuncu sorguları **daima** `public` filtreler |
| body | TEXT | | | Markdown (sanitize edilerek render) |
| edited_at | DATETIME | ✓ | | Yalnızca 5 dk içinde, yalnızca yazar |
| created_at | DATETIME | | | |

- Index: `idx_sm_ticket_created (ticket_id, created_at)`, `idx_sm_ticket_visibility (ticket_id, visibility)`.

### 8.4 `support_attachments`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | İndirme route key |
| ticket_id | BIGINT UNSIGNED | | FK → support_tickets **CASCADE** |
| message_id | BIGINT UNSIGNED | | FK → support_messages **CASCADE** (eki görme yetkisi mesaj görünürlüğünü izler) |
| uploaded_by | BIGINT UNSIGNED | | FK → users **RESTRICT** |
| disk | VARCHAR(32) | | `private` |
| path | VARCHAR(255) | | `support/2026/11/{ulid}.webp` |
| original_name | VARCHAR(255) | | Gösterim için temizlenmiş ad |
| mime | VARCHAR(100) | | finfo ile tespit edilen |
| size_bytes | INT UNSIGNED | | |
| sha256 | CHAR(64) | | |
| scan_status | VARCHAR(16) | | `pending, clean, infected, skipped` |
| created_at | DATETIME | | |

### 8.5 `support_ticket_events`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ticket_id | BIGINT UNSIGNED | | FK → support_tickets **CASCADE** |
| actor_id | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| type | VARCHAR(32) | | `created, status_changed, assigned, unassigned, priority_changed, escalated, reopen_requested, reopened, auto_resolved, auto_closed` |
| from_value / to_value | VARCHAR(64) | ✓ | |
| note | VARCHAR(500) | ✓ | Yalnızca personel görür |
| created_at | DATETIME | | |

### 8.6 `support_macros`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| title | VARCHAR(100) | | |
| body | TEXT | | Şablon motoru söz dizimi ([CMS §6](CMS.md)) |
| category_id | BIGINT UNSIGNED | ✓ | FK → support_categories **SET NULL** |
| branch_id | BIGINT UNSIGNED | ✓ | FK → branches **CASCADE** (NULL = herkes) |
| is_active | BOOLEAN | | |
| created_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| created_at / updated_at | DATETIME | ✓ | |

---

## 9. Başvurular (R2)

### 9.1 `application_types`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| slug | VARCHAR(64) | | | |
| name | VARCHAR(100) | | | |
| kind | VARCHAR(32) | | | `faction, staff, special_role, event, whitelist` |
| form_key | VARCHAR(64) | | | Sabit form şeması (`faction_default`, `staff_v1`) |
| description | TEXT | ✓ | | |
| faction_id | BIGINT UNSIGNED | ✓ | | FK → factions **SET NULL** |
| branch_id | BIGINT UNSIGNED | | | FK → branches **RESTRICT** (inceleyenler) |
| is_open | BOOLEAN | | 0 | |
| requires_linked_account | BOOLEAN | | 1 | |
| requires_character | BOOLEAN | | 0 | |
| min_account_age_days | SMALLINT UNSIGNED | | 0 | |
| cooldown_days | SMALLINT UNSIGNED | | 14 | Ret sonrası bekleme |
| sort | SMALLINT | | 0 | |
| created_at / updated_at | DATETIME | ✓ | | |

### 9.2 `applications`

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | |
| user_id | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| application_type_id | BIGINT UNSIGNED | | | FK → application_types **RESTRICT** |
| character_id | BIGINT UNSIGNED | ✓ | | FK → characters **SET NULL** |
| status | VARCHAR(16) | | `draft` | `draft, submitted, in_review, needs_info, approved, rejected, cancelled` |
| open_key | VARCHAR(64) | ✓ | *generated* | `AS (IF(status IN ('draft','submitted','in_review','needs_info'), CONCAT(user_id,':',application_type_id), NULL)) STORED` |
| assigned_to | BIGINT UNSIGNED | ✓ | | FK → users **SET NULL** |
| submitted_at / decided_at | DATETIME | ✓ | | |
| decided_by | BIGINT UNSIGNED | ✓ | | FK → users **RESTRICT** |
| decision_note_public | VARCHAR(2000) | ✓ | | |
| created_at / updated_at | DATETIME | ✓ | | |

- Unique: `uq_applications_ulid`, **`uq_applications_open_key (open_key)`** (tür başına tek açık başvuru).
- Index: `idx_app_type_status (application_type_id, status, submitted_at)`, `idx_app_user (user_id, created_at)`.

### 9.3 `application_answers`
`id`, `application_id` FK **CASCADE**, `field_key VARCHAR(64)`, `question_label VARCHAR(255)` (anlık kopya), `answer TEXT`, `created_at/updated_at`. Unique: `(application_id, field_key)`.

### 9.4 `application_messages`
`id`, `application_id` FK **CASCADE**, `author_id` FK users **RESTRICT** (NULL=sistem), `visibility VARCHAR(16)` (`public, internal`), `body TEXT`, `created_at`. Index: `(application_id, created_at)`.

### 9.5 `application_status_history` (append-only)
`id`, `application_id` FK **RESTRICT**, `from_status`, `to_status`, `actor_id` FK users **RESTRICT** (NULL=sistem), `note_public VARCHAR(2000) NULL`, `note_internal VARCHAR(2000) NULL`, `created_at`.

---

## 10. İçerik (CMS)

Tüm yayınlanabilir içeriklerde ortak alanlar (**"yayın blok"**):
`status VARCHAR(16) DEFAULT 'draft'` (`draft, in_review, scheduled, published, archived`), `published_at DATETIME NULL`, `sort SMALLINT DEFAULT 0`, `seo_title VARCHAR(70) NULL`, `meta_description VARCHAR(170) NULL`, `created_by` / `updated_by` FK users **SET NULL**, `created_at`, `updated_at`. Index: `(status, published_at)`.

### 10.1 `media`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| disk | VARCHAR(32) | | `public` |
| path | VARCHAR(255) | | Orijinal (yeniden kodlanmış) |
| variants | JSON | | `{"480":"…webp","960":"…","1600":"…"}` |
| original_name | VARCHAR(255) | | |
| mime | VARCHAR(100) | | |
| width / height | SMALLINT UNSIGNED | ✓ | |
| size_bytes | INT UNSIGNED | | |
| alt_text | VARCHAR(255) | ✓ | |
| is_decorative | BOOLEAN | | Dekoratifse `alt=""` |
| uploaded_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| created_at / updated_at | DATETIME | ✓ | |

Medyaya referans veren FK'ler (`*_media_id`) **SET NULL** davranışlıdır. Silme işlemi kullanım kontrolü yapar ve kullanımdaysa reddeder.

### 10.2 `pages`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| slug | VARCHAR(120) | | `home, about, privacy, kvkk, terms, cookies, refund-policy` |
| title | VARCHAR(150) | | |
| template | VARCHAR(32) | | `home, standard, legal, landing` |
| body | MEDIUMTEXT | ✓ | `standard/legal` için Markdown |
| version_label | VARCHAR(32) | ✓ | Yasal metin sürümü (`2026-11-01`) |
| effective_at | DATETIME | ✓ | Yasal metin yürürlük tarihi |
| og_media_id | BIGINT UNSIGNED | ✓ | FK → media **SET NULL** |
| is_system | BOOLEAN | | Sistem sayfaları silinemez ve slug'ı değiştirilemez |
| *yayın blok* | | | |
| deleted_at | DATETIME | ✓ | Soft delete |

- Unique: `uq_pages_slug`.

### 10.3 `page_sections`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| page_id | BIGINT UNSIGNED | | FK → pages **CASCADE** |
| type | VARCHAR(48) | | Sistem şablonu anahtarı ([CMS §4](CMS.md)): `hero, server_status, feature_grid, world_gallery, onboarding_steps, community, news_latest, events_upcoming, faq_list, cta_banner, rich_text` |
| data | JSON | | Şablonun JSON şemasına göre doğrulanır |
| sort | SMALLINT | | |
| is_visible | BOOLEAN | | |
| created_at / updated_at | DATETIME | ✓ | |

- Index: `idx_ps_page_sort (page_id, sort)`.

### 10.4 `page_revisions`
`id`, `page_id` FK **CASCADE**, `snapshot JSON` (sayfa + bölümler), `note VARCHAR(255) NULL`, `created_by` FK users **SET NULL**, `created_at`. Sayfa başına son 50 revizyon tutulur.

### 10.5 `redirects`
`id`, `from_path VARCHAR(255)` unique, `to_path VARCHAR(255)`, `status_code SMALLINT DEFAULT 301`, `hits INT UNSIGNED DEFAULT 0`, `created_at`.

### 10.6 `navigation_items`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| location | VARCHAR(32) | | `header, footer_quick, footer_legal, footer_community` |
| parent_id | BIGINT UNSIGNED | ✓ | FK → navigation_items **CASCADE** (1 seviye alt menü) |
| label | VARCHAR(64) | | |
| route_name | VARCHAR(100) | ✓ | Dahili bağlantı (tercih edilen) |
| url | VARCHAR(255) | ✓ | Harici bağlantı (`https://` zorunlu) |
| visibility | VARCHAR(16) | | `all, guest, auth` |
| opens_new_tab | BOOLEAN | | `rel="noopener"` otomatik eklenir |
| sort | SMALLINT | | |
| is_visible | BOOLEAN | | |
| created_at / updated_at | DATETIME | ✓ | |

`CHECK (route_name IS NOT NULL OR url IS NOT NULL)`.

### 10.7 `social_links`
`id`, `platform VARCHAR(32)` (`discord, youtube, tiktok, instagram, x`), `label`, `url` (https), `sort`, `is_visible`, timestamps.

### 10.8 `news_categories`, `news_posts`, `tags`, `news_post_tag`
- `news_categories`: `id, slug (uq), name, sort, timestamps`.
- `news_posts`: `id, ulid, slug (uq), news_category_id` FK **RESTRICT**, `title VARCHAR(150), excerpt VARCHAR(300), body MEDIUMTEXT, cover_media_id` FK media **SET NULL**, `author_label VARCHAR(64)` ("Yönetim Ekibi"), `author_id` FK users **SET NULL**, `is_pinned BOOLEAN`, *yayın blok*, `deleted_at`. Index: `(status, published_at)`, `(news_category_id, published_at)`.
- `tags`: `id, slug (uq), name`.
- `news_post_tag`: `news_post_id` FK **CASCADE**, `tag_id` FK **CASCADE**. PK `(news_post_id, tag_id)`.

### 10.9 `game_system_categories`, `game_systems`
- `game_system_categories`: `id, slug (uq), name, icon VARCHAR(48)` (Lucide adı), `sort`, timestamps.
- `game_systems`: `id, slug (uq), game_system_category_id` FK **RESTRICT**, `name, summary VARCHAR(300), how_it_works MEDIUMTEXT, how_to_access TEXT, requirements TEXT NULL, media_id` FK **SET NULL**, `video_url VARCHAR(255) NULL` (yalnızca YouTube/izinli host), `is_live_in_game BOOLEAN DEFAULT 0`, `last_reviewed_at DATETIME NULL`, *yayın blok*.

### 10.10 `game_jobs` (meslekler — D-10)
`id, slug (uq), name, summary, class VARCHAR(16)` (`legal, special, event`), `description MEDIUMTEXT, duties TEXT, progression TEXT NULL, earnings_info TEXT NULL, access_requirement VARCHAR(255) NULL, media_id` FK **SET NULL**, `application_type_id` FK **SET NULL**, `is_active BOOLEAN`, *yayın blok*.

### 10.11 `factions`
`id, slug (uq), name, type VARCHAR(16)` (`legal, illegal, government, private`), `logo_media_id` FK **SET NULL**, `cover_media_id` FK **SET NULL**, `summary VARCHAR(300), description MEDIUMTEXT, min_requirements TEXT NULL, application_type_id` FK **SET NULL**, `applications_open BOOLEAN DEFAULT 0, rule_category_id` FK **SET NULL**, `accent_token VARCHAR(16) NULL` (rozet rengi), *yayın blok*.

### 10.12 Kurallar
- `rule_categories`: `id, slug (uq), name, description, prefix VARCHAR(8)` (`RP`, `GNL`), *yayın blok*.
- `rules`: `id, rule_category_id` FK **RESTRICT**, `code VARCHAR(16)` (uq; `RP-4.2`), `title VARCHAR(150), body TEXT, severity_hint VARCHAR(16) NULL` (`low, medium, high`), *yayın blok*.
- `rule_revisions`: `id, rule_id` FK **CASCADE**, `title, body, changed_by` FK **SET NULL**, `change_note VARCHAR(255)`, `created_at`.
- `rule_acknowledgements`: `id, user_id` FK **CASCADE**, `version VARCHAR(32)` (`settings.rules.current_version`), `acknowledged_at`. Unique `(user_id, version)`.

### 10.13 `faqs`
`id, group VARCHAR(48)` (`baslangic, hesap, odeme…`), `question VARCHAR(255), answer TEXT, show_on_home BOOLEAN`, *yayın blok*.

### 10.14 `community_events`
`id, ulid, slug (uq), title, summary VARCHAR(300), description MEDIUMTEXT, cover_media_id` FK **SET NULL**, `starts_at DATETIME, ends_at DATETIME NULL, location_label VARCHAR(100) NULL`, *yayın blok*. Index: `(status, starts_at)`.

### 10.15 `announcements`, `announcement_reads`

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| title | VARCHAR(150) | | |
| body | TEXT | | Markdown |
| priority | VARCHAR(16) | | `info, important, critical` |
| audience | VARCHAR(16) | | `all, players, linked_players, roles` |
| audience_role_ids | JSON | ✓ | `audience=roles` için |
| channels | JSON | | `["public_banner","panel","email"]` |
| starts_at | DATETIME | | |
| ends_at | DATETIME | ✓ | |
| email_dispatched_at | DATETIME | ✓ | E-posta kanalı bir kez çalışır |
| *yayın blok* | | | |

`announcement_reads`: `announcement_id` FK **CASCADE**, `user_id` FK **CASCADE**, `read_at`. PK `(announcement_id, user_id)`. Duyurular kullanıcı başına satıra açılmaz (fan-out yok); hedef kitle sorgu anında eşleştirilir.

### 10.16 `contact_messages`
`id, ulid, name VARCHAR(100), email VARCHAR(254), subject VARCHAR(150), body TEXT, ip_hash CHAR(64), status VARCHAR(16)` (`new, read, replied, spam`), `handled_by` FK users **SET NULL**, `created_at, updated_at`. Saklama 1 yıl.

### 10.17 Akademi (müfredat)

| Tablo | Kolonlar |
|---|---|
| `academy_courses` (Ders) | `id, slug (uq), title VARCHAR(150), summary VARCHAR(300), level VARCHAR(16)` (`beginner, intermediate, advanced`), `cover_media_id` FK **SET NULL**, `is_required BOOLEAN` (yeni oyuncu için zorunlu), `is_onboarding BOOLEAN` (`/start` kaynağı; en fazla 1 kayıt, servis kontrolü), `estimated_minutes SMALLINT`, *yayın blok* |
| `academy_topics` (Konu) | `id, academy_course_id` FK **CASCADE**, `slug` (ders içinde uq: `(academy_course_id, slug)`), `title VARCHAR(150), body MEDIUMTEXT, step_label VARCHAR(48) NULL` (`/start` adım başlığı), `estimated_minutes SMALLINT`, *yayın blok* |
| `academy_topic_rule` | `academy_topic_id` FK **CASCADE**, `rule_id` FK **CASCADE**. PK ikili |
| `academy_progress` | `user_id` FK **CASCADE**, `academy_topic_id` FK **CASCADE**, `completed_at`. PK `(user_id, academy_topic_id)` |
| `academy_quizzes` … (R2) | Taslak: `academy_quizzes, academy_questions, academy_options, academy_attempts`. Faz 10'da bu dokümana eklenecek |

### 10.18 Mesaj şablonları

**`message_templates`**

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| key | VARCHAR(100) | | `auth.verify_email, support.staff_replied…` ([CMS §6.4](CMS.md)) |
| channel | VARCHAR(32) | | `mail, panel, discord_webhook, whatsapp` |
| locale | VARCHAR(8) | | `tr` |
| name | VARCHAR(150) | | |
| subject | VARCHAR(200) | ✓ | E-posta konusu / panel başlığı |
| body | TEXT | | Şablon söz dizimi |
| variables | JSON | | İzinli değişken listesi (kod kaynaklı, salt okunur) |
| provider_template_name | VARCHAR(100) | ✓ | WhatsApp onaylı şablon adı |
| provider_language | VARCHAR(16) | ✓ | `tr` |
| provider_params_map | JSON | ✓ | `["user.username","ticket.number"]` → `{{1}}, {{2}}` |
| status | VARCHAR(16) | | `draft, active, archived` |
| is_system | BOOLEAN | | Sistem şablonu: silinemez, anahtarı değişmez |
| version | INT UNSIGNED | | Her kayıtta +1 |
| updated_by | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| created_at / updated_at | DATETIME | ✓ | |

- Unique: `uq_mt_key_channel_locale (key, channel, locale)`.

**`message_template_revisions`**: `id, message_template_id` FK **CASCADE**, `version, subject, body, changed_by` FK **SET NULL**, `created_at`.

**`message_dispatches`**

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| message_template_id | BIGINT UNSIGNED | ✓ | FK → message_templates **SET NULL** |
| template_key | VARCHAR(100) | | Anlık kopya |
| channel | VARCHAR(32) | | |
| user_id | BIGINT UNSIGNED | ✓ | FK → users **SET NULL** |
| recipient_masked | VARCHAR(100) | ✓ | `f***@g***.com`, `+90 5** *** ** 12` |
| status | VARCHAR(24) | | `queued, sent, delivered, failed, skipped_no_consent, skipped_preference` |
| provider_message_id | VARCHAR(128) | ✓ | |
| error | VARCHAR(500) | ✓ | |
| attempts | TINYINT UNSIGNED | | |
| queued_at / sent_at | DATETIME | ✓ | |

- Index: `(user_id, queued_at)`, `(status, queued_at)`. Saklama 180 gün.

---

## 11. Moderasyon ve denetim

### 11.1 `sanctions` (R2)

| Kolon | Tip | Null | Varsayılan | Açıklama |
|---|---|---|---|---|
| id | BIGINT UNSIGNED PK | | | |
| ulid | CHAR(26) | | | |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users **RESTRICT** |
| mta_account_id | BIGINT UNSIGNED | ✓ | | FK → mta_accounts **RESTRICT** |
| character_id | BIGINT UNSIGNED | ✓ | | FK → characters **RESTRICT** |
| type | VARCHAR(16) | | | `warning, mute, jail, kick, temp_ban, perm_ban, web_suspension` |
| public_reason | VARCHAR(500) | | | Oyuncu görür |
| internal_note | TEXT | ✓ | | Oyuncu **görmez** |
| evidence_url | VARCHAR(500) | ✓ | | Yalnızca personel |
| rule_id | BIGINT UNSIGNED | ✓ | | FK → rules **SET NULL** (ihlal edilen kural) |
| starts_at | DATETIME | | | |
| ends_at | DATETIME | ✓ | | NULL = süresiz/tek seferlik |
| status | VARCHAR(16) | | `active` | `active, expired, revoked` |
| appealable | BOOLEAN | | 1 | |
| issued_by | BIGINT UNSIGNED | | | FK → users **RESTRICT** |
| revoked_by | BIGINT UNSIGNED | ✓ | | FK → users **RESTRICT** |
| revoked_at | DATETIME | ✓ | | |
| revoke_reason | VARCHAR(500) | ✓ | | |
| created_at / updated_at | DATETIME | ✓ | | |

- `CHECK (user_id IS NOT NULL OR mta_account_id IS NOT NULL OR character_id IS NOT NULL)`.
- Index: `(user_id, status)`, `(mta_account_id, status)`, `(status, ends_at)`.

### 11.2 `sanction_appeals`
`id, ulid, sanction_id` FK **RESTRICT** (uq — ceza başına 1 itiraz), `user_id` FK **RESTRICT**, `body TEXT, status VARCHAR(16)` (`submitted, in_review, accepted, rejected`), `reviewed_by` FK users **RESTRICT** (≠ `sanctions.issued_by`), `decision_note_public VARCHAR(1000) NULL, internal_note TEXT NULL, decided_at`, timestamps.

### 11.3 `audit_logs` (append-only, hash zinciri)

| Kolon | Tip | Null | Açıklama |
|---|---|---|---|
| id | BIGINT UNSIGNED PK | | |
| ulid | CHAR(26) | | |
| actor_type | VARCHAR(16) | | `user, system, bridge, payment_provider, scheduler` |
| actor_id | BIGINT UNSIGNED | ✓ | FK → users **RESTRICT** |
| action | VARCHAR(64) | | `users.suspended, roles.assigned, wallet.adjustment_applied…` ([ADMIN §8](ADMIN.md)) |
| target_type | VARCHAR(64) | ✓ | Morph alias (`user`, `ticket`…) |
| target_id | BIGINT UNSIGNED | ✓ | |
| severity | VARCHAR(16) | | `info, warning, critical` |
| changes | JSON | ✓ | `{"before":{…},"after":{…}}`. Hassas alanlar `[REDACTED]` |
| reason | VARCHAR(1000) | ✓ | Gerekçe (kritik işlemlerde zorunlu) |
| ip_masked | VARCHAR(64) | ✓ | |
| user_agent_summary | VARCHAR(120) | ✓ | |
| request_id | CHAR(26) | ✓ | |
| prev_hash | CHAR(64) | ✓ | Önceki kaydın `hash` değeri |
| hash | CHAR(64) | | `SHA256(prev_hash ‖ canonical_json(row))` |
| created_at | DATETIME | | |

- Unique: `uq_audit_ulid`. Index: `(actor_id, created_at)`, `(target_type, target_id, created_at)`, `(action, created_at)`, `(severity, created_at)`.
- Yazım sırası `AuditLogger` içinde `GET_LOCK('audit_chain')` ile serileştirilir (zincir bütünlüğü). `php artisan audit:verify` zinciri baştan doğrular.

### 11.4 `admin_activity_logs`
`id, user_id` FK **CASCADE**, `route_name VARCHAR(100), method VARCHAR(8), target_type VARCHAR(64) NULL, target_id BIGINT UNSIGNED NULL, ip_masked, created_at`. Admin GET görüntülemelerini de kaydeder (ör. "kullanıcı detayını görüntüledi"). Saklama 90 gün. Index `(user_id, created_at)`.

---

## 12. Seed verisi

| Seeder | İçerik | Ortam |
|---|---|---|
| `PermissionSeeder` | `config/permissions.php` → `permissions` (idempotent sync) | Tümü |
| `RoleSeeder` | Sistem rolleri ve [ADMIN §3](ADMIN.md) matrisi | Tümü |
| `BranchSeeder` | `destek-genel, destek-teknik, moderasyon, icerik, finans` | Tümü |
| `SettingsSeeder` | [ADMIN §7](ADMIN.md) varsayılan ayarları | Tümü |
| `MessageTemplateSeeder` | Sistem şablonları ([CMS §6.4](CMS.md)) | Tümü |
| `ContentSeeder` | Sistem sayfaları (home, legal), örnek bölümler, SSS, kural kategorileri, Akademi "Başlangıç" dersi | Tümü (içerik `draft`) |
| `WalletTypeSeeder` | `web_credit` | Tümü |
| `DemoSeeder` | Sahte kullanıcılar, ticket'lar, karakterler | **Yalnızca local**. `APP_ENV=production` iken çalışmayı reddeder |

İlk süper admin seeder ile **oluşturulmaz**. Bunun yerine `php artisan mrp:make-super-admin {email}` komutu kullanılır (interaktif parola, MFA kurulumu ilk girişte zorunlu).

## 13. Anonimleştirme (hesap silme)

`AnonymizeUser` action'ı (tek transaction):
1. `users`: `username = 'silinmis_' + id`, `email = 'deleted+{ulid}@invalid.local'`, `password` rastgele hash, 2FA alanları NULL, `status = anonymized`.
2. `sessions`, `user_contact_channels`, `user_notification_preferences`, `email_change_requests`, `game_link_codes`, `user_security_events` silinir.
3. `game_account_links` aktifse `revoked` olur ve köprüye `account.link_revoked` komutu gönderilir.
4. Ticket mesajları korunur (personel geçmişi), ancak yazar adı "Silinmiş kullanıcı" olarak gösterilir.
5. Finansal kayıtlar ve audit **değişmez** (yasal saklama).
6. `audit_logs(users.anonymized, severity=warning)` yazılır.
