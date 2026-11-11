# /docs — Tek Doğruluk Kaynağı (Single Source of Truth)

> **Proje:** Meridyen Roleplay (çalışma adı) — MTA:SA roleplay topluluk platformu
> **Doküman seti sürümü:** 1.0.0 · **Tarih:** 2026-11-11 · **Durum:** Onay bekliyor

Bu klasör projenin **tek doğruluk kaynağıdır**. Kod ile doküman çelişirse doküman doğrudur; kod düzeltilir ya da önce doküman güncellenip sonra kod değiştirilir. Her yeni özellik **önce** bu dokümanlara işlenir, **sonra** kodlanır (bkz. [RULES.md §1](RULES.md)).

## Okuma sırası

| # | Doküman | Ne cevaplar? |
|---|---|---|
| 1 | [PRD.md](PRD.md) | Ne yapıyoruz, kimin için, neden? Kullanıcı hikayeleri, metrikler, kabul kriterleri, kararlar |
| 2 | [SRS.md](SRS.md) | Sistem tam olarak ne yapmalı? Fonksiyonel / fonksiyonel olmayan / güvenlik gereksinimleri |
| 3 | [DATABASE.md](DATABASE.md) | Hangi tablolar, kolonlar, tipler, indexler, FK ve cascade kuralları? |
| 4 | [ERD.md](ERD.md) | Varlık ilişkileri (Mermaid) |
| 5 | [ARCHITECTURE.md](ARCHITECTURE.md) | Dizin yapısı, katmanlar, auth akışı, session, PWA yaşam döngüsü, MTA köprüsü |
| 6 | [ROUTES.md](ROUTES.md) | Tüm GET/POST endpoint'leri, middleware ve yetki katmanları |
| 7 | [CMS.md](CMS.md) | İçerik modeli, Akademi müfredatı (ders/konu), sistem şablonları, mesaj/WhatsApp şablon motoru |
| 8 | [ADMIN.md](ADMIN.md) | Süper Admin yetkileri, rol matrisi, branş (birim) atama mekanizması |
| 9 | [DESIGN.md](DESIGN.md) | Görsel dil, tasarım token'ları, bileşenler, ekran kalıpları |
| 10 | [ROADMAP.md](ROADMAP.md) | Geliştirme fazları ve çıkış kriterleri |
| 11 | [RULES.md](RULES.md) | Kod standartları, PDO/SQL injection, XSS/CSRF, test ve commit kuralları |
| 12 | [TODO.md](TODO.md) | Sıradaki görevler, test adımları, açık kararlar |
| 13 | [OPERATIONS.md](OPERATIONS.md) | Dağıtım, geri alma, yedek/geri yükleme, izleme, break-glass, kimlik bilgisi rotasyonu |

**Kaynak şartname:** [grandroleplay_benzeri_mta_roleplay_platform_teknik_sartname.md](grandroleplay_benzeri_mta_roleplay_platform_teknik_sartname.md) — proje sahibinin ilk brief'i. Bu set brief'i esas alır; brief'ten bilinçli sapmalar [PRD.md §10](PRD.md#10-kararlar-ve-brieften-sapmalar) içinde gerekçesiyle kayıtlıdır. Brief ile bu set çeliştiğinde **bu set** geçerlidir.

## Değişiklik kuralı

1. Değişiklik ihtiyacı → ilgili dokümanda güncelleme (commit başlığı `docs:` ile).
2. Doküman başlığındaki **sürüm** ve **son güncelleme** alanı artırılır (SemVer: yapı değişikliği = minor, düzeltme = patch).
3. Etkilenen diğer dokümanlar aynı commit'te senkronlanır (ör. yeni tablo → DATABASE + ERD; yeni endpoint → ROUTES + SRS).
4. Ancak bundan sonra kod yazılır; kod commit'i ilgili doküman bölümüne referans verir.

## Değişiklik geçmişi

| Sürüm | Tarih | Değişiklik |
|---|---|---|
| 1.0.0 | 2026-11-11 | İlk tam doküman seti oluşturuldu |
| 1.0.1 | 2026-11-11 | Faz 1 uygulama notları: ARCHITECTURE (global middleware, Intervention Image 4.x, `storage/{path}` kapalı), DATABASE §1.1 (bağlantı ayarları), TODO (Faz 1 durumu ve kanıtlar) |
