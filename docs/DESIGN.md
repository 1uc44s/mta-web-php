# DESIGN — Görsel Dil ve Tasarım Sistemi

> **Sürüm:** 2.0.0 · **Son güncelleme:** 2026-11-13 · **Durum:** Onay bekliyor (proje sahibi isteği: grandroleplay.net benzeri yapı, birebir kopya değil)
> **İlgili:** [PRD](PRD.md) · [CMS §4](CMS.md#4-sistem-şablonları--sayfa-bölümleri-page-builder) · [SRS §4.2](SRS.md#42-erişilebilirlik-nfr-a11y--wcag-22-aa)

Bu doküman, istenen listeye ek olarak oluşturulmuştur. Proje sahibi tasarımın önemli olduğunu vurguladığı için görsel kararlar da tek doğruluk kaynağında tutulur. Kod içindeki token adları burada tanımlananlarla birebir aynıdır.

---

## 1. Tasarım konsepti: "Gün Batımı Bulvarı" (v2)

> v1 "Gece Haritası" (turkuaz, ince meridyen çizgileri) proje sahibinin isteğiyle 2026-11-13'te değiştirildi: site, grandroleplay.net'in **yapısal** dilini (yüzen hap menü, dev soluk arka plan kelimeleri, iki tonlu ağır başlık, cam kartlar, "yasal mı yeraltı mı" seçimi, geniş çağrı bandı) izler; **renk, metin, görsel ve kompozisyon ayrıntısı kopyalanmaz.** Referans sitenin mor/menekşe paleti, karakter illüstrasyonları, logo ve metinleri kullanılmaz.

San Andreas'ta güneşin batıp neon tabelaların yandığı saat: **mor değil, sıcak**. Zemin neredeyse siyah bir mürekkep moru, vurgu gün batımı turuncusu; yasal taraf sabah mavisi, yeraltı tarafı neon kırmızı.

| Fikir | Görsel karşılığı | Nerede |
|---|---|---|
| Gün batımı | Tek vurgu: turuncu (`accent`). Hero ve çağrı bandında **yumuşak turuncu ışıma** (radial), başka yerde ışıma yok | CTA, link, odak, hero, çağrı bandı |
| Dev tabela | Bölüm arkasında çok büyük, çok soluk (≈ %4) tek kelime (`x-ui.ghost-word`) — markanın kısa adı veya bölümün kelimesi | Hero, seçim bölümü, çağrı bandı, iç sayfa başlığı |
| Ufuk | Hero'da kodla çizilmiş şehir silueti: çizgili güneş diski, kuleler, palmiyeler (`x-brand.skyline`). Sahte ekran görüntüsü / stok karakter yok | Ana sayfa hero |
| Cam | Yarı saydam yüzey + `backdrop-filter: blur` + ince açık kenar (`x-ui.glass`) | Menü hapı, özellik kartları, durum kartı |
| İki yol | Yasal (`info` mavisi) ve yeraltı (`danger` kırmızısı) iki büyük panel, ortada "ya da" | `lifestyle_split` |
| Plaka | Sunucu adresi ve bağlama kodu mono plakası (değişmedi) | Sunucu kartı, panel |

**Kaçınılacaklar:** referans sitenin mor paleti ve görselleri, gökkuşağı gradyanı, metin içinde gradyan, her karta ışıma, ağır paralaks, abartılı ünlemler. Düşük efekt modunda (`data-effects=low`) blur ve ışımalar kapanır, kartlar opak olur.

**Ton:** Sinematik ve davetkâr; yine de iyi işletilen bir şehir: kurallar, destek ve güvenlik ciddiye alınır.

## 2. Marka

| Öğe | Karar |
|---|---|
| Ad | **ACP → Marka** ekranından değiştirilir (`site.name`, `site.short_name`). Kod içinde marka adı sabit yazılmaz: arayüz `config('app.name')` okur, uygulama açılışta bu değeri `site.name` ayarından alır (`App\Support\Brand`). Varsayılan çalışma adı "Meridyen Roleplay" / "Meridyen" (D-13, OD-01) |
| Logo | ACP'den yüklenir (PNG/JPG/WebP, ≤ 2 MB). Sunucu yeniden kodlar (WebP, en fazla 640×200, metadata silinir); SVG kabul edilmez (XSS). Yüklenmemişse kodla çizilen varsayılan işaret + kısa ad gösterilir |
| Sekme ikonu (favicon) | ACP'den yüklenir (PNG/JPG/WebP, en az 64×64, ≤ 1 MB). Sunucu kare kırpıp PNG olarak 32, 180, 192 ve 512 px üretir: tarayıcı sekmesi, Apple dokunma ikonu ve PWA manifesti bu setten beslenir. Yüklenmemişse `favicon.svg` |
| Slogan | `site.tagline` (ACP → Marka) |
| Ses | Kısa, doğrudan, ikinci tekil şahıs ("Hesabını bağla"), samimi ama ciddi. Abartı ("EN İYİ SUNUCU!!!") kullanılmaz |
## 3. Token'lar

Tailwind v4 `@theme` ile tanımlanır (`resources/css/app.css`). Renkler OKLCH'de yazılır, hex karşılıkları referans içindir. Bileşenler **yalnızca semantik token** kullanır, ham renk kullanmaz.

### 3.1 Renk — semantik token'lar

| Token | Dark (varsayılan) | Light | Kullanım |
|---|---|---|---|
| `--color-bg` | `#0E0B10` | `#F7F3F1` | Sayfa zemini |
| `--color-surface-1` | `#17121A` | `#FFFFFF` | Kart, header |
| `--color-surface-2` | `#1E1822` | `#F2ECE9` | İç içe yüzey, input |
| `--color-surface-3` | `#282030` | `#E9E1DD` | Hover, seçili satır |
| `--color-border` | `#30283A` | `#E0D7D2` | Varsayılan çizgi |
| `--color-border-strong` | `#42384D` | `#CBBFB9` | Ayırıcı, kart vurgusu (dekoratif) |
| `--color-border-input` | `#776B83` | `#857A75` | Input, checkbox ve select sınırı (≥ 3:1) |
| `--color-text` | `#F5EFF4` | `#17110F` | Ana metin |
| `--color-text-muted` | `#BDB1BF` | `#514743` | İkincil metin |
| `--color-text-subtle` | `#9C90A0` | `#645955` | Yardımcı metin, placeholder |
| `--color-accent` | `#FF7A45` | `#B8430F` | Birincil eylem, link, odak, gün batımı |
| `--color-accent-hover` | `#FF9A6E` | `#9C3609` | |
| `--color-accent-fg` | `#220B02` | `#FFFFFF` | Accent dolgu üzerindeki metin |
| `--color-accent-soft` | `rgb(255 122 69 / 0.14)` | `rgb(184 67 15 / 0.10)` | Seçili durum zemini, rozet zemini |
| `--color-amber` | `#FFC65C` | `#8F5600` | İkincil vurgu (sınırlı) |
| `--color-amber-soft` | `rgb(255 198 92 / 0.12)` | `rgb(143 86 0 / 0.10)` | |
| `--color-success` | `#3DDC97` | `#137A47` | |
| `--color-warning` | `#FFC65C` | `#8F5600` | **Her zaman ikon + metinle** |
| `--color-danger` | `#FF5468` | `#C0283B` | Hata; seçim bölümünde "yeraltı" |
| `--color-info` | `#5EC8FF` | `#1D62B5` | Bilgi; seçim bölümünde "yasal" |
| `--color-internal` | `#FFC65C` | `#8F5600` | İç not çerçevesi (§6.3) |
| `--color-grid-line` | `rgb(245 239 244 / 0.05)` | `rgb(23 17 15 / 0.06)` | Hayalet kelime, ince çizgi |
| `--color-glass` | `rgb(30 24 34 / 0.62)` | `rgb(255 255 255 / 0.72)` | Cam yüzey |
| `--color-glow` | `rgb(255 122 69 / 0.22)` | `rgb(184 67 15 / 0.12)` | Hero / çağrı bandı ışıması |
**Kontrast kontrol tablosu** — WCAG 2.x göreli parlaklık formülüyle ölçülmüştür (2026-11-13, v2 paleti). `tests/Unit/Design/ContrastTest.php` aynı hesabı `resources/css/app.css` token dosyasından yapar ve eşik altına düşen çiftte testi başarısız kılar:

| Çift | Dark | Light | Eşik |
|---|---|---|---|
| text / bg | 17.3:1 | 17.0:1 | ≥ 4.5 |
| text-muted / surface-1 | 9.0:1 | 9.0:1 | ≥ 4.5 |
| text-subtle / surface-1 | 6.1:1 | 6.8:1 | ≥ 4.5 |
| text-subtle / surface-3 (en kötü durum: hover satırı) | 5.2:1 | 5.3:1 | ≥ 4.5 |
| accent / bg (link, odak halkası) | 7.6:1 | 5.0:1 | ≥ 4.5 |
| accent / surface-1 | 7.1:1 | 5.5:1 | ≥ 4.5 |
| accent-fg / accent (birincil buton) | 7.3:1 | 5.5:1 | ≥ 4.5 |
| amber / surface-1 | 11.9:1 | 6.0:1 | ≥ 4.5 |
| danger / surface-1 | 5.9:1 | 5.8:1 | ≥ 4.5 |
| success / surface-1 | 10.4:1 | 5.4:1 | ≥ 4.5 |
| info / surface-1 | 9.8:1 | 6.1:1 | ≥ 4.5 |
| border-input / surface-2 (input sınırı) | 3.5:1 | 3.6:1 | ≥ 3.0 |

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
| Glow | Odak halkası, canlı nokta ve **yalnızca** hero + çağrı bandındaki `--color-glow` radial ışıması (v2). Kartlarda glow yok; düşük efekt modunda ışıma kapanır |
| Cam (v2) | `--color-glass` + `backdrop-filter: blur(14px)` + 1 px `border` — menü hapı, özellik kartları, durum kartı. Düşük efekt modunda opak `surface-1` |
| Radius (v2, public) | Menü hapı ve butonlar `pill`; public kartlar `24px` (`--radius-xl`); panel/ACP bileşenleri v1 radius değerlerini korur |

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

### 7.1 Public site (v2)
- **Header:** sayfanın üstünde 12 px boşlukla **yüzen cam hap** (`radius-pill`, `max-width: 1240px`). Sol: logo (yüklenen görsel veya varsayılan işaret + kısa ad). Orta: navigasyon hapları; aktif sayfa dolu accent hap. Sağ: durum hapı, tema menüsü, "Giriş yap" / "Oyuncu paneli" dolu hap buton. < 1024 px'te hamburger → tam ekran drawer.
- **Hero:** `min-height: min(92svh, 900px)`. Arka planda markanın kısa adı dev hayalet kelime olarak, sağ altta turuncu ışıma. Sol 7 kolon: durum hapı, iki tonlu display başlık (`title` metin rengi, `title_accent` accent), açıklama, iki hap buton, üç kısa "ilke". Sağ 5 kolon: kodla çizilmiş şehir ufku (`x-brand.skyline`) ve üzerinde cam sunucu durum kartı.
- **Bölüm ritmi:** bölümler `bg` zeminde; ayrım boşluk ve hayalet kelimeyle. Seçim bölümü iki renkli tam genişlik panel, çağrı bandı accent ışımalı geniş kart.
- **İç sayfa başlığı (`x-ui.page-header`):** breadcrumb + h1 + giriş; arkasında sayfa adının hayalet kelimesi.
- **Footer:** üstte tam genişlik dev marka adı (soluk), altında 4 kolon (marka + slogan, hızlı bağlantılar, topluluk, yasal) ve alt satır (telif, veri sorumlusu, "Rockstar Games veya Take-Two ile bağlantılı değildir").
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
