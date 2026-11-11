# PRD — Ürün Gereksinimleri Dokümanı

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [SRS](SRS.md) · [ROADMAP](ROADMAP.md) · [DESIGN](DESIGN.md) · [Kaynak brief](grandroleplay_benzeri_mta_roleplay_platform_teknik_sartname.md)

---

## 1. Ürün vizyonu

**Meridyen Roleplay** (çalışma adı), MTA:SA üzerinde çalışan bir roleplay sunucusunun **dijital merkezidir**. Ziyaretçi burada sunucuyu birkaç dakikada tanır ve oyuna katılır. Oyuncu web hesabını, bağlı oyun hesaplarını, karakterlerini, bakiyesini, başvurularını ve destek taleplerini tek bir panelden yönetir. Yetkili ekip ise oyunculardan içeriğe, ekonomiden moderasyona kadar tüm operasyonu, her işlemi denetlenebilir biçimde tek bir yönetim panelinden yürütür.

> **Tek cümle:** "Sunucuyu keşfetmekten ilk karakterine, ilk destek talebinden ilk fraksiyon başvurusuna kadar her şey tek, güvenli ve dürüst bir platformda."

### 1.1 Ürün ilkeleri

1. **Dürüstlük:** Oyunda olmayan özellik varmış gibi gösterilmez. Doğrulanamayan veri (ör. eskimiş oyuncu sayısı) canlıymış gibi sunulmaz. Sahte yorum ya da sahte sayı kullanılmaz.
2. **Güvenlik varsayılandır:** Web hesabı ile oyun hesabı ayrı kimliklerdir. Bağlantı kanıtla kurulur. Her yetki backend'de kontrol edilir. Kritik işlemler iz bırakır.
3. **Denetlenebilirlik:** Para, rol, ceza ve ayar değişiklikleri silinmez. Düzeltme ters kayıtla yapılır ve her biri audit log'a yazılır.
4. **Dayanıklılık:** MTA sunucusu çökse bile web sitesi, giriş ve destek sistemi çalışmaya devam eder.
5. **Özgünlük:** Referans sitenin yalnızca akış fikirleri alınır. Marka, metin, görsel ve kod özgündür (brief §0.1).
6. **Önce temel:** Kimlik, veri modeli ve yetki doğru kurulmadan ödeme, bakiye, serial ve oyun içi komut modüllerine başlanmaz.

## 2. Problem tanımı

| Problem | Bugünkü durum (tipik RP topluluğu) | Hedef durum |
|---|---|---|
| Yeni oyuncu katılım sürtünmesi | Kurulum bilgisi Discord mesajlarına dağılmış | Tek bir `/start` rehberi, kopyalanabilir adres, `mtasa://` bağlantısı |
| Sunucu durumu belirsiz | Oyuncu sayısı ya yok ya da sahte/eskimiş | Heartbeat'e dayalı, tazeliği belirtilen durum kartı |
| Destek kaosu | DM ve Discord kanallarında kaybolan talepler | Kategori, branş, öncelik, durum ve geçmiş içeren ticket sistemi |
| Başvuru takibi yok | Google Form ve Discord rolleri | Panelden başvuru, durum geçmişi ve yetkili yanıtı |
| Yetki dağınıklığı | Herkes her şeyi yapabiliyor, iz yok | RBAC, branş ataması, MFA ve audit log |
| Finansal belirsizlik | Elle bakiye düzenleme | Ledger, idempotent webhook, dört göz onayı |

## 3. Hedefler ve hedef olmayanlar

### 3.1 Hedefler

- G1: Ziyaretçinin ana sayfadan oyuna bağlanma talimatına **≤ 2 tıklamada** ulaşması.
- G2: Kayıt → e-posta doğrulama → oyun hesabı bağlama hunisinin ölçülebilir ve yüksek dönüşümlü olması.
- G3: Destek taleplerinin ilk yanıt süresinin ölçülmesi ve kısaltılması.
- G4: Yetkili işlemlerinin %100'ünün backend izin kontrolünden geçmesi ve kritik olanların audit log'a yazılması.
- G5: Web ile MTA arasında imzalı, replay korumalı, idempotent veri akışı.
- G6: Tek geliştirici + AI ile sürdürülebilir, test edilebilir, modüler kod tabanı.

### 3.2 Hedef olmayanlar (bu ürünün yapmayacağı işler)

