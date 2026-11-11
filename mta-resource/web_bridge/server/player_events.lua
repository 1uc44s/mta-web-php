-- Giriş/çıkış olayları — docs/ROUTES.md §7.2. Web yalnızca bağlı hesapları günceller (veri minimizasyonu).
local sessions = {}

local function accountPayload(player)
    local ref, name = CONFIG.getAccountRef(player), CONFIG.getAccountName(player)
    if not ref or not name then return nil end
    return { ref = ref, name = name }
end

-- Diğer resource'lar için: exports.web_bridge:queueEvent("player.connected", {...})
function queueEvent(eventType, payload)
    local path = EVENT_ENDPOINTS[eventType]
    if not path or type(payload) ~= "table" then return false end
    payload.event_id = payload.event_id or Util.uuid()
    Request.enqueue(path, payload)
    return true
end

addEventHandler("onPlayerLogin", root, function()
    local account = accountPayload(source)
    if not account then return end
    sessions[source] = getRealTime().timestamp
    queueEvent("player.connected", { occurred_at = Util.isoTime(), account = account })
end)

addEventHandler("onPlayerQuit", root, function()
    local account = accountPayload(source)
    local started = sessions[source]
    sessions[source] = nil
    if not account then return end
    queueEvent("player.disconnected", {
        occurred_at = Util.isoTime(),
        account = account,
        session_seconds = started and (getRealTime().timestamp - started) or nil,
    })
end)
