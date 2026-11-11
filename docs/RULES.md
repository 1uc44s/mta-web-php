# RULES — Kod Yazım Standartları ve Güvenlik Kuralları

> **Sürüm:** 1.0.0 · **Son güncelleme:** 2026-11-11 · **Durum:** Onay bekliyor
> **İlgili:** [ARCHITECTURE](ARCHITECTURE.md) · [SRS §5](SRS.md#5-güvenlik-gereksinimleri-sec) · [ROADMAP](ROADMAP.md)

Bu kurallar **bağlayıcıdır**. CI'da otomatik denetlenebilenler (Pint, Larastan, Pest arch testleri) build'i durdurur. Diğerleri code review kontrol listesindedir. "MUST" = zorunlu, "SHOULD" = güçlü tavsiye (istisna gerekçesi yorumda yazılır).

---

## 1. Docs-first (tek doğruluk kaynağı)

1. **MUST:** Yeni özellik, tablo, kolon, rota, izin veya ayar önce `/docs` içinde tanımlanır, sonra kodlanır.
2. **MUST:** Kod ile doküman çelişirse çelişki aynı PR'da giderilir. "Sonra düzeltirim" yoktur.
3. **MUST:** Commit mesajı ilgili doküman kimliğine referans verir (`feat(support): ticket idempotency (FR-SUP-003)`).
4. **MUST:** Migration'daki her kolon [DATABASE.md](DATABASE.md) belgesinde, her rota [ROUTES.md](ROUTES.md) belgesinde, her izin [ADMIN.md §3](ADMIN.md#3-i̇zin-kataloğu-ve-rol-matrisi) belgesinde bulunur. `RouteCatalogTest` ve `PermissionCatalogTest` bunu doğrular.
5. **SHOULD:** Önemli tasarım kararları PRD §10 tablosuna yeni `D-xx` satırı olarak eklenir.

## 2. Genel ilkeler (clean code)

- **Okunabilirlik önce gelir.** Kod bir kez yazılır, çok kez okunur. İsimler niyeti anlatır: `$expiredLinkCodes`, `$c` değil.
- **Tek sorumluluk.** Bir sınıf veya metot tek bir şey yapar. Metot ≤ 30 satır (SHOULD), sınıf ≤ 300 satır (SHOULD).
- **Erken dönüş.** İç içe `if` yerine guard clause kullanılır. En fazla 3 seviye girinti.
- **Sihirli değer yok.** Sabitler enum, config veya sınıf sabiti olarak tanımlanır (`LinkCode::TTL_MINUTES` değil, `config('roleplay.link_code.ttl_minutes')`).
- **Yan etki açık olmalı.** Veri değiştiren metotların adı fiildir (`suspend`, `post`, `revoke`). Sorgular yan etkisizdir.
- **Sessiz hata yok.** Boş `catch` yasaktır. Yakalanan istisna ya ele alınır ya loglanıp yeniden fırlatılır (brief §18.1).
- **Ölü kod yok.** Yorum satırına alınmış kod commit edilmez. Gerekirse git geçmişi kullanılır.
- **Yorum "neden"i anlatır,** "ne"yi değil. Güvenlik kararlarında yoruma doküman referansı eklenir (`// SEC-042: düşük entropili kod, HMAC`).
- **YAGNI.** İstenmeyen genelleme yapılmaz. Fakat dokümanda "R2'de genişleyecek" denen modeller (çoklu sunucu) baştan doğru kurulur.
- **Mock veri production verisi gibi sunulmaz** (brief §18.1). Seed metinleri `[TASLAK]` önekiyle işaretlenir.

## 3. PHP standartları

| Kural | Seviye |
|---|---|
| Her dosya `declare(strict_types=1);` ile başlar | MUST |
| PSR-12 + Laravel Pint preset (`pint.json`), CI'da `pint --test` | MUST |
| Tüm parametre, dönüş ve property'lerde tip bildirimi. `mixed` yalnızca gerekçeyle | MUST |
| Larastan seviye 6 (Faz 4 sonunda 7, R1 sonunda 8). Baseline büyümez, yalnızca küçülür | MUST |
| Sınıflar varsayılan olarak `final`. Kalıtım gerekiyorsa açıkça kaldırılır | SHOULD |
| Değer nesneleri ve DTO'lar `readonly class` | SHOULD |
| Enum benzeri değerler PHP backed enum (`enum TicketStatus: string`) | MUST |
| Tarih/saat için `CarbonImmutable` (`Date::use(CarbonImmutable::class)`) | MUST |
| Para için `int` minor unit + `Money` value object. Float ile para hesabı yasak | MUST |
| `env()` yalnızca `config/*.php` içinde kullanılır | MUST |
| `dd()`, `dump()`, `var_dump()`, `ray()` commit edilmez (Pest arch testi) | MUST |
| İsimlendirme: sınıf `PascalCase`, metot/değişken `camelCase`, config/DB anahtarı `snake_case`, rota adı `kebab.case` | MUST |

## 4. Laravel kuralları

### 4.1 Katmanlar (bkz. [ARCHITECTURE §3.1](ARCHITECTURE.md#31-katman-kuralları-pest-arch-testleriyle-zorlanır))

```php
// ✅ İnce controller
final class SupportTicketController extends Controller
{
    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $this->authorize('create', SupportTicket::class);

        $ticket = $createTicket->handle($request->user(), $request->toData());

        return to_route('panel.support.show', $ticket)
            ->with('toast', __('support.ticket_created', ['number' => $ticket->number]))
            ->setStatusCode(303);
    }
}
```

```php
// ❌ Controller'da iş mantığı, doğrulanmamış girdi, mass assignment
public function store(Request $request)
{
    $ticket = SupportTicket::create($request->all());   // SEC-014, SEC-020 ihlali
    // ...
}
```

### 4.2 Kurallar listesi

| Kural | Seviye |
|---|---|
| Her durum değiştiren uç bir **FormRequest** kullanır. `$request->validated()` / `->safe()` dışında girdi okunmaz | MUST |
| Her controller aksiyonu `authorize()` çağırır **veya** rota `can:` middleware'i taşır | MUST |
| İş mantığı `app/Actions/*` içindedir. Action'lar `handle()` metoduna sahiptir ve HTTP'den bağımsızdır | MUST |
| Birden çok tabloya yazan işlemler `DB::transaction()` içindedir. Para işlemlerinde `lockForUpdate()` kullanılır | MUST |
| Modellerde `$fillable` açık listedir. `$guarded = []` yasaktır | MUST |
| Hassas alanlar `$hidden` içindedir (`password, remember_token, two_factor_*`). Panel JSON'u Resource sınıflarıyla üretilir | MUST |
| `Model::preventLazyLoading(! app()->isProduction())`, `preventSilentlyDiscardingAttributes()`, `preventAccessingMissingAttributes()` açıktır | MUST |
| Route model binding dışa açık varlıklarda `ulid` kullanır | MUST |
| Cache'e yalnızca skaler ve düz dizi yazılır. Eloquent modeli/koleksiyonu veya başka nesne cache'lenmez: Laravel 13 cache'i güvenlik gereği nesne `unserialize` etmez (Faz 2 bulgusu) | MUST |
| Kuyruk işleri idempotenttir ve `ShouldBeUnique` veya idempotency anahtarı kullanır | MUST |
| Uzun süren işler (e-posta, görsel işleme, webhook işleme) kuyruğa atılır | MUST |
| Olaylar (`Events`) yan etkileri ayırmak için kullanılır. Kritik iş kuralı listener'a gizlenmez | SHOULD |
| Arayüz metinleri `__('dosya.anahtar')` ile yazılır. Blade içinde Türkçe sabit metin yazılmaz | MUST |
| Config cache uyumluluğu: `config()` çağrıları runtime'da yapılır, `env()` yalnızca config dosyalarında kullanılır | MUST |

## 5. Veritabanı ve SQL injection koruması (PDO)

### 5.1 Bağlantı
Laravel PDO bağlantısı şu seçeneklerle kurulur (`config/database.php`):

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    PDO::ATTR_EMULATE_PREPARES  => false,   // gerçek prepared statement (sunucu tarafı)
    PDO::ATTR_STRINGIFY_FETCHES => false,
    PDO::MYSQL_ATTR_SSL_CA      => env('MYSQL_ATTR_SSL_CA'),
]) : [],
```

### 5.2 Sorgu kuralları

| Kural | Seviye |
|---|---|
| Sorgular Eloquent veya Query Builder ile yazılır. Bunlar PDO prepared statement + parametre binding kullanır | MUST |
| `DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw`, `havingRaw`, `DB::statement`, `DB::select` yalnızca **binding dizisiyle** kullanılır. Değişken asla string birleştirme veya interpolasyon ile SQL'e girmez | MUST |
| Kolon ve tablo adları, `ORDER BY` yönü, `LIMIT` gibi bind edilemeyen parçalar **allowlist**'ten gelir | MUST |
| `LIKE` aramalarında `%`, `_` ve `\` escape edilir (projeye ait `App\Support\Sql::escapeLike()` yardımcısı) | MUST |
| `whereIn` boş diziyle çağrılmaz (Laravel güvenli davranır, ancak niyet açık olmalıdır) | SHOULD |
| Uygulama DB kullanıcısı DDL ve TRIGGER yetkisine sahip değildir (production) | MUST |
| Migration'lar geri alınabilir (`down()`). Production'da veri kaybettiren migration iki aşamalı yapılır (önce kolon ekle/kopyala, sonraki sürümde kaldır) | MUST |

```php
// ❌ YASAK — SQL injection
DB::select("SELECT * FROM users WHERE username = '$username'");
User::whereRaw("email = '{$request->email}'")->first();
$query->orderBy($request->input('sort'));                       // kolon adı enjeksiyonu