- Native mobil uygulama (PWA yeterli; bkz. [ARCHITECTURE §8](ARCHITECTURE.md)).
- Oyun dosyası / modifiye GTA arşivi dağıtımı. Yalnızca resmi ve yasal kaynaklara yönlendirme yapılır.
- Oyun veritabanına web'den doğrudan SQL yazma.
- Kart verisi saklama.
- Oyuncu parolasını yetkiliye gösterme veya MTA parola hash'lerini web'e taşıma.
- Forum yazılımı (topluluk Discord'da yaşar; web yalnızca bağlantı verir).

## 4. Personalar

| Kod | Persona | Amaç | Acı noktası | Birincil alan |
|---|---|---|---|---|
| P1 | **Meraklı Ziyaretçi** (16–30 yaş, YouTube/TikTok'tan geldi) | Sunucu nasıl bir yer, nasıl girerim? | Kurulum karmaşası, güven eksikliği | Public site |
| P2 | **Yeni Oyuncu** | Hesap aç, kuralları öğren, ilk karakteri oluştur | Kuralları bilmeden ceza yemek | `/start`, Akademi, UCP |
| P3 | **Aktif Oyuncu** | Karakter/başvuru/destek takibi, bakiye | Talebin kaybolması, belirsiz durum | UCP |
| P4 | **Destek Yetkilisi** | Kendi branşındaki ticket'ları hızla çözmek | Dağınık kanal, bağlam eksikliği | ACP › Destek |
| P5 | **Fraksiyon Yöneticisi** | Kendi fraksiyonunun başvurularını yönetmek | Başka fraksiyonların verisine gereksiz erişim | ACP › Başvurular (branş kapsamlı) |
| P6 | **Finans Yöneticisi** | Ödemeleri, iadeleri ve bakiye düzeltmelerini mutabık tutmak | Elle yapılan, izsiz düzeltmeler | ACP › Ekonomi / Ödemeler |
| P7 | **Süper Admin / Kurucu** | Sistemi, rolleri ve entegrasyonları güvenle yönetmek | Kim neyi değiştirdi bilinmiyor | ACP tamamı |

## 5. Kapsam ve sürüm planı

Fazların ayrıntısı [ROADMAP.md](ROADMAP.md) içindedir. Sürüm kapsamı:

| Modül | R1 — MVP | R2 | R3 |
|---|---|---|---|
| Public site (ana sayfa, hakkında, sistemler, meslekler, fraksiyonlar, start, kurallar, haberler, SSS, iletişim, yasal) | ✅ | | |
| Tasarım sistemi + dark/light tema + düşük efekt modu | ✅ | | |
| Kayıt, giriş, e-posta doğrulama, şifre sıfırlama, oturum yönetimi | ✅ | | |
| UCP: dashboard, bildirimler, güvenlik ayarları | ✅ | | |
| Web ↔ MTA hesap bağlama (oyun içi kod) | ✅ | | |
| Karakter listesi/detayı (salt okunur) | ✅ | | |
| Sunucu durumu (heartbeat) | ✅ | | |
| Destek talepleri (oyuncu + yetkili, iç not, makro) | ✅ | | |
| ACP: RBAC, branş ataması, MFA, kullanıcı yönetimi, CMS, duyurular, audit log, ayarlar | ✅ | | |
| Akademi (müfredat: ders → konu), kural onayı | ✅ (içerik + okuma takibi) | Sınav/quiz | |
| Mesaj şablon motoru: e-posta + panel | ✅ | Discord webhook | WhatsApp (opt-in) |
| Başvurular (sabit formlar) | | ✅ | Dinamik form oluşturucu |
| Ceza geçmişi, itiraz, moderasyon, izinli oyun komutları | | ✅ | |
| Cüzdan/ledger, ödeme adapter'ı, checkout, webhook, iade, dört göz düzeltme | | ✅ | Mutabakat raporları |
| Serial/cihaz ilişkilendirme | | ✅ | |
| PWA (kurulabilir panel, offline sayfa) | ✅ (temel) | Web Push | |
| Gerçek zaman (Reverb WebSocket) | Polling | ✅ | |
| Çoklu MTA sunucusu, çoklu dil, gelişmiş analitik | | | ✅ |

## 6. Kullanıcı hikayeleri ve kabul kriterleri

Format: **Kimlik · Hikaye · Kabul kriterleri (KK)**. Her hikaye SRS'teki bir veya daha fazla FR'ye bağlanır (izlenebilirlik matrisi: [SRS §9](SRS.md#9-i̇zlenebilirlik-matrisi)). Öncelik: **M** Must, **S** Should, **C** Could.

### 6.1 Public site (EPIC-PUB)

**US-PUB-01 (M) — Sunucuyu hızlıca anlamak**
Ziyaretçi olarak ana sayfada sunucunun ne sunduğunu tek bakışta görmek istiyorum ki katılıp katılmayacağıma karar vereyim.
- KK1: Hero alanında logo, slogan, tek paragraf açıklama, "Oyuna Katıl" ve "Nasıl Oynanır?" CTA'ları görünür. Bunların hepsi mobil 360 px'te ilk ekrana sığar.
- KK2: Hero metni, CTA etiketleri ve görselleri ACP › İçerik'ten kod yazmadan değiştirilebilir.
- KK3: LCP ≤ 2,5 sn (4G, orta segment mobil, Lighthouse).

**US-PUB-02 (M) — Oyuna katıl**
Ziyaretçi olarak "Oyuna Katıl"a basınca sunucu adresini kolayca almak istiyorum.
- KK1: Butona basınca bir panel açılır. Panelde `IP:PORT` monospace "plaka" bileşeninde gösterilir, "Kopyala" butonu panoya kopyalar ve "Kopyalandı" geri bildirimi verir (ekran okuyucuya `aria-live` ile duyurulur).
- KK2: `mtasa://IP:PORT` bağlantısı "MTA ile aç" olarak sunulur. MTA kurulu değilse `/start` rehberine yönlendiren açıklama görünür.
- KK3: Adres ve port, ACP › Ayarlar'dan gelir; kodda sabit değer yoktur.

**US-PUB-03 (M) — Dürüst sunucu durumu**
Ziyaretçi olarak sunucunun açık olup olmadığını ve kaç kişinin oynadığını doğru bilmek istiyorum.
- KK1: Son heartbeat 90 sn'den yeniyse durum **Çevrimiçi** olur; `oyuncu/kapasite` gösterilir.
- KK2: 90 sn – 5 dk arasında durum **Doğrulanamıyor** olur. Son bilinen sayı "X dk önce" ibaresiyle gri tonda gösterilir.
- KK3: 5 dk'dan eskiyse **Çevrimdışı** olur ve oyuncu sayısı gösterilmez.
- KK4: Admin bakım modunu açtıysa **Bakımda** ve bakım notu gösterilir.
- KK5: Durum yalnızca renkle anlatılmaz; ikon + metin etiketi birlikte kullanılır.

**US-PUB-04 (M) — Oyun sistemleri / meslekler / fraksiyonlar**
Ziyaretçi olarak oyundaki sistemleri, meslekleri ve fraksiyonları kategorili görmek istiyorum.
- KK1: Yalnızca `status=published` ve (sistemler için) `is_live_in_game=true` kayıtlar listelenir.
- KK2: Her detayda "son güncelleme" tarihi görünür.
- KK3: Fraksiyon detayında başvuru durumu (açık/kapalı) ve başvuru CTA'sı görünür. Başvuru için girişe yönlendirilir.

**US-PUB-05 (M) — Kurulum rehberi (`/start`)**
Yeni oyuncu olarak adım adım kurulum rehberi istiyorum.
- KK1: 7 adımlı rehber numaralı ve ilerleme göstergeli sunulur. Her adım Akademi'deki "Başlangıç" dersinin konusundan beslenir.
- KK2: Harici bağlantılar yalnızca resmi kaynaklara (multitheftauto.com) gider; üçüncü taraf arşiv bağlantısı yoktur.

**US-PUB-06 (M) — Kurallar**
Oyuncu olarak kategori bazlı, aranabilir ve güncel kuralları okumak istiyorum.
- KK1: Kategoriler sıralı listelenir. Her kural `kod` (ör. `RP-4.2`) ile derin bağlantılanabilir (`/rules#RP-4.2`).
- KK2: Sayfa içi arama istemci tarafında anında filtreler.
- KK3: Kural seti sürümü değişince giriş yapmış oyuncuya panelde "Kurallar güncellendi, onayla" bildirimi çıkar.

**US-PUB-07 (M) — Haberler** · Listeleme, kategori, detay, kapak görseli, yayın tarihi ve SEO meta. Zamanlanmış haber vaktinde kendiliğinden yayına girer (±1 dk).

**US-PUB-08 (S) — SSS** · Akordeon yapı, `FAQPage` yapılandırılmış verisi, ana sayfada `show_on_home` işaretli olanlar.

**US-PUB-09 (M) — İletişim** · Form gönderilir, rate limit (5/saat/IP) ve honeypot ile korunur, ACP › İletişim'de listelenir. Gerçekçi yanıt süresi metni gösterilir.

**US-PUB-10 (M) — Yasal sayfalar** · `/privacy`, `/kvkk`, `/terms`, `/cookies`, ödeme aktifse `/refund-policy`. Sürümlüdür. Kayıt sırasında kabul edilen sürüm saklanır.

**US-PUB-11 (S) — Tema ve düşük efekt** · Dark varsayılan, light seçilebilir, tercih cookie'de (girişliyse hesapta) saklanır. Sayfa yüklenirken tema flaşı (FOUC) yaşanmaz. `prefers-reduced-motion` ve "Düşük efekt" anahtarı animasyonları kapatır.

### 6.2 Kimlik ve hesap (EPIC-AUTH)

**US-AUTH-01 (M) — Kayıt**
- KK1: Alanlar: kullanıcı adı, e-posta, parola, parola tekrarı, kullanım koşulları onayı, KVKK aydınlatma metnine bağlantı ve okundu beyanı.
- KK2: Kullanıcı adı 3–20 karakter, `[A-Za-z0-9_.]`, büyük/küçük harf duyarsız benzersiz. E-posta benzersiz.
- KK3: Parola en az 10 karakterdir ve sızdırılmış parola listesine karşı kontrol edilir (`Password::uncompromised()`, k-anonymity).
- KK4: Parola **Argon2id** ile hashlenir.
- KK5: Aynı e-posta zaten kayıtlıysa genel mesaj gösterilir ("Doğrulama bağlantısı gönderildi"). Hesap varlığı ifşa edilmez.
- KK6: Kayıt kapalıysa (ACP ayarı) form yerine bilgilendirme gösterilir.

**US-AUTH-02 (M) — E-posta doğrulama** · İmzalı ve 60 dk geçerli bağlantı gönderilir. Doğrulanmadan kritik işlemler (hesap bağlama, ticket, başvuru, ödeme) kapalıdır. Yeniden gönderme 1/dk ve 6/saat ile sınırlıdır.

**US-AUTH-03 (M) — Giriş** · E-posta veya kullanıcı adı ile giriş yapılır. "Beni hatırla" 30 gün geçerlidir. Hesap+IP bazlı rate limit uygulanır (5 başarısız/dk → bekleme). Başarısız ve başarılı girişler güvenlik olayına yazılır. Askıya alınmış hesap için nedeni ve bitiş tarihi gösterilir.

**US-AUTH-04 (M) — Şifre sıfırlama** · Her durumda aynı genel yanıt verilir. Tek kullanımlık token 60 dk geçerlidir. Sıfırlama sonrası diğer tüm oturumlar kapatılır ve güvenlik e-postası gönderilir.

**US-AUTH-05 (M) — Oturumlarım** · Aktif oturumlar listelenir (cihaz/tarayıcı özeti, maskeli IP, son etkinlik, "bu cihaz" etiketi). Tek tek ya da toplu kapatılabilir; işlem parola onayı ister.

**US-AUTH-06 (M) — Yetkili MFA** · Herhangi bir personel rolü olan kullanıcı TOTP kurmadan `/admin` alanına giremez. Kurtarma kodları bir kez gösterilir. Oyuncular MFA'yı isteğe bağlı açabilir.

**US-AUTH-07 (S) — E-posta değiştirme** · Parola onayı istenir. Yeni adrese doğrulama bağlantısı, eski adrese bilgilendirme gider. Değişiklik yeni adres doğrulanınca uygulanır.

### 6.3 Oyun hesabı ve karakterler (EPIC-ACC)

**US-ACC-01 (M) — Oyun hesabımı bağlamak**
Doğrulanmış oyuncu olarak MTA hesabımı web hesabıma güvenli şekilde bağlamak istiyorum.
- KK1: Panelde "Bağlama kodu oluştur" ile 8 karakterlik kod (`K7Q4-M2XD` biçiminde) üretilir. Kod 10 dk geçerlidir ve geri sayım gösterilir.
- KK2: Oyuncu oyunda `/webbagla K7Q4M2XD` yazar. MTA köprüsü kodu imzalı istekle doğrular ve bağlantı kurulur.
- KK3: Kod tek kullanımlıktır. Süresi dolan ya da kullanılan kod reddedilir. 5 hatalı denemede kod iptal edilir.
- KK4: Bir MTA hesabı aynı anda yalnızca bir web hesabına bağlı olabilir. Çakışma durumunda oyuna anlaşılır hata döner ve olay audit'e düşer.
- KK5: Bağlantı kurulduğunda panel sayfası sayfayı yenilemeden "Bağlandı" durumuna geçer (polling ≤ 5 sn) ve bildirim üretilir.

**US-ACC-02 (M) — Bağlı hesaplarım** · Hesap adı, bağlanma tarihi, durum ve karakter sayısı listelenir. "Bağlantıyı kaldır" doğrudan uygulanmaz; talep oluşturur ve yetkili onayına gider.

**US-ACC-03 (M) — Karakterlerim** · Ad, ID, oyun hesabı, durum, son görülme listelenir. Detayda yalnızca izinli `public_profile` alanları gösterilir. Düzenleme yoktur (salt okunur).

**US-ACC-04 (S, R2) — Cihaz/serial ilişkilendirme** · Web'den talep oluşturulur, oyun içi kodla doğrulanır, gerekirse yetkili onayına gider. Ham serial hiçbir arayüzde gösterilmez.

### 6.4 Oyuncu paneli (EPIC-UCP)

**US-UCP-01 (M) — Genel bakış** · Kartlar: bağlı hesap, karakter, açık ticket, bekleyen başvuru, son giriş, sunucu durumu (bakiye kartı yalnızca cüzdan modülü aktifse). Altında son aktiviteler, son duyurular ve hızlı işlemler yer alır. Her kartın boş durumu yönlendirici bir CTA içerir.

**US-UCP-02 (M) — Bildirimler** · Okundu/okunmadı, tümünü okundu yap, kategori filtresi. Üst bardaki zil sayaç gösterir.

**US-UCP-03 (M) — Akademi ilerlemem** · Dersler ve konular listelenir. "Okudum" işaretlenir, ilerleme yüzdesi gösterilir. Zorunlu dersler rozetle belirtilir.

### 6.5 Destek (EPIC-SUP)

**US-SUP-01 (M) — Ticket açmak** · Kategori, konu, açıklama, isteğe bağlı karakter ve en fazla 3 ek (jpg/png/webp/pdf/txt, ≤ 5 MB) ile ticket açılır. Gönderim sonrası ticket numarası gösterilir. Çift tıklama ikinci ticket oluşturmaz (idempotency).
**US-SUP-02 (M) — Takip** · Mesaj geçmişi kronolojik gösterilir. İç notlar oyuncuya **hiçbir koşulda** görünmez. Yanıt gelince bildirim ve e-posta (tercihe bağlı) gönderilir.
**US-SUP-03 (M) — Kapatma/yeniden açma** · Oyuncu çözülen ticket'ı kapatabilir. Kapanıştan sonra 7 gün içinde yeniden açma talebi gönderebilir.
**US-SUP-04 (M) — Yetkili kuyruğu** · Yetkili kendi branşlarının kuyruğunu görür. Durum, öncelik, kategori, atanan kişi ve "yanıt bekleyen" filtreleri vardır. Okunmamış sayaç gösterilir.
**US-SUP-05 (M) — Yetkili yanıtı** · Herkese açık yanıt ve iç not, görsel olarak açıkça ayrışan iki ayrı sekmeden yazılır. Makro (hazır yanıt) eklenebilir. Atama, öncelik değişikliği ve başka branşa iletme olay geçmişine yazılır.

### 6.6 Başvurular (EPIC-APP, R2)

**US-APP-01** · Oyuncu açık başvuru türlerini görür, uygunluk şartlarını (bağlı hesap, karakter, hesap yaşı, bekleme süresi) okur, taslak kaydeder ve gönderir.
**US-APP-02** · Oyuncu durum geçmişini ve yetkilinin herkese açık notunu görür. "Ek bilgi bekleniyor" durumunda yanıt yazabilir.
**US-APP-03** · Fraksiyon yöneticisi yalnızca kendi branşına bağlı başvuru türlerini görür. Onay, ret ve ek bilgi isteme kararları gerekçeyle verilir ve audit'e düşer.

### 6.7 Ceza ve moderasyon (EPIC-MOD, R2)

**US-MOD-01** · Oyuncu ceza geçmişini görür: tür, tarih, süre, genel açıklama, itiraz edilebilirlik. İç notlar görünmez.
**US-MOD-02** · Oyuncu itiraz edilebilir ceza için tek bir itiraz gönderir ve sonucunu takip eder.
**US-MOD-03** · Moderatör ceza oluşturur. Oyun içi etkisi olan cezalar (kick/ban) izinli komut kuyruğuna gider, sonucu geri bildirilir ve tümü audit'e yazılır.

### 6.8 Ekonomi ve ödeme (EPIC-WAL, R2)

**US-WAL-01** · Oyuncu her bakiye türünü ayrı kartta görür. Web kredisi ile oyun içi para asla aynı alanda gösterilmez.
**US-WAL-02** · Oyuncu işlem geçmişini filtreler. Kayıtlarda önceki/sonraki bakiye ve referans yer alır. Silme yoktur.
**US-WAL-03** · Oyuncu paket seçip sağlayıcının güvenli ödeme sayfasına gider. Bakiye **yalnızca** doğrulanmış webhook ile yüklenir. Aynı webhook ikinci kez gelirse ikinci yükleme olmaz.
**US-WAL-04** · Finans yöneticisi manuel düzeltme talebi açar. Eşik üstü tutarlarda başka bir finans yetkilisi onaylar (dört göz). Onaylanan düzeltme ters kayıtla geri alınabilir, asla silinmez.

### 6.9 Yönetim (EPIC-ADM)

**US-ADM-01 (M)** · Süper admin rolleri, izinleri ve branşları yönetir. Kimse kendinden yüksek ya da eşit öncelikli rolü atayamaz. Son süper admin rolü kaldırılamaz.
**US-ADM-02 (M)** · Admin kullanıcı arar, detayını görür, hesabı askıya alır ya da açar (gerekçe zorunlu, audit).
**US-ADM-03 (M)** · İçerik editörü haber, kural, sayfa bölümü vb. içerikleri taslak olarak hazırlar. Yayın yetkisi olan kişi yayınlar veya zamanlar.
**US-ADM-04 (M)** · Audit log filtrelenebilir (aktör, eylem, hedef, tarih, önem). Silinemez ve düzenlenemez. Hash zinciri bütünlüğü doğrulanabilir.
**US-ADM-05 (M)** · Sistem ayarları (site adı, sunucu adresi, bakım modu, kayıt açık/kapalı, modül anahtarları) değiştirilebilir. Secret değerler hiçbir zaman açık metin gösterilmez.
**US-ADM-06 (M)** · Duyuru hedef kitlesi, kanal (public banner / panel bildirimi / e-posta) ve tarih aralığıyla yayınlanır.
**US-ADM-07 (M)** · Admin dashboard'unda her sayının yanında veri kaynağı ve "son güncelleme" zamanı gösterilir.

### 6.10 MTA köprüsü (EPIC-BRG)

**US-BRG-01 (M)** · MTA resource her 30 sn'de imzalı heartbeat gönderir. İmzası hatalı, zaman damgası ±300 sn dışında veya nonce'u tekrar eden istek reddedilir ve loglanır.
**US-BRG-02 (M)** · Oyuncu giriş/çıkış ve karakter özet olayları idempotent işlenir (aynı `event_id` ikinci kez etki üretmez).
**US-BRG-03 (S, R2)** · Backend izinli komutları (allowlist) kuyruğa koyar. MTA bunları çekip uygular ve sonucunu bildirir. Genel amaçlı SQL veya shell komutu kesinlikle yoktur.

## 7. Başarı metrikleri

Her metriğin kaynağı ve hesaplama yöntemi tanımlıdır. Hedefler R1 yayınından 60 gün sonra değerlendirilir.

| Kod | Metrik | Tanım | Hedef | Kaynak |
|---|---|---|---|---|
| M1 | Ziyaretçi → kayıt dönüşümü | Benzersiz ziyaretçi içinden kayıt tamamlayan oranı | ≥ %8 | Çerez onaylı analitik + `users` |
| M2 | Doğrulama oranı | 24 saat içinde e-postasını doğrulayan kayıtların oranı | ≥ %75 | `users.email_verified_at` |
| M3 | Hesap bağlama oranı | Doğrulanmış kullanıcılardan 7 gün içinde oyun hesabı bağlayanlar | ≥ %60 | `game_account_links` |
| M4 | Katılım süresi | Kayıttan ilk `player.connected` olayına kadar geçen medyan süre | ≤ 30 dk | `game_events` |
| M5 | Ticket ilk yanıt süresi | Ticket açılıştan ilk public staff mesajına medyan süre | ≤ 6 saat | `support_messages` |
| M6 | Ticket çözüm süresi | Açılıştan `resolved` durumuna medyan süre | ≤ 48 saat | `support_ticket_events` |
| M7 | Durum doğruluğu | Heartbeat verisi taze olan sürenin oranı (sunucu açıkken) | ≥ %99 | `server_heartbeats` |
| M8 | Yetkisiz erişim | Yetki testlerinde (IDOR/BOLA) bulunan açık sayısı | 0 | Test raporu |
| M9 | Finans tutarlılığı | Ledger toplamı ile cüzdan bakiyesi arasındaki fark | 0 | Günlük mutabakat görevi |
| M10 | Performans | Public sayfalarda p75 LCP | ≤ 2,5 sn | Lighthouse / RUM |
| M11 | Akademi tamamlama | Yeni oyuncuların "Başlangıç" dersini 7 gün içinde bitirme oranı | ≥ %50 | `academy_progress` |

## 8. Sürüm kabul kriterleri (R1 — MVP)

R1 aşağıdakilerin **tümü** sağlandığında yayınlanabilir:

- [ ] Brief §16.5'teki yayın öncesi kontrol listesinin R1 kapsamındaki tüm maddeleri geçer.
- [ ] US-PUB-01..11, US-AUTH-01..07, US-ACC-01..03, US-UCP-01..03, US-SUP-01..05, US-ADM-01..07, US-BRG-01..02 hikayelerinin tüm KK'ları otomatik test veya belgelenmiş manuel testle doğrulanmıştır.
- [ ] Her modül [RULES.md §12 Definition of Done](RULES.md) şartlarını sağlar.
- [ ] Rol matrisi ([ADMIN.md §3](ADMIN.md)) için her izin anahtarının pozitif/negatif yetki testi vardır.
- [ ] Responsive kontroller 360 / 390 / 768 / 1024 / 1366 / 1920 px'te yapılmıştır.
- [ ] Public sayfalarda Lighthouse: Performance ≥ 90, Accessibility ≥ 95, Best Practices ≥ 95, SEO ≥ 95.
- [ ] Yedekten geri yükleme testi yapılmış ve tarihi [TODO.md](TODO.md) içine işlenmiştir.
- [ ] KVKK, gizlilik ve kullanım metinleri hukuk kontrolünden geçmiştir (proje sahibinin sorumluluğu).

## 9. Kapsam dışı (R1)

Ödeme ve cüzdan, başvurular, ceza/itiraz, serial değişikliği, oyun içi admin komutları, Discord bot, WhatsApp kanalı, çoklu sunucu, çoklu dil. Brief §20'deki uyarı gereği bu modüllere ilk sprintte başlanmaz.

## 10. Kararlar ve brief'ten sapmalar

Brief, teknoloji tablosunda React/Express/PostgreSQL/Prisma kalıntıları ile PHP/Laravel/MySQL hedeflerini birlikte içeriyor. Aşağıdaki kararlar bu çelişkileri çözer. Her karar, değiştirilene kadar bağlayıcıdır.

| Kod | Karar | Gerekçe | Brief referansı |
|---|---|---|---|
| D-01 | **Laravel 13 monolith** (backend + server-rendered frontend aynı uygulamada). "TypeScript harici doğru backend" = Laravel'in kendisi. | Tek geliştirici için en düşük karmaşıklık. Session/CSRF/policy altyapısı hazır. XAMPP ile doğrudan uyumlu. | §2.1 |
| D-02 | **Blade + Tailwind CSS v4 + Alpine.js (CSP build)**. shadcn/ui yerine kendi Blade bileşen kütüphanemiz (shadcn anatomisinden ilham), Lucide ikonları Blade-icon olarak, Framer Motion yerine CSS geçişleri + View Transitions API. | React ekosistemi kütüphaneleri Blade'de kullanılamaz. Server-rendered yaklaşım SEO ve performans için daha iyidir. | §2.1 |
| D-03 | **MariaDB 10.11** (XAMPP) geliştirmede, **MySQL 8.4 LTS veya MariaDB 10.11+** production'da. Laravel `mariadb` sürücüsü kullanılır. Yalnızca iki motorda da çalışan SQL özellikleri kullanılır. | Brief "MySQL" diyor. XAMPP MariaDB ile geliyor. PostgreSQL ifadeleri brief kalıntısıdır. | §2.1, §8.8, §12.5 |
| D-04 | **Eloquent ORM** (PDO prepared statements üzerine). Prisma/Drizzle yok. | Laravel'in yerel ORM'i. Prisma Node'a özgüdür. | §2.1 |
| D-05 | **PHP 8.4** (mevcut XAMPP) hedeflenir, kod **PHP 8.5 uyumlu** yazılır. CI'da iki sürüm de test edilir. | XAMPP PHP 8.4.27 kurulu. Laravel 13 ≥ 8.3 ister. | §2.1 |
| D-06 | Auth: **server-side session** (database driver) + **Laravel Fortify** (headless). JWT kullanılmaz. Parola: **Argon2id**. MFA: TOTP. | Aynı origin'li monolith için session + CSRF en güvenli ve en basit yoldur. | §2.1, §5 |
| D-07 | **Hesap bağlama web'de başlatılır**: kod web panelinde üretilir, oyunda `/webbagla KOD` ile doğrulanır. | Brief §5.1 kodu oyunda üretip web'e girmeyi, §8.4/§11.4 ise web'de başlatmayı tarif ediyor. Web'de başlatma, serial akışı (§6.13) ile aynı deseni kullanır ve kod hep kimliği doğrulanmış web oturumuna bağlı kalır. | §5.1, §8.4, §11.4 |
| D-08 | Gerçek zaman: R1'de **akıllı polling** (ETag, görünmez sekmede durur), R2'de **Laravel Reverb** (WebSocket). Socket.IO yok. | XAMPP/Apache altında SSE her bağlantı için bir PHP worker'ı kilitler. Reverb Laravel'in yerel çözümüdür. | §2.1, §15.3 |
| D-09 | PK: `BIGINT UNSIGNED AUTO_INCREMENT`. URL'de görünen varlıklara ek olarak `ulid CHAR(26)` route key. | FK performansı + enumerasyon (sayı tahmini) koruması. | §10.7 |
| D-10 | Laravel'in kuyruk tablosu `jobs` olduğu için meslekler tablosu **`game_jobs`** adını alır. | İsim çakışması. | §10.5 |
| D-11 | Para: `BIGINT` minor unit (kuruş) + `currency CHAR(3)`. Kredi/puan: `BIGINT` tam sayı. Float hiçbir yerde kullanılmaz. | Brief §10.7. | §10.7 |
| D-12 | **Talep yorumları:** Doküman talebindeki "müfredat ders/konu", "WhatsApp şablon motoru", "branş atama" ve "PWA" maddeleri bu projeye şöyle uyarlandı: **Akademi** = yeni oyuncu eğitim müfredatı (Ders → Konu); **Mesaj şablon motoru** = e-posta/panel/Discord/WhatsApp kanallı, değişkenli şablonlar (WhatsApp R3, opt-in); **Branş** = yetkili birimi (Destek, Moderasyon, her fraksiyon için ayrı birim vb.) ve kapsamlı yetki; **PWA** = kurulabilir oyuncu paneli. | Maddeler roleplay bağlamında anlamlı ve brief'teki ihtiyaçlara (kural onayı, bildirim, fraksiyon yöneticisi kapsamı, mobil kullanım) karşılık geliyor. Proje sahibi farklı bir şey kastettiyse ilgili doküman güncellenir. | — |
| D-13 | Çalışma markası **Meridyen Roleplay** (kısaltma MRP). Tüm marka metinleri `system_settings` ve `config/roleplay.php` üzerinden okunur, kodda sabit değildir. | Brief §20 marka belirlemeyi ilk iş olarak istiyor. Değiştirmek bir ayar değişikliğidir. | §0.1, §20 |
| D-14 | Dosyalar geliştirmede yerel diskte (`storage/app/private` ve `public`), production'da isteğe bağlı S3 uyumlu disk kullanılır. Görseller GD ile WebP'ye dönüştürülür ve EXIF temizlenir. | Laravel filesystem soyutlaması sayesinde disk değişimi yapılandırma işidir. | §2.1 |
| D-15 | Kayıt ve e-posta doğrulama Fortify yerine kendi controller'larımızda. Kayıttan sonra otomatik giriş yok; her durumda aynı "E-postanı kontrol et" ekranı gösterilir. Doğrulama bağlantısı imzalıdır ve oturum gerektirmez. | Fortify'ın otomatik girişi ve benzersiz e-posta hatası hesap varlığını ifşa eder (SEC-005, US-AUTH-01 KK5). | §5.2 |

## 11. Açık kararlar (proje sahibi onayı gerekli)

| Kod | Soru | Varsayılan (karar verilene kadar) | Bloke ettiği faz |
|---|---|---|---|
| OD-01 | Nihai marka adı, logo, alan adı | "Meridyen Roleplay", `meridyen-rp.test` (geliştirme) | Faz 11 (yayın) |
| OD-02 | MTA tarafındaki hesap sistemi: MTA dahili `Account` mı, özel gamemode hesap tablosu mu? | Bridge, `mta_account_ref` ile her ikisini de soyutlar | Faz 7 |
| OD-03 | Cüzdan türleri: web kredisi mi, premium puan mı, ikisi mi? Oyun içi paraya dönüşüm var mı? | Yalnızca `web_credit`. Oyun içi para salt okunur gösterilir | Faz 9 |
| OD-04 | Ödeme sağlayıcısı (iyzico, PayTR, Stripe, Papara…) | Adapter + `FakeProvider` (test) | Faz 9 |
| OD-05 | WhatsApp kanalı gerçekten gerekli mi? (Telefon numarası toplamak ek KVKK yükü getirir) | R3, yalnızca açık rıza ile | Faz 10 |
| OD-06 | Production barındırma (VPS + Nginx + PHP-FPM önerilir) | Linux VPS | Faz 11 |
| OD-07 | Serial verisi tutulacak mı? Tutulacaksa HMAC mı, şifreli ham değer mi? | Yalnızca HMAC referansı | Faz 8 |
| OD-08 | Yasal metinlerin hukuk kontrolü | Taslak metinler "TASLAK" filigranıyla yayınlanmaz | Faz 11 |

## 12. Riskler

| Risk | Olasılık | Etki | Önlem |
|---|---|---|---|
| MTA tarafında HMAC-SHA256 desteğinin sürüme bağlı olması | Orta | Yüksek | Faz 7'nin ilk görevi bir doğrulama spike'ı. Destek yoksa `hash("sha256")` üzerine saf Lua HMAC yazılır ([ARCHITECTURE §7.3](ARCHITECTURE.md)) |
| Tek geliştirici kapasitesi | Yüksek | Orta | Faz bazlı küçük teslimatlar, docs-first, otomatik testler |
| Ödeme sağlayıcısı gecikmesi | Orta | Düşük | Adapter + FakeProvider. Ödeme R2'de |
| XAMPP (Windows) ile production (Linux) farkları | Orta | Orta | Dosya yolu ve büyük/küçük harf testleri, CI'da Linux |
| Kişisel veri ihlali | Düşük | Çok yüksek | Veri minimizasyonu, maskeleme, şifreli kolonlar, saklama süreleri |
| Telif riski (referans siteye benzerlik) | Düşük | Yüksek | Özgün tasarım sistemi ([DESIGN.md](DESIGN.md)), özgün metin ve görseller |
