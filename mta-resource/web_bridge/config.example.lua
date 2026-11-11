-- web_bridge yapılandırması. Bu dosyayı config.lua olarak kopyala ve değerleri doldur.
-- config.lua .gitignore'dadır; SECRET asla repoya, loga veya sohbete yazılmaz.
-- KEY_ID ve SECRET: ACP → Sistem → MTA entegrasyonu → "Yeni anahtar oluştur" (secret yalnızca bir kez gösterilir).
CONFIG = {
    BASE_URL = "https://meridyenrp.com",      -- Sonunda / olmadan. Yerel geliştirme: http://127.0.0.1:8000
    KEY_ID = "mrp_live_xxxxxxxxxxxxxxxx",
    SECRET = "64-hex-karakterlik-secret",

    HEARTBEAT_SECONDS = 30,                   -- ARCHITECTURE §7.8
    COMMAND_POLL_SECONDS = 10,                -- Faz 8: /commands/pending
    ENABLE_COMMANDS = false,                  -- Komut kuyruğu uçları Faz 8'de açılır; o zamana kadar false kalmalı
    REQUEST_TIMEOUT_MS = 8000,
    QUEUE_LIMIT = 500,                        -- Bellek içi olay kuyruğu üst sınırı
    LINK_COMMAND_COOLDOWN = 10,               -- /webbagla oyuncu başına bekleme (sn)
    LOG_LEVEL = "info",                       -- debug | info | warn | error

    -- Oyun hesabının kalıcı referansı. Varsayılan: MTA hesap ID'si. Gamemode kendi hesap sistemini kullanıyorsa değiştir.
    getAccountRef = function(player)
        local account = getPlayerAccount(player)
        if not account or isGuestAccount(account) then return nil end
        return tostring(getAccountID(account))
    end,
    getAccountName = function(player)
        local account = getPlayerAccount(player)
        if not account or isGuestAccount(account) then return nil end
        return getAccountName(account)
    end,
}
