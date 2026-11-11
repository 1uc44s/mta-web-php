# DESIGN — Görsel Dil ve Tasarım Sistemi

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [PRD](PRD.md) · [CMS §4](CMS.md#4-sistem-şablonları--sayfa-bölümleri-page-builder) · [SRS §4.2](SRS.md#42-erişilebilirlik-nfr-a11y--wcag-22-aa)

Bu doküman, istenen listeye ek olarak oluşturulmuştur. Proje sahibi tasarımın önemli olduğunu vurguladığı için görsel kararlar da tek doğruluk kaynağında tutulur. Kod içindeki token adları burada tanımlananlarla birebir aynıdır.

---

## 1. Tasarım konsepti: "Gece Haritası"

**Meridyen** adı bir haritanın boylam çizgilerinden gelir. Görsel dil bu fikri şehrin gece hâliyle birleştirir: **antrasit asfalt** üzerinde bir **navigasyon ekranı**. Turkuaz rota çizgisi oyuncuyu hedefine götürür, sodyum lambası amberi önemli noktaları işaretler, ince meridyen çizgileri de her şeyi bir haritanın üzerindeymiş gibi hissettirir.

| Fikir | Görsel karşılığı | Nerede |
|---|---|---|
| Harita / meridyen | 1 px ince boylam ve enlem çizgileri, düşük opaklık | Hero arka planı, boş durumlar, 404 |
| Koordinat | Monospace küçük büyük harf "eyebrow" etiketler: `§02 · OYUN SİSTEMLERİ` | Bölüm başlıklarının üstü |
| Rota | Kesikli turkuaz çizgiyle birbirine bağlanan numaralı duraklar | `/start`, onboarding adımları, başvuru durum çizelgesi |
| Neon tabela | Tek vurgu rengi turkuaz. Yalnızca etkileşim ve "canlı" anlamı taşır | CTA, link, odak, canlı rozet |
| Sodyum lamba | Sınırlı amber | Öne çıkan haber, etkinlik, uyarı, "Yeni" rozeti |
| Plaka | Çerçeveli monospace bilgi kutusu | Sunucu adresi, bağlama kodu, ticket no |

**Kaçınılacaklar (brief §3.1):** Her yerde glow, gökkuşağı gradyanları, ağır parallax, mor-pembe "cyberpunk" klişesi, referans sitenin renk ve kompozisyonu, sahte ekran görüntüleri ya da stok "gamer" görselleri.

**Ton:** Sakin, güvenilir ve sinematik. Bağıran bir oyun sitesi değil, iyi işletilen bir şehrin belediye binası ile gece kulübü arasında bir yer.

## 2. Marka

| Öğe | Karar |
|---|---|
| Ad | Meridyen Roleplay (çalışma adı, D-13). Kısa: **Meridyen**, kısaltma **MRP** |
| Logo | Wordmark `MERİDYEN` (Archivo, genişlik 125, ağırlık 800, harf aralığı +0.04em) + işaret: bir dairenin içinde sağa kaydırılmış tek boylam yayı (küre üzerindeki meridyen). İşaret turkuaz, wordmark metin rengindedir |
| İşaret kullanımı | Favicon, PWA ikonu (maskable: işaret %60 güvenli alanda), avatar yer tutucusu |
| Slogan (taslak) | "Şehrin ritmi, senin hikâyen." (ACP'den değiştirilebilir) |
| Ses | Kısa, doğrudan, ikinci tekil şahıs ("Hesabını bağla"), samimi ama ciddi. Ünlem işareti ve abartı ("EN İYİ SUNUCU!!!") kullanılmaz |

Logo SVG dosyaları Faz 2'de `resources/svg/brand/` altında üretilir. Logo, marka adı değişince yeniden çizilir (OD-01).

## 3. Token'lar

Tailwind v4 `@theme` ile tanımlanır (`resources/css/app.css`). Renkler OKLCH'de yazılır, hex karşılıkları referans içindir. Bileşenler **yalnızca semantik token** kullanır, ham renk kullanmaz.

### 3.1 Renk — semantik token'lar

| Token | Dark (varsayılan) | Light | Kullanım |
|---|---|---|---|
| `--color-bg` | `#0B0D10` | `#F5F6F7` | Sayfa zemini |
| `--color-surface-1` | `#12151A` | `#FFFFFF` | Kart, header |
| `--color-surface-2` | `#181C22` | `#F0F2F4` | İç içe yüzey, input |
| `--color-surface-3` | `#20252D` | `#E7EAEE` | Hover, seçili satır |
| `--color-border` | `#262C35` | `#DDE1E6` | Varsayılan çizgi |
| `--color-border-strong` | `#343B46` | `#C5CBD3` | Ayırıcı, kart vurgusu (dekoratif) |
| `--color-border-input` | `#606A79` | `#7D8693` | Input, checkbox ve select sınırı (WCAG 1.4.11 için ≥ 3:1) |
| `--color-text` | `#E9ECEF` | `#0E1116` | Ana metin |
| `--color-text-muted` | `#A3ACB9` | `#4A5361` | İkincil metin |
| `--color-text-subtle` | `#858F9C` | `#5F6875` | Yardımcı metin, placeholder |
| `--color-accent` | `#2DE2C8` | `#0A7C6D` | Birincil eylem, link, odak, "canlı" |
| `--color-accent-hover` | `#62EDD8` | `#08695C` | |
| `--color-accent-fg` | `#03201B` | `#FFFFFF` | Accent dolgu üzerindeki metin |
| `--color-accent-soft` | `rgb(45 226 200 / 0.12)` | `rgb(10 124 109 / 0.10)` | Seçili durum zemini, rozet zemini |
| `--color-amber` | `#FFB547` | `#9A5B00` | İkincil neon (sınırlı) |
| `--color-amber-soft` | `rgb(255 181 71 / 0.12)` | `rgb(154 91 0 / 0.10)` | |
| `--color-success` | `#3DD68C` | `#147A45` | |
| `--color-warning` | `#FFB547` | `#9A5B00` | Amber ile aynı ton. **Her zaman ikon + metinle** kullanılır |
| `--color-danger` | `#F2555A` | `#C42B30` | |
| `--color-info` | `#6CB6FF` | `#1F63B8` | |
| `--color-internal` | `#FFB547` | `#9A5B00` | İç not çerçevesi (§6.6) |
| `--color-grid-line` | `rgb(233 236 239 / 0.05)` | `rgb(14 17 22 / 0.06)` | Meridyen çizgileri |

**Kontrast kontrol tablosu** — WCAG 2.x göreli parlaklık formülüyle ölçülmüştür (2026-11-11). Faz 2'de `tests/Unit/Design/ContrastTest.php` aynı hesabı token dosyasından yapar ve eşik altına düşen çiftte testi başarısız kılar:

| Çift | Dark | Light | Eşik |
|---|---|---|---|
| text / bg | 16.4:1 | 17.5:1 | ≥ 4.5 |
| text-muted / surface-1 | 8.0:1 | 7.8:1 | ≥ 4.5 |
| text-subtle / surface-1 | 5.6:1 | 5.6:1 | ≥ 4.5 |
| text-subtle / surface-3 (en kötü durum: hover satırı) | 4.7:1 | 4.7:1 | ≥ 4.5 |
| accent / bg (link, odak halkası) | 11.9:1 | 4.7:1 | ≥ 4.5 |
| accent / surface-1 | 11.2:1 | 5.1:1 | ≥ 4.5 |
| accent-fg / accent (birincil buton) | 10.5:1 | 5.1:1 | ≥ 4.5 |
| amber / surface-1 | 10.4:1 | 5.4:1 | ≥ 4.5 |
| danger / surface-1 | 5.4:1 | 5.6:1 | ≥ 4.5 |
| success / surface-1 | 9.8:1 | 5.4:1 | ≥ 4.5 |
| info / surface-1 | 8.5:1 | 5.9:1 | ≥ 4.5 |
| border-input / surface-2 (input sınırı) | 3.1:1 | 3.3:1 | ≥ 3.0 |

> `border` ve `border-strong` yalnızca dekoratif ayırıcıdır (~1.6:1). Bir bileşenin sınırını tanımlamak için **kullanılmaz**; bu iş için `border-input` kullanılır. Gelecekte bir token değişirse eşik değiştirilmez, token ayarlanır.

### 3.2 Tipografi

| Rol | Font | Ayar |
|---|---|---|
| Display | **Archivo Variable** | `font-stretch: 125%` (Expanded), ağırlık 700–800, harf aralığı -0.01em |
| Başlık | Archivo Variable | `font-stretch: 112%`, ağırlık 650–700 |
| Gövde | Archivo Variable | `font-stretch: 100%`, ağırlık 400 / 500, `font-feature-settings: "ss01"` (varsa) |
| Mono | **JetBrains Mono Variable** | Adres, kod, ID, koordinat eyebrow, tablo sayıları (`tabular-nums`) |

- İki font da Türkçe karakter setini (ç, ğ, ı, İ, ö, ş, ü) tam destekler. Fontlar self-host edilir, `font-display: swap` ve `size-adjust` ile CLS önlenir. Yalnızca `latin` + `latin-ext` alt kümeleri yüklenir.
- `<html lang="tr">` sayesinde `text-transform: uppercase` "i → İ" dönüşümünü doğru yapar. Büyük harf yalnızca eyebrow ve kısa etiketlerde kullanılır.

**Ölçek** (akışkan, `clamp`, 360 → 1440 px):

| Token | Boyut | Satır yüksekliği | Kullanım |
|---|---|---|---|
| `--text-display` | `clamp(2.5rem, 1.6rem + 4.2vw, 5.25rem)` | 1.0 | Hero başlığı |
| `--text-h1` | `clamp(2rem, 1.5rem + 2.2vw, 3.25rem)` | 1.1 | Sayfa başlığı |
| `--text-h2` | `clamp(1.5rem, 1.25rem + 1.2vw, 2.25rem)` | 1.15 | Bölüm başlığı |
| `--text-h3` | `clamp(1.2rem, 1.1rem + 0.4vw, 1.5rem)` | 1.25 | Kart başlığı |
| `--text-lg` | 1.125rem | 1.6 | Giriş paragrafı |
| `--text-base` | 1rem | 1.6 | Gövde (min 16 px) |
| `--text-sm` | 0.875rem | 1.5 | Tablo, yardımcı |
| `--text-xs` | 0.75rem | 1.4 | Eyebrow (mono, büyük harf, `letter-spacing: 0.12em`) |

Okuma genişliği: gövde metni `max-width: 68ch`.

### 3.3 Boşluk, ızgara, radius, gölge

| Token | Değer |
|---|---|
| Boşluk tabanı | 4 px (Tailwind varsayılanı). Bölüm dikey boşluğu: `clamp(4rem, 3rem + 4vw, 8rem)` |
| Container | `max-width: 1240px`, yan boşluk `clamp(1rem, 0.5rem + 2vw, 2rem)` |
| Izgara | Public 12 kolon / 24 px boşluk. Mobilde 4 kolon / 16 px |
| `--radius-sm` | 6 px — input, rozet, küçük buton |
| `--radius-md` | 10 px — kart, buton |
| `--radius-lg` | 14 px — modal, drawer, büyük panel |
| `--radius-pill` | 999 px — durum hapı |
| Gölge (dark) | Kullanılmaz. Derinlik **yüzey tonu + 1 px border + üst kenarda 1 px iç parlaklık** (`inset 0 1px 0 rgb(255 255 255 / 0.04)`) ile verilir |
| Gölge (light) | `--shadow-1: 0 1px 2px rgb(14 17 22 / .06), 0 1px 1px rgb(14 17 22 / .04)`; `--shadow-2` (popover/modal): `0 12px 32px -8px rgb(14 17 22 / .18)` |
| Glow | **Tek yer:** odak halkası (`0 0 0 2px var(--color-bg), 0 0 0 4px var(--color-accent)`) ve canlı nokta. Başka glow yok |

### 3.4 Hareket

| Token | Değer | Kullanım |
|---|---|---|
| `--dur-fast` | 120 ms | Hover, basma |
| `--dur-base` | 200 ms | Açılır menü, toast |
| `--dur-slow` | 320 ms | Modal, drawer |
| `--ease-out` | `cubic-bezier(0.2, 0.8, 0.2, 1)` | Giriş |
| `--ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Çıkış |

- Sayfa geçişleri: View Transitions API ile 150 ms cross-fade (destekleyen tarayıcılarda). Desteklemeyen tarayıcıda geçiş yoktur.
- Canlı nokta: 2 sn'lik yumuşak nabız. Hero arka plan videosu ≤ 8 sn, sessiz, döngülü.
- **`prefers-reduced-motion: reduce` veya `data-effects="low"`**: tüm `animation` ve `transition` süreleri `0.01ms`'ye iner, video yerine poster gösterilir, `backdrop-filter` kapatılır, View Transitions devre dışı kalır. Nabız animasyonu yerine statik nokta kullanılır.

## 4. Tema sistemi

| Konu | Karar |
|---|---|
| Modlar | `dark` (varsayılan), `light`, `system` |
| Uygulama | `<html data-theme="dark|light" data-effects="full|low">`. Token'lar `[data-theme=light]` seçicisiyle değişir |
| Flaş önleme | Sunucu, `theme` cookie'sini (veya kullanıcı tercihini) okuyup `data-theme` değerini HTML'e **render eder**. `system` seçiliyse `<head>` içindeki nonce'lu küçük satır içi script `matchMedia` ile değeri boyamadan önce ayarlar. `<meta name="color-scheme" content="dark light">` ve `body` zemin rengi CSS'in ilk satırlarındadır |
| Geçiş | Tema değişirken renk geçişi **uygulanmaz** (flaş ve titreme önlenir). Değişim anlıktır |
| Kalıcılık | Misafir: cookie (1 yıl). Girişli: `users.theme` + cookie |
| Düşük efekt | Header'daki tema menüsünde "Düşük efekt" anahtarı. `prefers-reduced-motion` varsayılan olarak bu modu açar |

## 5. İkonografi ve görseller

- **Lucide**, 20 px, `stroke-width: 1.75`. Metinle aynı rengi kullanır (`currentColor`). Tek başına anlam taşıyan ikonda `aria-label`, dekoratif ikonda `aria-hidden="true"` zorunludur.
- **Oyun içi görseller:** Yalnızca gerçek oyun içi ekran görüntüleri kullanılır. Ortak renk düzeni (soğuk gölgeler + amber ışıklar) için hafif bir LUT uygulanır. Görsel üzerine okunabilirlik için `bg → transparent` gradyan katmanı eklenir.
- **Görsel yoksa:** Sahte görsel yerine meridyen ızgaralı soyut yer tutucu (SVG) kullanılır. Yer tutucu bir ekran görüntüsü gibi görünmez.
- **Avatar:** Kullanıcı adı baş harfi + deterministik nötr ton (kullanıcı adı hash'i → 6 sabit tondan biri). Kullanıcı yüklemeli avatar R2'ye bırakılmıştır.

## 6. Bileşen kütüphanesi

Blade bileşenleri `resources/views/components/ui/` altındadır. Her bileşenin durumları: **varsayılan, hover, focus-visible, active, disabled, loading** (ilgiliyse **error, empty**). Her bileşen hem dark hem light temada ve 360 px'te test edilir.

### 6.1 Temel bileşenler

| Bileşen | Varyantlar / notlar |
|---|---|
| `x-ui.button` | `primary` (accent dolgu), `secondary` (surface-2 + border), `ghost`, `danger`, `link`. Boyutlar `sm` (32 px), `md` (40 px), `lg` (48 px). Dokunma hedefi her zaman ≥ 44 px (gerekirse görünmez dolgu). `loading` durumunda `aria-busy`, spinner ve `disabled`. İkon + metin aralığı 8 px |
| `x-ui.input`, `textarea`, `select`, `checkbox`, `radio`, `switch` | Görünür etiket her zaman bulunur (placeholder etiket yerine geçmez). Yardım metni ve hata `aria-describedby` ile bağlanır. Hata durumu: danger border + ikon + metin |
| `x-ui.field` | Etiket + kontrol + yardım + hata sarmalayıcısı. Zorunlu alan `*` + gizli "zorunlu" metni |
| `x-ui.card` | `plain`, `interactive` (tamamı tıklanabilir; tek bir `<a>` + `::after` örtüsü), `inset` |
| `x-ui.badge` | `neutral`, `accent`, `amber`, `success`, `warning`, `danger`, `info`. Durum rozetleri ikon içerir |
| `x-ui.status-pill` | Sunucu durumu: `online` (accent nokta + nabız), `stale` (amber, "Doğrulanamıyor · 3 dk önce"), `offline` (gri, içi boş daire), `maintenance` (amber anahtar ikonu) |
| `x-ui.modal`, `x-ui.drawer` | Focus trap, `Esc`, arka plan tıklamasıyla kapanma (formlu modalda yalnızca onayla), `aria-labelledby`, kaydırma kilidi. Mobilde modal alt sayfa (bottom sheet) olur |
| `x-ui.confirm` | Yıkıcı işlemler için. Kritik işlemlerde hedefin adını yazarak onaylama (`type-to-confirm`) seçeneği |
| `x-ui.toast` | `aria-live="polite"` (hatada `assertive`). Sağ alt (mobilde üst). 5 sn, hover/focus'ta durur. En fazla 3 toast üst üste |
| `x-ui.tabs` | Roving tabindex ve ok tuşları (WAI-ARIA tabs deseni). URL hash ile senkron |
| `x-ui.table` | Sticky başlık, sıralanabilir başlık (`aria-sort`), satır seçimi. < 768 px'te **kart listesine** dönüşür (her satır bir kart, etiket: değer çiftleri) |
| `x-ui.pagination` | Önceki/sonraki + sayfa numaraları. Mobilde yalnızca önceki/sonraki + "Sayfa 3 / 12" |
| `x-ui.filter-bar` | Arama + filtre çipleri + "Filtreleri temizle". Filtreler GET parametresidir (paylaşılabilir URL) |
| `x-ui.skeleton` | İçerik şekline uyan iskelet. Shimmer animasyonu düşük efekt modunda kapalıdır |
| `x-ui.empty-state` | Meridyen ızgaralı küçük illüstrasyon + başlık + açıklama + tek birincil CTA |
| `x-ui.error-state` | Ne oldu + ne yapabilirsin + "Tekrar dene" + request ID (mono, kopyalanabilir) |
| `x-ui.breadcrumb` | `nav[aria-label="Konum"]`. Son öğe `aria-current="page"` |
| `x-ui.avatar`, `x-ui.user-menu` | |
| `x-ui.file-upload` | Sürükle-bırak + buton. Tür ve boyut ipucu, ilerleme, önizleme, kaldır. Klavye ile tamamen kullanılabilir |
| `x-ui.date-range` | İki yerel `<input type="date">` + hazır aralıklar (Bugün, 7 gün, 30 gün). Özel takvim widget'ı yapılmaz (erişilebilirlik ve boyut için) |
| `x-ui.callout` | `not`, `ipucu`, `uyari` (Markdown kısa kodlarıyla aynı) |
| `x-ui.image` | `media` modelinden `srcset`. Bkz. CMS §3.3 |

### 6.2 İmza bileşenleri

**`x-brand.address-plate` — Sunucu adresi plakası**
```text
┌───────────────────────────────────────────┐
│ SUNUCU                                ● 87│  ← eyebrow + canlı oyuncu sayısı
│ play.meridyen.gg:22003         [Kopyala]  │  ← mono, büyük; kopyala butonu
└───────────────────────────────────────────┘
   [ MTA ile aç ]   Nasıl bağlanırım? →
```
1 px `border-strong` çerçeve, `radius-sm`, iç dolgu 16 px. Kopyalandığında buton etiketi 2 sn boyunca "Kopyalandı ✓" olur ve `aria-live` ile duyurulur.

**`x-brand.route-steps` — Rota adımları**
Numaralı duraklar (32 px daire, mono rakam) arasında 2 px kesikli turkuaz çizgi bulunur. Tamamlanan durak dolu accent, aktif durak halkalı, gelecek durak `border-strong` olur. Masaüstünde yatay, < 768 px'te dikey. Çizgi yalnızca dekoratiftir (`aria-hidden`). Liste semantiği `<ol>` ile sağlanır.

**`x-brand.eyebrow` — Koordinat etiketi**
`§02 · OYUN SİSTEMLERİ` — mono, `text-xs`, büyük harf, `text-subtle`, `§` işareti accent renkte. Bölüm numarası otomatik üretilir.

**`x-brand.link-code` — Bağlama kodu**
Sekiz karakter, iki grup halinde (`K7Q4 – M2XD`), her karakter ayrı hücrede, mono `text-h2`. Altında dairesel geri sayım ve "Oyunda şunu yaz: `/webbagla K7Q4M2XD`" satırı + kopyala butonu. Süre dolunca hücreler soluklaşır ve "Yeni kod oluştur" butonu görünür.

**`x-brand.meridian-grid` — Arka plan ızgarası**
SVG `pattern`: 1 px boylam çizgileri (hafif kavisli, bir küre yüzeyi hissi) + seyrek enlem çizgileri, `--color-grid-line`. Hero'da imleç hareketiyle **çok hafif** (≤ 6 px) paralaks yapar. Düşük efekt modunda statiktir.

### 6.3 Destek mesaj dizisi (kritik ayrım)
- **Oyuncu mesajı:** sola hizalı, `surface-2` zemin.
- **Personel public yanıtı:** sağa hizalı, `accent-soft` zemin, "Destek Ekibi" etiketi + rol rozeti.
- **İç not (yalnızca ACP):** tam genişlik, **amber kesikli çerçeve**, 45° çapraz tarama deseni, kilit ikonu ve "Yalnızca personel görür" etiketi. Yanıt yazma alanında "Yanıt" / "İç not" sekmeleri vardır. İç not sekmesi seçiliyken yazma alanı da amber çerçeve alır ve gönder butonunun metni "İç not ekle" olur. Yanlışlıkla iç notun oyuncuya gitmesini ya da tersini görsel olarak imkânsız kılmak hedeflenir.

## 7. Düzen kalıpları

### 7.1 Public site
- **Header:** 64 px, `surface-1` + `backdrop-filter: blur(12px)` (düşük efekt modunda opak), kaydırınca alt border belirir. Sol: logo. Orta: navigasyon (≥ 1024 px). Sağ: durum hapı, tema menüsü, Discord ikonu, "Oyuncu Paneli" butonu. < 1024 px'te hamburger → tam ekran drawer.
- **Hero:** Tam genişlik, min-height `min(88svh, 860px)`. Arka planda oyun görseli/videosu + soldan sağa koyulaşan gradyan + meridyen ızgarası. İçerik sol 7 kolonda: eyebrow (`§01 · SAN ANDREAS · ROLEPLAY`), display başlık (2 satır), açıklama (`text-lg`, `text-muted`), CTA'lar. Sağ 5 kolonda (≥ 1024 px) **yüzen durum kartı** (`server_status` mini) bulunur. Mobilde durum kartı CTA'ların altına iner.
- **Bölüm ritmi:** Bölümler arasında zemin değişimi yerine ince meridyen çizgisi ayırıcı kullanılır. Bir bölüm `surface-1` zemine sahipse sonraki `bg` olur (en fazla iki ton).
- **Footer:** 4 kolon (marka, hızlı bağlantılar, topluluk, yasal) + alt satır (telif, veri sorumlusu, "Bu sunucu Rockstar Games veya Take-Two ile bağlantılı değildir" ibaresi).

### 7.2 Oyuncu paneli
- Masaüstü: solda 264 px sidebar (logo, kullanıcı kartı, menü grupları, en altta sunucu durumu). Üst bar 56 px (breadcrumb, bildirim zili, tema, kullanıcı menüsü).
- Mobil: üst bar + **alt sekme çubuğu** (Genel, Destek, Karakterler, Bildirimler, Menü). "Menü" sekmesi tam menüyü drawer olarak açar. PWA standalone modunda alt çubuk safe-area'ya uyar.
- Dashboard: 12 kolon. Üstte selamlama + doğrulama/kural banner'ları, ardından 4'lü KPI kartı (mobilde 2×2), altta 8/4 bölünmüş "Son aktiviteler" ve "Duyurular / Hızlı işlemler".

### 7.3 Yönetim paneli
- Daha yoğun bir yerleşim kullanılır: gövde `text-sm` ağırlıklıdır, tablo satır yüksekliği 44 px'tir.
- Sidebar 248 px, 72 px ikon moduna daraltılabilir (tercih cookie'de). Sidebar başlığında **"YÖNETİM" etiketi ve amber üst şerit** bulunur: personel hangi alanda olduğunu her an bilir.
- Liste sayfası şablonu: başlık + birincil eylem → filtre çubuğu → tablo → sayfalama. Detay sayfası: başlık + durum rozeti + eylem menüsü → sekmeler → içerik + sağda meta paneli.
- KPI kutucuğu: büyük sayı (mono, `tabular-nums`) + etiket + altında `kaynak · 2 dk önce` dipnotu (FR-ADM-010).

## 8. Erişilebilirlik kontrol listesi (bileşen tamamlanma kriteri)

- [ ] Klavye ile ulaşılabilir ve kullanılabilir. Odak sırası mantıklı, odak halkası görünür.
- [ ] Rol, ad ve durum doğru (`button`, `aria-expanded`, `aria-pressed`, `aria-current`…).
- [ ] Renk dışında da ayırt edilebilir (ikon/metin).
- [ ] Dark ve light kontrastı ≥ 4.5:1 (metin), ≥ 3:1 (bileşen sınırı ve odak).
- [ ] %200 zoom ve 320 px genişlikte (WCAG 1.4.10) yatay kaydırma yok (tablolar hariç).
- [ ] Düşük efekt ve reduced-motion altında hareket yok.
- [ ] Dokunma hedefi ≥ 44 px.
- [ ] Hata mesajı ne olduğunu ve nasıl düzeltileceğini söylüyor.

## 9. Yazım kılavuzu (arayüz metni)

| Durum | Yap | Yapma |
|---|---|---|
| Buton | Fiil + nesne: "Kodu kopyala", "Ticket aç" | "Tamam", "Gönder" (bağlamsız) |
| Hata | "Bu kod süresi dolmuş. Yeni kod oluşturabilirsin." | "Hata 422" |
| Boş durum | "Henüz destek talebin yok. Bir sorun yaşarsan buradan yazabilirsin." + CTA | "Kayıt bulunamadı" |
| Onay | "Bu oturumu kapatmak istiyor musun? O cihazda tekrar giriş yapman gerekecek." | "Emin misiniz?" |
| Hitap | "sen" (samimi, tutarlı) | "siz"/"sen" karışık |
| Sayı ve tarih | "12 Kasım 2026, 21:40", "3 dk önce", "1.250 MK" | ISO tarih, İngilizce ay |

Tüm metinler `lang/tr` dosyalarındadır ve bu kılavuza göre yazılır.

## 10. Ekran envanteri (tasarım teslimatları)

Faz 2'de yüksek doğruluklu tasarım + kodlanmış prototip olarak hazırlanacak ekranlar (öncelik sırasıyla):

1. Ana sayfa (dark + light, 360 / 1440)
2. `/start` rehberi (rota adımları)
3. Kurallar (arama + derin bağlantı)
4. Giriş / kayıt / şifre sıfırlama / 2FA
5. Panel dashboard (boş durum + dolu durum)
6. Oyun hesabı bağlama (kod üretildi → bekleniyor → bağlandı → süresi doldu)
7. Destek: liste, oluştur, mesaj dizisi (oyuncu)
8. ACP: dashboard, ticket kuyruğu, ticket detayı (iç not ayrımıyla), kullanıcı detayı
9. ACP: içerik listesi + bölüm düzenleyici
10. Hata sayfaları (404 meridyen illüstrasyonu, 419, 429, 500, 503)
