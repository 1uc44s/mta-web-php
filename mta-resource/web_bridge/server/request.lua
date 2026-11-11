-- fetchRemote sarmalayıcı: imza, timeout, üstel geri çekilme (1 / 5 / 15 sn, max 5 deneme), bellek içi kuyruk.
-- docs/ARCHITECTURE.md §7.7 "Dayanıklılık". event_id sabit kaldığı için tekrar gönderim güvenlidir (FR-BRG-007).
Request = {}

local BACKOFF = { 1000, 5000, 15000, 15000 }
local MAX_ATTEMPTS = 5
local queue = {}
local inFlight = 0

-- Tekrar denenmemesi gereken durumlar: imza/doğrulama/iş kuralı hataları (istemci hatası).
local function retryable(status)
    return status == 0 or status == 429 or status >= 500
end

local function send(method, path, payload, callback, attempt)
    local body = payload ~= nil and Util.encode(payload) or ""
    local fullPath = API_PREFIX .. path

    inFlight = inFlight + 1
    fetchRemote(CONFIG.BASE_URL .. fullPath, {
        queueName = "web_bridge",
        connectionAttempts = 1,
        connectTimeout = CONFIG.REQUEST_TIMEOUT_MS,
        method = method,
        headers = Signature.headers(method, fullPath, body),
        postData = method ~= "GET" and body or nil,
    }, function(responseData, info)
        inFlight = inFlight - 1
        local status = (info and info.statusCode) or 0
        local decoded = Util.decode(responseData)

        if status >= 200 and status < 300 then
            if callback then callback(true, decoded and decoded.data, status) end
            return
        end

        if retryable(status) and attempt < MAX_ATTEMPTS then
            local delay = BACKOFF[attempt] or 15000
            Util.log("warn", "%s %s → %d, %d ms sonra tekrar (deneme %d)", method, path, status, delay, attempt + 1)
            setTimer(send, delay, 1, method, path, payload, callback, attempt + 1)
            return
        end

        local code = decoded and decoded.error and decoded.error.code or ("HTTP_" .. status)
        Util.log((status == 401 or status == 409) and "error" or "warn","%s %s başarısız: %s", method, path, code)
        if callback then callback(false, code, status) end
    end)
end

function Request.send(method, path, payload, callback)
    send(method, path, payload, callback, 1)
end

-- Olay kuyruğu: sınır aşılırsa en eski olay düşürülür (bellek koruması).
function Request.enqueue(path, payload)
    if #queue >= CONFIG.QUEUE_LIMIT then
        table.remove(queue, 1)
        Util.log("warn", "Olay kuyruğu dolu, en eski olay düşürüldü")
    end
    queue[#queue + 1] = { path = path, payload = payload }
end

function Request.flush()
    while #queue > 0 and inFlight < 4 do
        local item = table.remove(queue, 1)
        send("POST", item.path, item.payload, nil, 1)
    end
end
