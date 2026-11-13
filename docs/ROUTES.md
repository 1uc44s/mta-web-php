# ROUTES — Endpoint Kataloğu

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [ARCHITECTURE §5](ARCHITECTURE.md#5-kimlik-doğrulama-ve-yetkilendirme) · [ADMIN §3](ADMIN.md) · [SRS](SRS.md)

Bu doküman **tüm rotaların sözleşmesidir**. Yeni bir rota önce buraya eklenir. `php artisan route:list` çıktısı bu listeyle birebir eşleşmelidir (CI testi: `tests/Feature/Security/RouteCatalogTest.php`).

---

## 1. Kurallar

1. **Fiiller:** Tarayıcı arayüzünde yalnızca **GET** (okuma) ve **POST** (her türlü durum değişikliği) kullanılır. Aksiyon adı URL'de yer alır (`POST /panel/support/{ticket}/close`). İstisna: Fortify'ın kendi 2FA kapatma rotası `DELETE`'tir ve HTML formunda `@method('DELETE')` ile POST üzerinden gönderilir.
2. **GET güvenlidir:** GET rotaları asla durum değiştirmez. "Okundu işaretle" gibi işlemler de POST'tur.
3. **Route key:** Dışa açık varlıklar URL'de `ulid` ile bağlanır (`{ticket:ulid}`). İçerikler `slug` ile bağlanır. Sayısal `id` yalnızca admin içi lookup tablolarında (rol, branş, kategori) görünür ve bu rotalar zaten izinle korunur.
4. **İsimlendirme:** `alan.kaynak.eylem` (`panel.support.store`, `admin.users.status`). View ve testler isimle rota üretir; URL hard-code edilmez.
5. **Yanıt:** Başarılı POST → `303 See Other` (PRG). Doğrulama hatası → `302 back` + hata çantası. JSON uçları SRS §8 formatını kullanır.
6. **Her `admin.*` rotası** bir `can:` middleware'i **veya** controller'da `authorize()` çağrısı taşır. Aşağıdaki tablolarda "Yetki" sütunu boş olan admin rotası olamaz (SEC-010).

## 2. Middleware grupları (özet)

| Grup | Zincir |
|---|---|
| `web` | cookies → session → CSRF → bindings → `request.id` → `security.headers` → `site.maintenance` → `share.settings` |
| `panel` | `web` + `auth` + `active` + `noindex` |
| `panel.verified` | `panel` + `verified` |
| `admin` | `web` + `auth` + `verified` + `active` + `staff` + `mfa` + `admin.idle` + `admin.activity` + `noindex` |
| `bridge` | `request.id` + `bridge.auth` + `throttle:bridge` (session ve CSRF **yok**) |
| `hooks` | `request.id` + `throttle:webhooks` + `webhook.verify:{provider}` |
| `api.public` | `request.id` + `throttle:public-api` |

Kısaltmalar: **PC** = `password.confirm`, **T:x** = `throttle:x`, **F:x** = `feature:x`.

---

## 3. Public site (`web`)

| Yöntem | URI | Ad | Ek middleware | Açıklama |
|---|---|---|---|---|
| GET | `/` | `public.home` | | CMS bölümlerinden ana sayfa |
| GET | `/about` | `public.about` | | `pages(slug=about)` |
| GET | `/systems` | `public.systems.index` | | Kategorili oyun sistemleri |
| GET | `/systems/{system:slug}` | `public.systems.show` | | |
| GET | `/jobs` | `public.jobs.index` | | `?class=legal\|special\|event` |
| GET | `/jobs/{job:slug}` | `public.jobs.show` | | |
| GET | `/factions` | `public.factions.index` | | `?type=…` |
| GET | `/factions/{faction:slug}` | `public.factions.show` | | |
| GET | `/start` | `public.start` | | Akademi onboarding dersinden rehber |
| GET | `/rules` | `public.rules` | | Tüm kategoriler, `#KOD` çapası |
| GET | `/academy` | `public.academy.index` | | Ders listesi (okuma herkese açık) |
| GET | `/academy/{course:slug}` | `public.academy.course` | | Konu listesi |
| GET | `/academy/{course:slug}/{topic:slug}` | `public.academy.topic` | | Konu içeriği (`scopeBindings`) |
| GET | `/news` | `public.news.index` | | 12/sayfa |
| GET | `/news/category/{category:slug}` | `public.news.category` | | |
| GET | `/news/{post:slug}` | `public.news.show` | | |
| GET | `/events` | `public.events.index` | | (C) |
| GET | `/events/{event:slug}` | `public.events.show` | | (C) |
| GET | `/faq` | `public.faq` | | |
| GET | `/contact` | `public.contact` | | |
| POST | `/contact` | `public.contact.store` | T:contact | Honeypot + min. süre |
| GET | `/privacy` · `/kvkk` · `/terms` · `/cookies` · `/refund-policy` | `public.legal.{slug}` | | Yasal sayfalar (`refund-policy` Faz 9, ROADMAP 9.8) |
| GET | `/refund-policy` | `public.legal.refund` | F:payments | |
| GET | `/p/{page:slug}` | `public.page` | | Ek `standard` sayfalar |
| POST | `/preferences/theme` | `public.preferences.theme` | T:60,1 | `theme`, `reduced_effects` → cookie (+ girişliyse hesap) |
| POST | `/cookie-consent` | `public.cookie-consent` | T:30,1 | Çerez tercihi |
| GET | `/sitemap.xml` | `public.sitemap` | | 1 saat cache |
| GET | `/robots.txt` | `public.robots` | | `Disallow: /panel/`, `/admin/` |
| GET | `/feed.xml` | `public.feed` | | Haber RSS (S) |
| GET | `/up` | — | | Laravel sağlık kontrolü |

Statik: `/manifest.webmanifest`, `/sw.js`, `/offline.html`, `/build/*`, `/storage/*`.

## 4. Kimlik doğrulama (Fortify + özel)

| Yöntem | URI | Ad | Middleware | Açıklama |
|---|---|---|---|---|
| GET | `/login` | `login` | guest | |
| POST | `/login` | `login.store` | guest, T:login | E-posta veya kullanıcı adı |
| POST | `/logout` | `logout` | auth | `Clear-Site-Data` |
| GET | `/register` | `register` | guest, F:registration | **Özel** controller (Fortify değil) |
| POST | `/register` | `register.store` | guest, F:registration, T:register | Her durumda aynı yanıt: `/register/check-email` (otomatik giriş yok, SEC-005) |
| GET | `/register/check-email` | `register.check-email` | guest | "E-postanı kontrol et" ekranı |
| GET | `/forgot-password` | `password.request` | guest | |
| POST | `/forgot-password` | `password.email` | guest, T:password-reset | Genel yanıt |
| GET | `/reset-password/{token}` | `password.reset` | guest | |
| POST | `/reset-password` | `password.update` | guest, T:password-reset | |
| GET | `/email/verify` | `verification.notice` | auth | Doğrulanmamış girişli kullanıcı için bilgi ekranı |
| GET | `/email/verify/{ulid}/{hash}` | `verification.verify` | signed, T:6,1 | **Özel**, oturum gerektirmez (kayıt sonrası otomatik giriş olmadığı için). `hash = sha256(email)`; başarıda `/login` + toast |
| POST | `/email/verification-notification` | `verification.send` | auth, T:verification | |
| GET | `/user/confirm-password` | `password.confirm` | auth | |
| POST | `/user/confirm-password` | `password.confirm.store` | auth, T:5,1 | |
| GET | `/user/confirmed-password-status` | `password.confirmation` | auth | JSON |
| GET | `/two-factor-challenge` | `two-factor.login` | guest | |
| POST | `/two-factor-challenge` | `two-factor.login.store` | guest, T:two-factor | TOTP veya kurtarma kodu |
| POST | `/user/two-factor-authentication` | `two-factor.enable` | auth, PC | Secret üret |
| POST | `/user/confirmed-two-factor-authentication` | `two-factor.confirm` | auth, PC | İlk kodla onay |
| DELETE | `/user/two-factor-authentication` | `two-factor.disable` | auth, PC | **Personel için engellenir** (Fortify action override) |
| GET | `/user/two-factor-qr-code` | `two-factor.qr-code` | auth, PC | SVG |
| GET | `/user/two-factor-secret-key` | `two-factor.secret-key` | auth, PC | |
| GET | `/user/two-factor-recovery-codes` | `two-factor.recovery-codes` | auth, PC | |
| POST | `/user/two-factor-recovery-codes` | `two-factor.regenerate-recovery-codes` | auth, PC | |

**Faz 3 kararı (D-15):** Fortify yalnızca giriş, çıkış, parola sıfırlama, 2FA ve parola onayı için kullanılır. `registration` ve `emailVerification` özellikleri kapalıdır; kayıt ve doğrulama kendi controller'larımızdadır. Gerekçe: Fortify kayıt sonrası otomatik giriş yapar ve benzersiz e-posta hatası hesap varlığını ifşa eder (SEC-005).

Fortify'ın `updateProfileInformation` ve `updatePasswords` özellikleri **kapalıdır**. Bu işlemler `/panel/security` altındaki kendi rotalarımızla yapılır (güvenlik olayı + bildirim kontrolü bizde kalır).

## 5. Oyuncu paneli (`/panel`)

`panel` grubu. ✔ işaretli satırlar `panel.verified` grubundadır.

### 5.1 Genel

| Yöntem | URI | Ad | V | Ek | Açıklama |
|---|---|---|---|---|---|
| GET | `/panel` | `panel.dashboard` | | | Doğrulanmamışsa doğrulama kartı gösterir |
| GET | `/panel/notifications` | `panel.notifications.index` | | | |
| GET | `/panel/notifications/count` | `panel.notifications.count` | | T:poll | JSON `{unread}` + ETag |
| POST | `/panel/notifications/{id}/read` | `panel.notifications.read` | | | Laravel notification UUID'si (policy: sahip) |
| POST | `/panel/notifications/read-all` | `panel.notifications.read-all` | | | |
| POST | `/panel/announcements/{announcement:ulid}/read` | `panel.announcements.read` | | | |
| GET | `/panel/settings` | `panel.settings` | | | Tema, düşük efekt, bildirim tercihleri |
| POST | `/panel/settings/preferences` | `panel.settings.preferences` | | | |
| POST | `/panel/settings/notifications` | `panel.settings.notifications` | | | |

### 5.2 Oyun hesapları, karakterler, cihazlar

| Yöntem | URI | Ad | V | Ek | Açıklama |
|---|---|---|---|---|---|
| GET | `/panel/game-accounts` | `panel.game-accounts.index` | ✔ | | Bağlı hesaplar + aktif kod kartı |
| POST | `/panel/game-accounts/link-code` | `panel.game-accounts.link-code` | ✔ | T:link-code | Yeni kod (öncekini iptal eder) |
| POST | `/panel/game-accounts/link-code/cancel` | `panel.game-accounts.link-code.cancel` | ✔ | | |
| GET | `/panel/game-accounts/link-status` | `panel.game-accounts.link-status` | ✔ | T:poll | JSON `{state: pending\|linked\|expired, expires_in, account_name?}` |
| POST | `/panel/game-accounts/{link}/unlink-request` | `panel.game-accounts.unlink-request` | ✔ | PC | Gerekçe zorunlu. Policy: sahip |
| GET | `/panel/characters` | `panel.characters.index` | ✔ | | |
| GET | `/panel/characters/{character:ulid}` | `panel.characters.show` | ✔ | | Policy: aktif bağlantı sahibi |
| GET | `/panel/devices` | `panel.devices.index` | ✔ | F:devices | (R2) |
| POST | `/panel/devices/link-code` | `panel.devices.link-code` | ✔ | F:devices, T:link-code | (R2) |
| POST | `/panel/devices/{device}/revoke` | `panel.devices.revoke` | ✔ | F:devices, PC | (R2) |

### 5.3 Akademi ve kurallar

| Yöntem | URI | Ad | V | Açıklama |
|---|---|---|---|---|
| GET | `/panel/academy` | `panel.academy` | | İlerleme özeti |
| POST | `/panel/academy/topics/{topic}/complete` | `panel.academy.complete` | | İdempotent. `{topic}` = konu id'si (slug ders içinde benzersiz olduğundan) |
| GET | `/panel/academy/topics/{topic}/quiz` | `panel.academy.quiz` | F:academy_quiz | Konu sınavı (Faz 10). Doğru cevaplar istemciye gönderilmez |
| POST | `/panel/academy/topics/{topic}/quiz` | `panel.academy.quiz.submit` | F:academy_quiz, T:10,60 | `answers[question_id]=option_id`. Deneme sınırı + bekleme süresi; geçerse konu tamamlanır |
| POST | `/panel/rules/acknowledge` | `panel.rules.acknowledge` | | Geçerli kural sürümünü onaylar |

### 5.4 Destek

| Yöntem | URI | Ad | V | Ek | Açıklama |
|---|---|---|---|---|---|
| GET | `/panel/support` | `panel.support.index` | ✔ | | `?status=open\|closed` |
| GET | `/panel/support/create` | `panel.support.create` | ✔ | | |
| POST | `/panel/support` | `panel.support.store` | ✔ | T:support-create, T:uploads | multipart. `idempotency_key` |
| GET | `/panel/support/{ticket:ulid}` | `panel.support.show` | ✔ | | Yalnızca `public` mesajlar. `player_unread=false` yapılır. **GET yan etkisi istisnası:** okundu bayrağı yalnızca görünüm durumudur, audit gerektirmez |
| POST | `/panel/support/{ticket:ulid}/messages` | `panel.support.messages.store` | ✔ | T:support-message, T:uploads | |
| POST | `/panel/support/{ticket:ulid}/close` | `panel.support.close` | ✔ | | |
| POST | `/panel/support/{ticket:ulid}/reopen-request` | `panel.support.reopen-request` | ✔ | | 7 gün kuralı |
| GET | `/panel/support/attachments/{attachment:ulid}` | `panel.support.attachments.show` | ✔ | | Policy: ticket sahibi + mesaj `public` |

### 5.5 Başvurular (R2, `F:applications`)

| Yöntem | URI | Ad | Açıklama |
|---|---|---|---|
| GET | `/panel/applications` | `panel.applications.index` | Benim başvurularım + açık türler |
| GET | `/panel/applications/new/{type:slug}` | `panel.applications.create` | Uygunluk kontrolü |
| POST | `/panel/applications/new/{type:slug}` | `panel.applications.store` | `action=save_draft\|submit` |
| GET | `/panel/applications/{application:ulid}` | `panel.applications.show` | |
| POST | `/panel/applications/{application:ulid}` | `panel.applications.update` | Yalnızca `draft` |
| POST | `/panel/applications/{application:ulid}/submit` | `panel.applications.submit` | |
| POST | `/panel/applications/{application:ulid}/messages` | `panel.applications.messages.store` | `needs_info` yanıtı |
| POST | `/panel/applications/{application:ulid}/cancel` | `panel.applications.cancel` | |

### 5.6 Cezalar (R2, `F:sanctions`)

| Yöntem | URI | Ad | Açıklama |
|---|---|---|---|
| GET | `/panel/sanctions` | `panel.sanctions.index` | |
| GET | `/panel/sanctions/{sanction:ulid}` | `panel.sanctions.show` | `internal_note` asla yüklenmez (`$hidden` + ayrı resource) |
| POST | `/panel/sanctions/{sanction:ulid}/appeal` | `panel.sanctions.appeal` | T:5,60 |

### 5.7 Cüzdan ve ödeme (R2, `F:wallet` / `F:payments`)

| Yöntem | URI | Ad | Ek | Açıklama |
|---|---|---|---|---|
| GET | `/panel/wallet` | `panel.wallet` | F:wallet | Türlere göre ayrı kartlar |
| GET | `/panel/transactions` | `panel.transactions` | F:wallet | Filtre: tür, yön, tarih |
| GET | `/panel/top-up` | `panel.top-up` | F:payments | Paketler |
| POST | `/panel/payments/checkout` | `panel.payments.checkout` | F:payments, T:10,60 | `package_id`, `idempotency_key` → 303 sağlayıcıya |
| GET | `/panel/payments/{order:ulid}` | `panel.payments.show` | F:payments | Dönüş sayfası (bakiye yüklemez) |
| GET | `/panel/payments/{order:ulid}/status` | `panel.payments.status` | F:payments, T:poll | JSON |
| GET | `/payments/fake/{order:ulid}` | `payments.fake.show` | imzalı URL | **Yalnızca** `PAYMENTS_PROVIDER=fake` ve `APP_ENV≠production`: sahte ödeme ekranı (Faz 9, OD-04 kararına kadar) |
| POST | `/payments/fake/{order:ulid}` | `payments.fake.complete` | imzalı URL | `outcome=succeeded\|failed` → `FakeProvider` imzalı webhook olayını **aynı** gelen kutusu yolundan işler |

### 5.8 Güvenlik ve hesap

| Yöntem | URI | Ad | Ek | Açıklama |
|---|---|---|---|---|
| GET | `/panel/security` | `panel.security` | | Parola, e-posta, 2FA, oturumlar |
| POST | `/panel/security/password` | `panel.security.password` | T:5,10 | Mevcut parola zorunlu |
| POST | `/panel/security/email` | `panel.security.email` | PC, T:3,60 | Değişiklik talebi |
| GET | `/panel/security/email/confirm/{token}` | `panel.security.email.confirm` | | Token hash eşleşmesi. **İstisna:** e-postadaki bağlantı GET olmak zorunda olduğundan ekran onay butonu (POST) gösterir |
| POST | `/panel/security/email/confirm/{token}` | `panel.security.email.confirm.store` | | Değişikliği uygular |
| GET | `/panel/security/two-factor` | `panel.security.two-factor` | | Kurulum sihirbazı (Fortify rotalarını kullanır) |
| POST | `/panel/security/sessions/{session}/revoke` | `panel.security.sessions.revoke` | PC | Policy: `sessions.user_id` eşleşmesi |
| POST | `/panel/security/sessions/revoke-others` | `panel.security.sessions.revoke-others` | PC | |
| POST | `/panel/security/sessions/revoke-all` | `panel.security.sessions.revoke-all` | PC | Çıkış dahil |
| GET | `/panel/security/events` | `panel.security.events` | | Son 90 gün güvenlik olaylarım |
| POST | `/panel/account/delete-request` | `panel.account.delete-request` | PC | 14 gün bekleme |
| POST | `/panel/account/delete-cancel` | `panel.account.delete-cancel` | | |
| POST | `/panel/account/export` | `panel.account.export` | PC, T:3,60 | KVKK veri dökümü (Faz 10, SRS §6.3): JSON dosya indirme, `Cache-Control: no-store`. Güvenlik olayı `data_exported` |
| POST | `/panel/push/subscriptions` | `panel.push.subscribe` | F:push, T:20,60 | JSON `{endpoint, keys:{p256dh, auth}, contentEncoding}` (Faz 10) |
| POST | `/panel/push/subscriptions/delete` | `panel.push.unsubscribe` | F:push | JSON `{endpoint}`; yalnızca kendi aboneliği |

---

## 6. Yönetim paneli (`/admin`)

`admin` grubu: `web, auth, verified, active, staff, mfa, admin.idle, admin.activity` (SRS FR-ADM-001). Personel olmayan → 404, 2FA yok → `panel.security.two-factor`, izin yok → 403. "Yetki" = `can:` middleware'i. "Kapsam" = Action/Policy içinde ek branş veya sahiplik kontrolü. "PC" = `password.confirm` (10 dk penceresi).

### 6.1 Panel ve sistem

| Yöntem | URI | Ad | Yetki | Açıklama |
|---|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | *(staff)* | Widget'lar izne göre filtrelenir |
| GET | `/admin/system/health` | `admin.system.health` | `settings.read` | Kuyruk, failed jobs, zamanlayıcı son çalışma, mail durumu |
| POST | `/admin/system/failed-jobs/{uuid}/retry` | `admin.system.failed-jobs.retry` | `settings.update` | |

### 6.2 Kullanıcılar

| Yöntem | URI | Ad | Yetki | Ek / Kapsam |
|---|---|---|---|---|
| GET | `/admin/users` | `admin.users.index` | `users.read` | Arama ve filtreler (FR-ADM-005) |
| GET | `/admin/users/{user:ulid}` | `admin.users.show` | `users.read` | Sekmeler kendi izinleriyle (PII, güvenlik, finans) |
| POST | `/admin/users/{user:ulid}/status` | `admin.users.status` | `users.suspend` | PC. Gerekçe zorunlu. Hiyerarşi: personeli yalnızca daha yüksek öncelikli kişi askıya alabilir |
| POST | `/admin/users/{user:ulid}/resend-verification` | `admin.users.resend-verification` | `users.update` | T:3,60 |
| GET | `/admin/users/{user:ulid}/security` | `admin.users.security` | `users.security_read` | Güvenlik olayları |
| POST | `/admin/users/{user:ulid}/anonymize` | `admin.users.anonymize` | `users.anonymize` | PC. KVKK talebi |

### 6.3 Oyun hesapları, karakterler, cihazlar, sunucular

| Yöntem | URI | Ad | Yetki | Ek |
|---|---|---|---|---|
| GET | `/admin/game-accounts` | `admin.game-accounts.index` | `game_accounts.read` | Çakışma uyarıları filtresi |
| GET | `/admin/game-accounts/{account:ulid}` | `admin.game-accounts.show` | `game_accounts.read` | |
| POST | `/admin/game-accounts/links/{link}/approve-unlink` | `admin.game-accounts.approve-unlink` | `game_accounts.manage` | Audit |
| POST | `/admin/game-accounts/links/{link}/reject-unlink` | `admin.game-accounts.reject-unlink` | `game_accounts.manage` | |
| POST | `/admin/game-accounts/links/{link}/revoke` | `admin.game-accounts.revoke` | `game_accounts.manage` | PC. Köprüye `account.link_revoked` |
| POST | `/admin/game-accounts/manual-link` | `admin.game-accounts.manual-link` | `game_accounts.manage` | PC. Brief §5.1 alternatif yöntem. Gerekçe + kanıt zorunlu |
| GET | `/admin/characters` | `admin.characters.index` | `characters.read` | |
| GET | `/admin/characters/{character:ulid}` | `admin.characters.show` | `characters.read` | |
| POST | `/admin/characters/{character:ulid}/visibility` | `admin.characters.visibility` | `characters.review` | `is_profile_hidden` aç/kapat. Gerekçe zorunlu, audit `characters.visibility_changed` |
| GET | `/admin/devices` | `admin.devices.index` | `devices.read` | (R2) |
| POST | `/admin/devices/{device}/approve` | `admin.devices.approve` | `devices.approve` | (R2) PC |
| POST | `/admin/devices/{device}/reject` | `admin.devices.reject` | `devices.approve` | (R2) |
| POST | `/admin/servers/{server}/maintenance` | `admin.servers.maintenance` | `servers.maintenance` | Bakım bayrağı + not |

### 6.4 Destek

| Yöntem | URI | Ad | Yetki | Kapsam |
|---|---|---|---|---|
| GET | `/admin/support` | `admin.support.index` | `support.read` | Branş kuyruğu |
| GET | `/admin/support/counters` | `admin.support.counters` | `support.read` | JSON, T:poll |
| GET | `/admin/support/{ticket:ulid}` | `admin.support.show` | `support.read` | Branş |
| POST | `/admin/support/{ticket:ulid}/messages` | `admin.support.messages.store` | `support.reply` | Branş. `visibility=internal` için ek `support.internal_note` |
| POST | `/admin/support/{ticket:ulid}/status` | `admin.support.status` | `support.reply` | `closed` için `support.close` |
| POST | `/admin/support/{ticket:ulid}/assign` | `admin.support.assign` | `support.assign` | Atanan kişi branş üyesi olmalı |
| POST | `/admin/support/{ticket:ulid}/claim` | `admin.support.claim` | `support.reply` | Kendine al |
| POST | `/admin/support/{ticket:ulid}/priority` | `admin.support.priority` | `support.assign` | |
| POST | `/admin/support/{ticket:ulid}/escalate` | `admin.support.escalate` | `support.assign` | Hedef branş + not. Audit |
| POST | `/admin/support/{ticket:ulid}/reopen-decision` | `admin.support.reopen-decision` | `support.reply` | |
| GET | `/admin/support/attachments/{attachment:ulid}` | `admin.support.attachments.show` | `support.read` | Branş |
| — | `/admin/support/macros…` | `admin.support.macros.*` | `support.manage` | CMS kaynak deseni (§6.8) |
| — | `/admin/support/categories…` | `admin.support.categories.*` | `support.manage` | CMS kaynak deseni |

### 6.5 Başvurular (R2)

| Yöntem | URI | Ad | Yetki | Kapsam |
|---|---|---|---|---|
| GET | `/admin/applications` | `admin.applications.index` | `applications.read` | Branş |
| GET | `/admin/applications/{application:ulid}` | `admin.applications.show` | `applications.read` | Branş |
| POST | `/admin/applications/{application:ulid}/assign` | `admin.applications.assign` | `applications.review` | Branş |
| POST | `/admin/applications/{application:ulid}/status` | `admin.applications.status` | `applications.review` | Branş. `approved/rejected/needs_info/in_review` |
| POST | `/admin/applications/{application:ulid}/messages` | `admin.applications.messages.store` | `applications.review` | Branş |
| — | `/admin/application-types…` | `admin.application-types.*` | `applications.types.manage` | CMS kaynak deseni |

### 6.6 Moderasyon ve oyun komutları (R2)

| Yöntem | URI | Ad | Yetki | Ek |
|---|---|---|---|---|
| GET | `/admin/sanctions` | `admin.sanctions.index` | `sanctions.read` | |
| GET | `/admin/sanctions/create` | `admin.sanctions.create` | `sanctions.create` | `?user=` / `?account=` ön doldurma |
| POST | `/admin/sanctions` | `admin.sanctions.store` | `sanctions.create` | `perm_ban` için `sanctions.ban_permanent` + PC. Oyun içi tür → `game_commands` |
| GET | `/admin/sanctions/{sanction:ulid}` | `admin.sanctions.show` | `sanctions.read` | |
| POST | `/admin/sanctions/{sanction:ulid}/revoke` | `admin.sanctions.revoke` | `sanctions.revoke` | Gerekçe zorunlu |
| GET | `/admin/appeals` | `admin.appeals.index` | `sanctions.appeals.review` | |
| GET | `/admin/appeals/{appeal:ulid}` | `admin.appeals.show` | `sanctions.appeals.review` | |
| POST | `/admin/appeals/{appeal:ulid}/decide` | `admin.appeals.decide` | `sanctions.appeals.review` | Cezayı veren kişi karar veremez |
| GET | `/admin/game-commands` | `admin.game-commands.index` | `game_commands.read` | |
| POST | `/admin/game-commands` | `admin.game-commands.store` | *tür bazlı* | Tür → izin eşlemesi: [ARCHITECTURE §7.5](ARCHITECTURE.md#75-komut-kuyruğu-pull-modeli-r2). Controller'da `authorize()` |
| POST | `/admin/game-commands/{command:ulid}/cancel` | `admin.game-commands.cancel` | `game_commands.read` | Yalnızca `pending` ve kendi komutu (veya `super_admin`) |

### 6.7 Ekonomi ve ödemeler (R2)

| Yöntem | URI | Ad | Yetki | Ek |
|---|---|---|---|---|
| GET | `/admin/economy` | `admin.economy.index` | `wallet.read` | Günlük/aylık toplamlar |
| GET | `/admin/economy/transactions` | `admin.economy.transactions` | `wallet.read` | Cursor pagination |
| GET | `/admin/economy/wallets/{wallet}` | `admin.economy.wallets.show` | `wallet.read` | |
| GET | `/admin/economy/adjustments` | `admin.economy.adjustments.index` | `wallet.read` | |
| POST | `/admin/economy/adjustments` | `admin.economy.adjustments.store` | `wallet.adjust.request` | PC |
| POST | `/admin/economy/adjustments/{request:ulid}/approve` | `admin.economy.adjustments.approve` | `wallet.adjust.approve` | PC. Dört göz |
| POST | `/admin/economy/adjustments/{request:ulid}/reject` | `admin.economy.adjustments.reject` | `wallet.adjust.approve` | |
| POST | `/admin/economy/transactions/{transaction:ulid}/reversal-request` | `admin.economy.reversal-request` | `wallet.adjust.request` | PC. Ters kayıt talebi (dört göz akışına girer) |
| — | `/admin/economy/packages…` | `admin.economy.packages.*` | `payments.packages.manage` | CMS kaynak deseni |
| GET | `/admin/payments` | `admin.payments.index` | `payments.read` | |
| GET | `/admin/payments/{order:ulid}` | `admin.payments.show` | `payments.read` | Webhook geçmişi dahil |
| POST | `/admin/payments/{order:ulid}/refund-request` | `admin.payments.refund-request` | `payments.refund.request` | PC |
| POST | `/admin/refunds/{refund:ulid}/approve` | `admin.refunds.approve` | `payments.refund.approve` | PC. Dört göz |
| POST | `/admin/refunds/{refund:ulid}/reject` | `admin.refunds.reject` | `payments.refund.approve` | |
| GET | `/admin/payments/reconciliation` | `admin.payments.reconciliation` | `payments.reconcile` | |
| POST | `/admin/payments/reconciliation/run` | `admin.payments.reconciliation.run` | `payments.reconcile` | Kuyruğa iş atar |

### 6.8 İçerik (CMS)

**CMS kaynak deseni.** Aşağıdaki her `{kaynak}` için şu rotalar üretilir (`AdminResource::routes()` makrosu):

| Yöntem | URI | Ad | Varsayılan yetki |
|---|---|---|---|
| GET | `/admin/content/{kaynak}` | `admin.content.{kaynak}.index` | `content.read` |
| GET | `/admin/content/{kaynak}/create` | `….create` | `content.create` |
| POST | `/admin/content/{kaynak}` | `….store` | `content.create` |
| GET | `/admin/content/{kaynak}/{id}/edit` | `….edit` | `content.read` |
| POST | `/admin/content/{kaynak}/{id}` | `….update` | `content.update` |
| POST | `/admin/content/{kaynak}/{id}/submit-review` | `….submit-review` | `content.update` |
| POST | `/admin/content/{kaynak}/{id}/publish` | `….publish` | `content.publish` (`published_at` ileri tarihse `scheduled`) |
| POST | `/admin/content/{kaynak}/{id}/unpublish` | `….unpublish` | `content.publish` |
| POST | `/admin/content/{kaynak}/{id}/archive` | `….archive` | `content.publish` |
| POST | `/admin/content/{kaynak}/{id}/delete` | `….destroy` | `content.delete` (sistem kayıtlarında 403) |
| POST | `/admin/content/{kaynak}/reorder` | `….reorder` | `content.update` |
| GET | `/admin/content/{kaynak}/{id}/preview` | `….preview` | `content.read` (imzalı, 30 dk) |

**Kaynaklar:** `pages`, `news`, `news-categories`, `tags`, `systems`, `system-categories`, `jobs`, `factions`, `rule-categories`, `rules`, `faqs`, `events`, `navigation`, `social-links`, `academy-courses`, `academy-topics`.

Kaynağa özel rotalar ve yetki istisnaları:

| Yöntem | URI | Ad | Yetki | Açıklama |
|---|---|---|---|---|
| GET | `/admin/content/pages/{page}/sections` | `admin.content.pages.sections` | `content.read` | Bölüm düzenleyici |
| POST | `/admin/content/pages/{page}/sections` | `admin.content.pages.sections.store` | `content.update` | `type` sistem şablonu kayıt defterinden |
| POST | `/admin/content/pages/{page}/sections/{section}` | `admin.content.pages.sections.update` | `content.update` | JSON şema doğrulaması |
| POST | `/admin/content/pages/{page}/sections/{section}/delete` | `admin.content.pages.sections.destroy` | `content.update` | |
| POST | `/admin/content/pages/{page}/sections/{section}/move` | `admin.content.pages.sections.reorder` | `content.update` | **Faz 5:** `direction=up\|down` ile komşuyla yer değiştirir (klavye erişilebilir; sürükle-bırak R2) |
| GET | `/admin/content/pages/{page}/revisions` | `admin.content.pages.revisions` | `content.read` | Revizyon listesi (metin karşılaştırma) |
| POST | `/admin/content/pages/{page}/revisions/{revision}/restore` | `admin.content.pages.revisions.restore` | `content.publish` | |
| GET | `/admin/content/rules/{rule}/revisions` | `admin.content.rules.revisions` | `content.read` | |
| GET | `/admin/content/rules-version` | `admin.content.rules.version` | `content.publish` | **Faz 5:** sürüm artırma formu (geçerli sürüm + not) |
| POST | `/admin/content/rules/bump-version` | `admin.content.rules.bump-version` | `content.publish` | Kural seti sürümünü artırır → oyunculardan yeniden onay ister |
| — | `factions` güncelleme | | `content.update` **veya** `factions.update_own` | `factions.update_own`: yalnızca üyesi olduğu fraksiyon branşının fraksiyonu ve yalnızca metin alanları. **Faz 8'e ertelendi** (fraksiyon branşı + başvurularla birlikte); Faz 5'te yalnızca `content.update` |
| — | `academy-*` | | `academy.manage` | `content.*` yerine |
| — | `academy-quizzes` (Faz 10) | | `academy.manage` | Sınav ayarları (CMS kaynak deseni) |
| GET / POST | `/admin/academy-quizzes/{quiz}/questions`, `…/questions/{question}/delete` | `admin.academy-quizzes.questions(.store/.destroy)` | `academy.manage` | Soru + 2–6 seçenek, tam 1 doğru |
| GET | `/admin/media` | `admin.media.index` | `content.read` | Izgara + arama |
| POST | `/admin/media` | `admin.media.store` | `media.upload` | T:uploads |
| POST | `/admin/media/{media:ulid}` | `admin.media.update` | `media.upload` | Alt metin, dekoratif bayrağı |
| POST | `/admin/media/{media:ulid}/delete` | `admin.media.destroy` | `media.delete` | Kullanımdaysa 409 |

### 6.9 Duyurular, şablonlar, iletişim

| Yöntem | URI | Ad | Yetki | Açıklama |
|---|---|---|---|---|
| — | `/admin/announcements…` | `admin.announcements.*` | `announcements.manage` | CMS kaynak deseni. `publish` için `announcements.publish` |
| GET | `/admin/templates` | `admin.templates.index` | `templates.read` | Anahtar × kanal matrisi |
| GET | `/admin/templates/{template}/edit` | `admin.templates.edit` | `templates.read` | Değişken paneli + canlı önizleme |
| POST | `/admin/templates/{template}` | `admin.templates.update` | `templates.manage` | Sözdizimi + değişken allowlist doğrulaması |
| POST | `/admin/templates/{template}/preview` | `admin.templates.preview` | `templates.read` | Örnek veriyle render (JSON) |
| POST | `/admin/templates/{template}/test-send` | `admin.templates.test-send` | `templates.manage` | Yalnızca kendi adresine. T:5,10 |
| GET | `/admin/templates/{template}/revisions` | `admin.templates.revisions` | `templates.read` | |
| POST | `/admin/templates/{template}/revisions/{revision}/restore` | `admin.templates.revisions.restore` | `templates.manage` | |
| GET | `/admin/contact` | `admin.contact.index` | `contact.read` | |
| GET | `/admin/contact/{message:ulid}` | `admin.contact.show` | `contact.read` | |
| POST | `/admin/contact/{message:ulid}/status` | `admin.contact.status` | `contact.read` | |

### 6.10 Personel, roller, branşlar

| Yöntem | URI | Ad | Yetki | Ek |
|---|---|---|---|---|
| GET | `/admin/staff` | `admin.staff.index` | `staff.read` | MFA durumu, son admin girişi |
| GET | `/admin/staff/{user:ulid}` | `admin.staff.show` | `staff.read` | Roller, branşlar, yetki değişikliği geçmişi |
| POST | `/admin/staff/{user:ulid}/roles` | `admin.staff.roles.assign` | `staff.manage` | PC. Hiyerarşi kuralı. Opsiyonel `expires_at` |
| POST | `/admin/staff/{user:ulid}/roles/{role}/revoke` | `admin.staff.roles.revoke` | `staff.manage` | PC. Son süper admin korunur |
| GET | `/admin/roles` | `admin.roles.index` | `roles.read` | |
| GET | `/admin/roles/create` | `admin.roles.create` | `roles.manage` | |
| POST | `/admin/roles` | `admin.roles.store` | `roles.manage` | PC |
| GET | `/admin/roles/{role}/edit` | `admin.roles.edit` | `roles.read` | İzin matrisi |
| POST | `/admin/roles/{role}` | `admin.roles.update` | `roles.manage` | PC. Kendi öncelikten yüksek veya eşit rol düzenlenemez |
| POST | `/admin/roles/{role}/delete` | `admin.roles.destroy` | `roles.manage` | PC. Sistem rolü ve kullanımda olan rol silinemez |
| GET | `/admin/branches` | `admin.branches.index` | `staff.read` | |
| GET | `/admin/branches/create` | `admin.branches.create` | `branches.manage` | |
| POST | `/admin/branches` | `admin.branches.store` | `branches.manage` | |
| GET | `/admin/branches/{branch}` | `admin.branches.show` | `staff.read` | Üyeler, kategoriler, başvuru türleri |
| POST | `/admin/branches/{branch}` | `admin.branches.update` | `branches.manage` | |
| POST | `/admin/branches/{branch}/members` | `admin.branches.members.store` | `branches.manage` **veya** (`branches.assign_members` + branş lideri) | [ADMIN §5](ADMIN.md) |
| POST | `/admin/branches/{branch}/members/{user:ulid}/remove` | `admin.branches.members.destroy` | aynı | |
| POST | `/admin/branches/{branch}/members/{user:ulid}/lead` | `admin.branches.members.lead` | `branches.manage` | Liderlik aç/kapat |

### 6.11 Denetim, ayarlar, entegrasyonlar

| Yöntem | URI | Ad | Yetki | Ek |
|---|---|---|---|---|
| GET | `/admin/audit-logs` | `admin.audit-logs.index` | `audit.read` | Filtreler: aktör, eylem, hedef, önem, tarih |
| GET | `/admin/audit-logs/{log:ulid}` | `admin.audit-logs.show` | `audit.read` | Diff görünümü |
| GET | `/admin/activity-logs` | `admin.activity-logs.index` | `activity.read` | |
| GET | `/admin/settings` | `admin.settings.index` | `settings.read` | Gruplar |
| GET | `/admin/settings/{group}` | `admin.settings.edit` | `settings.read` | |
| POST | `/admin/settings/{group}` | `admin.settings.update` | `settings.update` | PC. Değişen her anahtar audit'e yazılır |
| GET | `/admin/integrations/mta` | `admin.integrations.mta` | `integrations.manage` | Sunucular, credential'lar, son heartbeat |
| POST | `/admin/integrations/mta/servers` | `admin.integrations.mta.servers.store` | `integrations.manage` | PC |
| POST | `/admin/integrations/mta/servers/{server}` | `admin.integrations.mta.servers.update` | `integrations.manage` | PC |
| POST | `/admin/integrations/mta/servers/{server}/credentials` | `admin.integrations.mta.credentials.store` | `integrations.manage` | PC. Secret yanıtta **bir kez** gösterilir (flash'a yazılmaz, doğrudan view) |
| POST | `/admin/integrations/mta/credentials/{credential}/revoke` | `admin.integrations.mta.credentials.revoke` | `integrations.manage` | PC |

---

## 7. JSON API

### 7.1 Public API (`api.public`)

| Yöntem | URI | Ad | Açıklama |
|---|---|---|---|
| GET | `/api/v1/public/server-status` | `api.public.server-status` | 15 sn cache, ETag. `?server=main` (varsayılan birincil sunucu) |

Örnek yanıt:

```json
{
  "success": true,
  "data": {
    "server": "main",
    "status": "online",
    "players": 87,
    "max_players": 300,
    "last_heartbeat_at": "2026-11-11T18:42:10Z",
    "freshness_seconds": 12,
    "connect": { "host": "play.example.com", "port": 22003, "uri": "mtasa://play.example.com:22003" }
  }
}
```

> Brief §11.2'deki diğer public API uçları (news, rules, faqs…) SPA mimarisi içindi. Server-rendered mimaride (D-01, D-02) gerekli değildir. Bir ihtiyaç doğarsa önce bu dokümana eklenir.

### 7.2 MTA köprüsü (`bridge`)

Tüm uçlar `X-Bridge-*` başlıklarını gerektirir ([ARCHITECTURE §7.2](ARCHITECTURE.md#72-i̇mza-protokolü)). Tüm yanıtlar `{"success": bool, "data"|"error": …, "requestId": "…"}` biçimindedir.

| Yöntem | URI | Ad | Scope | Limit | Faz |
|---|---|---|---|---|---|
| GET | `/api/v1/game/ping` | `api.game.ping` | herhangi | bridge | 7 |
| POST | `/api/v1/game/heartbeat` | `api.game.heartbeat` | `heartbeat` | bridge-heartbeat | 7 |
| POST | `/api/v1/game/player/connected` | `api.game.player.connected` | `events` | bridge | 7 |
| POST | `/api/v1/game/player/disconnected` | `api.game.player.disconnected` | `events` | bridge | 7 |
| POST | `/api/v1/game/accounts/sync` | `api.game.accounts.sync` | `events` | bridge | 7 |
| POST | `/api/v1/game/characters/sync` | `api.game.characters.sync` | `events` | bridge | 7 |
| POST | `/api/v1/game/account-link/verify` | `api.game.account-link.verify` | `account_link` | bridge | 7 |
| POST | `/api/v1/game/device-link/verify` | `api.game.device-link.verify` | `account_link` | bridge | 8 |
| GET | `/api/v1/game/commands/pending` | `api.game.commands.pending` | `commands` | bridge | 8 |
| POST | `/api/v1/game/commands/result` | `api.game.commands.result` | `commands` | bridge | 8 |
| POST | `/api/v1/game/wallet/events` | `api.game.wallet.events` | `events` | bridge | 9 (OD-03) |

**Sözleşmeler** (alan adları ve tipler bağlayıcıdır; FormRequest'ler bunlardan yazılır):

`GET /ping` → `200 {"data":{"server":"main","time":1762867200}}` — saat kayması teşhisi için.

`POST /heartbeat`
```json
{ "players": 87, "max_players": 300, "server_name": "Meridyen RP", "game_mode": "Roleplay",
  "map": "San Andreas", "resource_version": "1.0.0", "uptime_sec": 86400 }
```
→ `200 {"data":{"status":"online","maintenance":false}}` (MTA tarafı bakım bayrağını oyun içinde gösterebilir).

`POST /player/connected` · `POST /player/disconnected`
```json
{ "event_id": "3f6c…-uuid", "occurred_at": "2026-11-11T18:40:00Z",
  "account": { "ref": "1042", "name": "kaan" }, "session_seconds": 3600 }
```
`session_seconds` yalnızca disconnect'te gönderilir. → `200 {"data":{"duplicate":false}}`

`POST /accounts/sync`
```json
{ "event_id": "…", "accounts": [ { "ref": "1042", "name": "kaan", "last_seen_at": "…" } ] }
```
En fazla 100 hesap. Yalnızca **mevcut** `mta_accounts` güncellenir; bağlı olmayan hesaplar web'de oluşturulmaz (veri minimizasyonu).

`POST /characters/sync`
```json
{ "event_id": "…", "account_ref": "1042",
  "characters": [ { "ref": "c-551", "name": "Kaan Demir", "status": "active", "level": 12,
                    "playtime_minutes": 5400, "faction_key": "lspd", "job_key": "taxi",
                    "public_profile": { "bio": "…" }, "created_at": "…", "last_seen_at": "…" } ] }
```
Yalnızca web'e **bağlı** hesaplar için kabul edilir (bağlı değilse `200 {"data":{"ignored":true}}`). `public_profile` anahtarları allowlist dışındaysa düşürülür. `faction_key`/`job_key` web'deki `factions.slug` / `game_jobs.slug` ile eşleştirilir.

`POST /account-link/verify`
```json
{ "event_id": "…", "code": "K7Q4M2XD", "account": { "ref": "1042", "name": "kaan" } }
```
→ `200 {"data":{"linked":true,"web_username":"kaan_w"}}` · `422 LINK_CODE_INVALID` · `409 ACCOUNT_ALREADY_LINKED` · `429 RATE_LIMITED`

`POST /device-link/verify` (R2)
```json
{ "event_id": "…", "code": "…", "account": { "ref": "1042" }, "serial": "<32 hex>" }
```
→ `200 {"data":{"status":"pending_approval"}}`. `serial` asla loglanmaz ve hiçbir yanıtta yer almaz.

`GET /commands/pending` → `200 {"data":[{"id":"01JC…","type":"player.kick","payload":{"account_ref":"1042","reason":"…"},"expires_at":"…"}]}`

`POST /commands/result`
```json
{ "id": "01JC…", "status": "succeeded", "detail": { "applied_at": "…" } }
```
`status`: `succeeded | failed`. `failed` için `detail.error` (`player_offline`, `unsupported_command`, `invalid_payload`…).

### 7.3 Webhook'lar (`hooks`)

| Yöntem | URI | Ad | Açıklama | Faz |
|---|---|---|---|---|
| POST | `/webhooks/payments/{provider}` | `webhooks.payments` | Sağlayıcı imza doğrulaması → `payment_events` → kuyruk. Her zaman hızlı `200` (işleme asenkron). İmza hatalıysa `400` | 9 |
| GET | `/webhooks/whatsapp` | `webhooks.whatsapp.verify` | Meta doğrulama challenge'ı (`hub.verify_token`) | 10 |
| POST | `/webhooks/whatsapp` | `webhooks.whatsapp` | Teslimat durumları, opt-out (`STOP`) | 10 |

CSRF istisnaları (`bootstrap/app.php` › `validateCsrfTokens(except: [...])`): yalnızca `webhooks/*`. (`api/*` rotaları zaten `web` grubunda değildir.)

---

### 7.4 Gerçek zaman (Faz 10, Laravel Reverb)

| Yöntem | URI | Ad | Açıklama |
|---|---|---|---|
| POST | `/broadcasting/auth` | — | Laravel kanal yetkilendirmesi (`web` + `auth`). Yalnızca `BROADCAST_CONNECTION=reverb` iken istemci bağlanır |

Kanallar (`routes/channels.php`, ARCHITECTURE §9): `private-user.{id}` (yalnızca kendisi — bildirimler), `private-support.ticket.{ulid}` (talep sahibi veya kapsamlı personel), `private-staff.branch.{id}` (branş üyesi veya `support.all_branches`).

## 8. Hata ve durum kodları (web)

| Durum | Davranış |
|---|---|
| 401 (web) | `/login?intended=` yönlendirmesi (yalnızca göreli yol) |
| 403 | `errors/403` — "Bu işlem için yetkin yok." İzin adı gösterilmez |
| 404 | `errors/404`. `staff` middleware'i personel olmayanlara 404 döner |
| 419 | `errors/419` — "Oturumun zaman aşımına uğradı, sayfayı yenile." Form verisi korunur |
| 429 | `errors/429` — kalan süre |
| 503 | `errors/503` — bakım notu |

## 9. Rota testleri (zorunlu)

| Test | Kural |
|---|---|
| `RouteCatalogTest` | `route:list` ile bu doküman senkron (isim listesi `tests/Fixtures/routes.txt` üzerinden) |
| `AdminRouteProtectionTest` | Her `admin.*` rotası için izinsiz personelle istek → 403. İzinsiz oyuncuyla istek → 404 |
| `PanelOwnershipTest` | `{ulid}` alan her panel rotası için başka kullanıcının kaynağı → 404 (varlık ifşa edilmez) |
| `CsrfTest` | Her POST web rotası token'sız → 419 |
| `BridgeSecurityTest` | İmzasız / hatalı imza / eski zaman damgası / tekrar nonce → 401/401/401/409 |
| `GetIsSafeTest` | GET rotaları DB'de yazma yapmaz (izinli istisnalar: okundu bayrakları, `admin_activity_logs`) |
