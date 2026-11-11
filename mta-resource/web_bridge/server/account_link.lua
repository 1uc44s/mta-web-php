-- /webbagla KOD — docs/ARCHITECTURE.md §7.4. Oyuncu başına 10 sn bekleme; kod loglanmaz.
local lastUse = {}

local function reply(player, text, ok)
    outputChatBox("[Meridyen] " .. text, player, ok and 120 or 230, ok and 220 or 170, ok and 160 or 90)
end

addCommandHandler("webbagla", function(player, _, code)
    local now = getTickCount()
    if lastUse[player] and now - lastUse[player] < CONFIG.LINK_COMMAND_COOLDOWN * 1000 then
        reply(player, "Lütfen birkaç saniye bekle.", false)
        return
    end
    lastUse[player] = now

    local ref, name = CONFIG.getAccountRef(player), CONFIG.getAccountName(player)
    if not ref then
        reply(player, "Önce oyunda hesabına giriş yapmalısın.", false)
        return
    end

    code = tostring(code or ""):upper():gsub("[%s%-]", "")
    if not code:match("^[0-9A-Z]+$") or #code ~= 8 then
        reply(player, "Kullanım: /webbagla KOD (paneldeki 8 karakterlik kod)", false)
        return
    end

    Request.send("POST", "/account-link/verify", {
        event_id = Util.uuid(),
        code = code,
        account = { ref = ref, name = name },
    }, function(ok, data)
        if not isElement(player) then return end
        if ok and type(data) == "table" and data.linked then
            reply(player, LINK_MESSAGES.ok:format(tostring(data.web_username or "")), true)
            triggerEvent("onWebBridgeAccountLinked", player, ref)
        else
            reply(player, LINK_MESSAGES[data] or LINK_MESSAGES.default, false)
        end
    end)
end)

addEventHandler("onPlayerQuit", root, function() lastUse[source] = nil end)
addEvent("onWebBridgeAccountLinked", false)