// ✅ DOĞRU — binding ve allowlist
DB::select('SELECT id FROM users WHERE username = ?', [$username]);
User::whereRaw('LOWER(email) = ?', [Str::lower($email)])->first();

$sortable = ['created_at', 'last_message_at', 'priority'];      // allowlist
$column   = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at';
$dir      = $request->query('dir') === 'asc' ? 'asc' : 'desc';
$query->orderBy($column, $dir);

$term = Sql::escapeLike($request->string('q')->trim()->limit(100)->toString());
$query->where('subject', 'like', "%{$term}%");                   // değer yine binding ile gider
```

`App\Support\Sorting::apply($query, $request, allowed: [...], default: ...)` yardımcı fonksiyonu tüm liste ekranlarında kullanılır.

## 6. XSS ve çıktı güvenliği

| Kural | Seviye |
|---|---|
| Blade çıktısı her zaman `{{ $var }}` ile yapılır (otomatik `htmlspecialchars`, ENT_QUOTES, UTF-8) | MUST |
| `{!! !!}` yalnızca `App\Support\SafeHtml` tipindeki değerler için kullanılır. Bu tip yalnızca `MarkdownRenderer` ve `HtmlSanitizer` tarafından üretilir. Pest arch testi `{!!` kullanımlarını tarar ve allowlist dışındakileri reddeder | MUST |
| Kullanıcı verisi HTML attribute'una `{{ }}` ile, URL'e `url()`/`route()` ile, JS'e yalnızca `@js($data)` veya `data-*` attribute'u + `JSON.parse` ile aktarılır | MUST |
| `href`/`src` içine kullanıcı URL'i konmadan önce şema allowlist'i (`https`, `mailto`) uygulanır. `javascript:` ve `data:` reddedilir | MUST |
| Alpine ifadelerinde kullanıcı verisi **ifade olarak** değerlendirilmez. Veri `x-data` bileşenine `data-*` attribute'undan okunur (Alpine CSP build bunu zorunlu kılar) | MUST |
| `innerHTML`, `insertAdjacentHTML`, `document.write`, `eval`, `new Function` kullanılmaz. DOM'a metin `textContent` ile yazılır | MUST |
| JSON yanıtları `application/json` döner. JSONP yoktur | MUST |
| E-posta şablonlarında değişkenler kanal kurallarına göre escape edilir ([CMS §6.3](CMS.md#63-kanala-göre-çıktı-ve-escape)) | MUST |
| Admin tarafından düzenlenen hiçbir metin Blade/PHP olarak derlenmez | MUST |
| CSP nonce'u olmayan satır içi `<script>` veya `<style>` yazılmaz. `onclick=""` gibi satır içi olay işleyicileri yasaktır | MUST |

```blade
{{-- ❌ YASAK --}}
<div>{!! $ticket->body !!}</div>
<a href="{{ $user->website }}">…</a>                     {{-- javascript: şeması riski --}}
<div x-data="{ name: '{{ $user->username }}' }">          {{-- ifade enjeksiyonu --}}

{{-- ✅ DOĞRU --}}
<div class="prose">{!! $ticket->renderedBody() !!}</div>  {{-- SafeHtml döner --}}
<a href="{{ \App\Support\Url::safe($user->website) }}" rel="noopener nofollow ugc">…</a>
<div x-data="userCard" data-username="{{ $user->username }}">
```

## 7. CSRF ve istek bütünlüğü

| Kural | Seviye |
|---|---|
| Her `<form method="POST">` içinde `@csrf` bulunur. `x-ui.form` bileşeni bunu otomatik ekler | MUST |
| GET istekleri durum değiştirmez ([ROUTES §1](ROUTES.md#1-kurallar)) | MUST |
| Alpine/fetch POST istekleri `X-CSRF-TOKEN` başlığını `<meta name="csrf-token">` değerinden alır (`resources/js/lib/http.js` tek yardımcı fonksiyonu kullanılır) | MUST |
| Session cookie `SameSite=Lax`. Ek katman olarak projeye ait `VerifyOrigin` middleware'i, durum değiştiren isteklerde `Sec-Fetch-Site` / `Origin` başlığını kontrol eder (cross-site ise 419) | MUST |
| CSRF istisnası yalnızca `webhooks/*` içindir ve bu rotalar imza doğrulaması taşır. Yeni istisna eklemek PRD'de karar kaydı gerektirir | MUST |
| `419` durumunda kullanıcı verisi korunur ve "Sayfayı yenile" yönlendirmesi yapılır | MUST |
| Yıkıcı veya finansal işlemler ek olarak `password.confirm` ve/veya onay modalı ister | MUST |
| Formlar `idempotency_key` gizli alanı taşır (oluşturma işlemleri) | MUST |
| Açık yönlendirme yoktur: `redirect()->to($request->input('next'))` yerine `Url::safeRedirect()` kullanılır | MUST |

## 8. Diğer güvenlik kuralları

| Alan | Kural |
|---|---|
| Kimlik doğrulama | Parola, token ve kod karşılaştırmaları `hash_equals` / `Hash::check` ile yapılır. Rastgelelik için `random_bytes`, `Str::random` veya `random_int` kullanılır. `rand`, `mt_rand` ve `uniqid` güvenlik amacıyla kullanılmaz |
| Hata mesajları | Hesap varlığını ifşa etmez. Exception mesajı kullanıcıya doğrudan gösterilmez |
| Dosya yükleme | Uzantı **ve** finfo MIME **ve** boyut kontrolü yapılır. Dosya adı ULID ile üretilir, orijinal ad yalnızca gösterim içindir. Özel dosyalar `private` diske yazılır. Görseller yeniden kodlanır. SVG yasaktır |
| Secret'lar | Koda, README'ye, örnek payload'a, test fixture'ına veya log'a yazılmaz. Test secret'ları `phpunit.xml` / `.env.testing` içindeki sahte değerlerdir |
| Loglama | `RedactSensitiveData` processor'ı kullanılır. `Log::info('…', $request->all())` yasaktır |
| Rate limit | Her public POST ucu bir `throttle` limiter'ı taşır ([SRS §5.4](SRS.md#54-rate-limit-tablosu)) |
| Bağımlılık | Yeni paket eklemeden önce son güncelleme tarihi, indirme sayısı ve bilinen açıkları kontrol edilir. `composer audit` / `npm audit` temiz olmalıdır |
| Köprü | MTA'dan gelen her alan kendi şemasıyla doğrulanır. Allowlist dışı komut kesinlikle yoktur. Ham serial loglanmaz |
| Yetki | Frontend'de buton gizlemek yetki kontrolü değildir (brief §12.2). Her uçta backend kontrolü yapılır |

## 9. Frontend kuralları

| Kural | Seviye |
|---|---|
| Stil yalnızca Tailwind utility'leri + DESIGN token'larıyla yazılır. Ham hex renk ve keyfi değer (`text-[#123456]`) yasaktır | MUST |
| Tekrar eden UI parçası Blade bileşenidir (`x-ui.*`). Aynı class dizisi 3 kez tekrarlanırsa bileşene çıkarılır | SHOULD |
| Alpine **CSP build** kullanılır. Bileşenler `Alpine.data('name', () => ({…}))` ile `resources/js/components/` altında tanımlanır | MUST |
| JS olmadan temel okuma ve formlar çalışır (progressive enhancement). Alpine yalnızca zenginleştirme yapar | MUST |
| Her etkileşimli öğe klavyeyle kullanılabilir, odak görünürdür. `div`/`span` tıklanabilir yapılmaz (`button` veya `a` kullanılır) | MUST |
| Görsellerde `alt`, `width`, `height` bulunur. Hero dışındaki görseller `loading="lazy"` kullanır | MUST |
| Animasyonlar `motion-safe:` varyantı veya `data-effects` kontrolüyle yazılır | MUST |
| Her sayfa loading (skeleton), empty, error ve success durumlarını tasarlar (brief §19) | MUST |
| JS bağımlılığı eklemek gerekçe ister. Public bundle bütçesi ≤ 60 KB gzip (NFR-PERF-003) | MUST |

## 10. Git ve commit

- Dal modeli: `main` (her zaman yayınlanabilir) ← `feat/…`, `fix/…`, `docs/…`, `chore/…` kısa ömürlü dallar.
- **Conventional Commits:** `feat(scope): …`, `fix(scope): …`, `docs: …`, `refactor`, `test`, `chore`, `perf`, `security`. Scope modül adıdır (`auth, support, bridge, cms, admin, wallet, design`).
- Commit mesajı Türkçe veya İngilizce olabilir, ancak bir dalda tutarlı olmalıdır. Gövdede "neden" anlatılır ve ilgili FR/SEC/D kimliği yazılır.
- Her commit derlenebilir ve testleri geçer durumdadır.
- `.env`, `storage/*`, `vendor/`, `node_modules/`, `public/build/`, `mta-resource/web_bridge/config.lua` repoya girmez.
- Satır sonu LF (`.gitattributes: * text=auto eol=lf`). Windows geliştirme ortamı nedeniyle zorunludur.

## 11. Test kuralları

| Kural | Seviye |
|---|---|
| Test çatısı Pest. Test adı gereksinim kimliğiyle başlar: `it('FR-SUP-006: hides internal notes from players')` | MUST |
| Her yeni uç için: başarılı yol, doğrulama hatası, yetkisiz (403/404), kimliksiz (redirect/401) testleri yazılır | MUST |
| Her izin için pozitif + negatif test bulunur (`RoleMatrixTest` veri sağlayıcısı `config/permissions.php` dosyasından okur) | MUST |
| Para, imza, kod ve durum makinesi mantığı için birim testi yazılır (kapsam ≥ %90) | MUST |
| Testler `mariadb` test veritabanında çalışır (SQLite değil: generated column, trigger ve CHECK davranışı aynı olmalıdır). `RefreshDatabase` kullanılır | MUST |
| Zaman bağımlı testler `Date::setTestNow()` / `travelTo()` kullanır | MUST |
| Harici servisler `Http::fake()`, `Mail::fake()`, `Queue::fake()` ile izole edilir. Gerçek ağ çağrısı yapılmaz | MUST |
| Bug düzeltmesi önce onu yeniden üreten başarısız bir testle başlar | MUST |
| Komutlar: `composer test` (Pest, paralel), `composer lint` (Pint), `composer analyse` (Larastan), `npm run build` | — |

## 12. Definition of Done

Bir görev veya modül ancak aşağıdakilerin **tümü** sağlandığında "tamamlandı" sayılır (brief §19'un genişletilmiş hâli):

1. [ ] İlgili doküman(lar) güncel ve kodla birebir uyumlu.
2. [ ] UI responsive (360–1920 px), dark ve light temada kontrol edilmiş.
3. [ ] Loading, empty, success ve error durumları mevcut.
4. [ ] FormRequest doğrulaması çalışıyor, hata mesajları anlaşılır ve Türkçe.
5. [ ] Backend'de auth + izin + sahiplik/branş kontrolü var.
6. [ ] Migration (geri alınabilir) ve gerekiyorsa seeder eklenmiş.
7. [ ] Testler yazılmış ve yeşil: başarılı yol + hata + yetki.
8. [ ] Kritik olaylar audit log'a yazılıyor.
9. [ ] Erişilebilirlik kontrol listesi ([DESIGN §8](DESIGN.md#8-erişilebilirlik-kontrol-listesi-bileşen-tamamlanma-kriteri)) geçiyor.
10. [ ] `composer lint`, `composer analyse`, `composer test`, `npm run build` başarılı.
11. [ ] Bilinen eksikler TODO'ya yazılmış. Çalışmayan hiçbir şey "tamamlandı" olarak işaretlenmemiş.

## 13. AI destekli geliştirme kuralları

Brief §18 aynen geçerlidir. Ek olarak:

1. AI her görevde önce **ilgili dokümanı ve mevcut dosyaları okur**. Görmediği dosya hakkında varsayımla değişiklik yapmaz.
2. Görev brief §18.2 formatında tanımlanır (AMAÇ / MEVCUT DURUM / GEREKSİNİMLER / DOKUNULACAK ALANLAR / KABUL KRİTERLERİ / GÜVENLİK / TEST / TESLİM RAPORU).
3. Tek görevde tek modül değişir. Çok modüllü değişiklik planlanıp parçalara bölünür.
4. Teslim raporu şunları içerir: değişen dosyalar, migration'lar, çalıştırılan komutlar ve **gerçek çıktıları**, bilinen eksikler, güncellenen doküman bölümleri.
5. Test çalıştırılamadıysa (ortam sorunu vb.) bu açıkça yazılır. "Çalışması gerekir" ifadesi doğrulama yerine geçmez.
