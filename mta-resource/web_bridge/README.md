# web_bridge — Meridyen MTA köprüsü

MTA:SA sunucusu ile web paneli arasındaki **imzalı** HTTP köprüsü. Protokol: [docs/ARCHITECTURE.md §7](../../docs/ARCHITECTURE.md), uç sözleşmeleri: [docs/ROUTES.md §7.2](../../docs/ROUTES.md).

## Kurulum

1. Klasörü sunucunun `mods/deathmatch/resources/` dizinine kopyala.
2. `config.example.lua` → `config.lua` olarak kopyala (`config.lua` git'e girmez).
3. ACP → Sistem → **MTA entegrasyonu** → sunucunu seç → **Yeni anahtar oluştur**. `key_id` ve `secret`'ı `config.lua`'ya yaz. Secret yalnızca bir kez gösterilir.
4. `BASE_URL`'i sitenin adresi yap (üretimde `https://` zorunlu).
5. ACL'de resource'a izin ver (`acl.xml`):
   ```xml
   <group name="WebBridge">
       <acl name="WebBridge" />
       <object name="resource.web_bridge" />
   </group>
   <acl name="WebBridge">
       <right name="function.fetchRemote" access="true" />
       <right name="function.kickPlayer" access="true" />
   </acl>
   ```
6. `start web_bridge`. Başlangıçta HMAC öz-testi (`tests/vectors.lua`) çalışır; başarısızsa köprü başlamaz ve sunucu loguna sebebini yazar. Ardından `/ping` ile saat farkı loglanır (±300 sn penceresi aşılırsa tüm istekler reddedilir — sunucu saatini NTP ile eşitle).

## Ne gönderir

| Olay | Uç | Ne zaman |
|---|---|---|
| Heartbeat | `POST /api/v1/game/heartbeat` | 30 sn'de bir |
| Giriş / çıkış | `POST /player/connected`, `/player/disconnected` | `onPlayerLogin`, `onPlayerQuit` |
| Hesap bağlama | `POST /account-link/verify` | Oyuncu `/webbagla KOD` yazınca (10 sn bekleme) |
| Karakterler | `POST /characters/sync` | Gamemode `exports.web_bridge:syncCharacters(player, list)` çağırınca |
| Komutlar (Faz 8) | `GET /commands/pending`, `POST /commands/result` | `ENABLE_COMMANDS = true` iken 10 sn'de bir |

Olaylar bellek içi kuyruktadır (en fazla 500). Ağ/5xx hatasında 1 / 5 / 15 sn geri çekilmeyle en fazla 5 kez denenir; `event_id` sabit kaldığından tekrar gönderim web'de yan etki üretmez.

## Gamemode entegrasyonu

```lua
-- Karakter seçimi/değişimi sonrasında:
exports.web_bridge:syncCharacters(player, {
    { ref = "c-551", name = "Kaan Demir", status = "active", level = 12, playtime_minutes = 5400,
      faction_key = "lspd", job_key = "taxi", public_profile = { bio = "..." } },
})

-- Hesap bağlanınca güncel karakter listesini iste:
addEventHandler("onWebBridgeRequestCharacters", root, function() --[[ syncCharacters(source, ...) ]] end)

-- Web bağlantısı kaldırıldığında (account.link_revoked):
addEventHandler("onWebBridgeLinkRevoked", root, function(accountRef) end)
```

`faction_key` / `job_key` web'deki fraksiyon/meslek `slug` değerleriyle eşleşir. `public_profile` içinde yalnızca `bio, job_label, faction_label, level, playtime_hours` web'de saklanır; diğer alanlar düşürülür.

Hesap referansı varsayılan olarak MTA hesap ID'sidir (`config.lua` → `getAccountRef`). Kendi hesap sistemin varsa bu fonksiyonu değiştir; referans **kalıcı** olmalıdır (isim değişse de aynı kalmalı).

## Güvenlik

- Secret yalnızca `config.lua`'dadır; loglarda maskelenir. İstemciye hiçbir dosya gönderilmez (tüm script'ler `type="server"`).
- Her istek `X-Bridge-Key / Timestamp / Nonce / Signature` taşır. İmza: `hex(HMAC-SHA256(secret, METHOD\nPATH\nTIMESTAMP\nNONCE\nhex(SHA256(BODY))))`.
- HMAC, MTA `hash("sha256")` üzerine saf Lua ile uygulanır (RFC 2104). Test vektörleri (`tests/vectors.lua` / `tests/vectors.json`) Pest'te de doğrulanır (`tests/Feature/Bridge/BridgeSecurityTest.php`).
- Komut yürütücü yalnızca `shared/constants.lua` allowlist'indeki türleri çalıştırır; `executeSQLQuery` veya shell çağrısı yoktur. Tek `loadstring` kullanımı, resource içindeki sabit `tests/vectors.lua` dosyasının boş ortamda yüklenmesidir.
- Anahtar sızarsa: ACP'den **iptal et**, yenisini oluştur (sunucu başına aynı anda en fazla 2 aktif anahtar — kesintisiz rotasyon).
