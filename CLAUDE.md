# Meridyen Roleplay — proje talimatları

MTA:SA roleplay topluluk platformu: public site, oyuncu paneli (`/panel`), yönetim paneli (`/admin`) ve MTA köprüsü (`/api/v1/game`). Laravel 13 monolith.

## Tek doğruluk kaynağı: `/docs`

- Kod yazmadan önce ilgili dokümanı oku. Başlangıç noktası `docs/README.md`.
- Yeni tablo/kolon, rota, izin, ayar veya özellik **önce** `/docs` içine işlenir, sonra kodlanır. Kod ile doküman çelişirse aynı değişiklikte düzelt.
- Kod kuralları: `docs/RULES.md`. Definition of Done: `docs/RULES.md` §12.
- Aktif faz ve görevler: `docs/TODO.md`. Faz planı: `docs/ROADMAP.md`.
- Bağlayıcı mimari kararlar: `docs/PRD.md` §10.

## Ortam (Windows + XAMPP)

- PHP: `C:\xampp\php\php.exe` (8.4). PATH'te `php` yoktur; bash'te `/c/xampp/php/php.exe`, Composer için `composer` (`~/bin`).
- Veritabanı: MariaDB 10.11 (`C:\xampp\mysql\bin\mysql.exe`), sürücü `mariadb`.
- Komutlar: `composer test`, `composer lint`, `composer analyse`, `npm run build`, `composer dev`.

## Kısa kurallar

- `declare(strict_types=1);`, ince controller, iş mantığı `app/Actions`, girdi yalnızca FormRequest `validated()`.
- Ham SQL yalnızca binding ile. `{!! !!}` yalnızca `SafeHtml`. CSP nonce'suz inline script yok, Alpine yalnızca CSP build.
- Arayüz metinleri `lang/tr` dosyalarında.
