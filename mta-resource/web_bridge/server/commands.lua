-- Komut kuyruğu (pull) — docs/ARCHITECTURE.md §7.5. Uçlar Faz 8'de açılır; CONFIG.ENABLE_COMMANDS=false iken çalışmaz.
-- Allowlist dışı tür ASLA çalıştırılmaz (failed: unsupported_command). loadstring / executeSQLQuery / shell yoktur.
Commands = {}

local function findPlayerByRef(ref)
    for _, player in ipairs(getElementsByType("player")) do
        if CONFIG.getAccountRef(player) == ref then return player end
    end
    return nil
end

local handlers = {
    ["account.link_revoked"] = function(payload)
        local player = findPlayerByRef(payload.account_ref)
        if player then outputChatBox("[Meridyen] Web hesabı bağlantın kaldırıldı.", player, 230, 170, 90) end
        triggerEvent("onWebBridgeLinkRevoked", root, payload.account_ref)
        return true
    end,
    ["player.warn"] = function(payload)
        local player = findPlayerByRef(payload.account_ref)
        if not player then return false, "player_offline" end
        outputChatBox("[Uyarı] " .. tostring(payload.message or ""), player, 230, 170, 90)
        return true
    end,
    ["player.kick"] = function(payload)
        local player = findPlayerByRef(payload.account_ref)
        if not player then return false, "player_offline" end
        kickPlayer(player, "Meridyen", tostring(payload.reason or ""))
        return true
    end,
    ["server.announce"] = function(payload)
        local message = tostring(payload.message or "")
        if #message == 0 or #message > 200 then return false, "invalid_payload" end
        outputChatBox("[Duyuru] " .. message, root, 120, 200, 255)
        return true
    end,
}
-- player.mute / jail / ban / unban: gamemode'un ceza sistemine bağlanır (Faz 8, onWebBridgeCommand olayı).
addEvent("onWebBridgeCommand", false)
addEvent("onWebBridgeLinkRevoked", false)

local function report(id, ok, errorCode)
    Request.send("POST", "/commands/result", {
        id = id, status = ok and "succeeded" or "failed",
        detail = ok and { applied_at = Util.isoTime() } or { error = errorCode or "failed" },
    })
end

function Commands.poll()
    if not CONFIG.ENABLE_COMMANDS then return end
    Request.send("GET", "/commands/pending", nil, function(ok, data)
        if not ok or type(data) ~= "table" then return end
        for _, command in ipairs(data) do
            if not COMMAND_ALLOWLIST[command.type] then
                report(command.id, false, "unsupported_command")
            elseif type(command.payload) ~= "table" then
                report(command.id, false, "invalid_payload")
            else
                local handler = handlers[command.type]
                if handler then
                    local success, result, errorCode = pcall(handler, command.payload)
                    report(command.id, success and result, success and errorCode or "invalid_payload")
                else
                    triggerEvent("onWebBridgeCommand", root, command.id, command.type, command.payload)
                end
            end
        end
    end)
end

-- Gamemode, onWebBridgeCommand ile aldığı komutun sonucunu bununla bildirir.
function Commands.report(id, ok, errorCode) report(id, ok, errorCode) end
