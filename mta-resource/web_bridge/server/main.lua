-- Başlatma: config doğrulama, HMAC öz-testi, zamanlayıcılar — docs/ARCHITECTURE.md §7.7.
local VECTORS_PATH = "tests/vectors.lua"

local function loadVectors()
    local file = fileOpen(VECTORS_PATH, true)
    if not file then return nil end
    local source = fileRead(file, fileGetSize(file))
    fileClose(file)
    -- Yalnızca resource içindeki sabit test dosyası; dış girdi değildir. Boş ortamda çalıştırılır.
    local chunk = loadstring(source)
    if not chunk then return nil end
    setfenv(chunk, {})
    local ok, vectors = pcall(chunk)
    return ok and vectors or nil
end

local function validConfig()
    if type(CONFIG) ~= "table" then return false, "config.lua bulunamadı (config.example.lua'yı kopyala)" end
    if type(CONFIG.BASE_URL) ~= "string" or not CONFIG.BASE_URL:match("^https?://") then return false, "BASE_URL geçersiz" end
    if type(CONFIG.KEY_ID) ~= "string" or not CONFIG.KEY_ID:match("^[%w_]+$") or #CONFIG.KEY_ID < 8 then return false, "KEY_ID geçersiz" end
    if type(CONFIG.SECRET) ~= "string" or #CONFIG.SECRET < 32 then return false, "SECRET eksik veya kısa" end
    if not CONFIG.BASE_URL:match("^https://") and not CONFIG.BASE_URL:match("^http://127%.0%.0%.1") and not CONFIG.BASE_URL:match("^http://localhost") then
        return false, "Üretimde BASE_URL https olmalıdır"
    end
    return true
end

addEventHandler("onResourceStart", resourceRoot, function()
    math.randomseed(getTickCount() + getRealTime().timestamp)

    local ok, reason = validConfig()
    if not ok then
        Util.log("error", "Başlatılamadı: %s", reason)
        cancelEvent()
        return
    end

    local vectors = loadVectors()
    local passed, failed = vectors and Signature.selfTest(vectors)
    if not passed then
        Util.log("error", "HMAC öz-testi başarısız (%s). Köprü devre dışı.", tostring(failed or "vectors.lua okunamadı"))
        cancelEvent()
        return
    end

    Request.send("GET", "/ping", nil, function(success, data)
        if success and type(data) == "table" then
            local drift = math.abs((tonumber(data.time) or 0) - getRealTime().timestamp)
            Util.log(drift > 60 and "warn" or "info", "Web bağlantısı hazır (sunucu: %s, saat farkı: %d sn)", tostring(data.server), drift)
        else
            Util.log("error", "Ping başarısız: %s", tostring(data))
        end
    end)

    Heartbeat.send()
    setTimer(Heartbeat.send, CONFIG.HEARTBEAT_SECONDS * 1000, 0)
    setTimer(Request.flush, 2000, 0)
    setTimer(Commands.poll, CONFIG.COMMAND_POLL_SECONDS * 1000, 0)
    Util.log("info", "web_bridge %s başladı", BRIDGE_VERSION)
end)
