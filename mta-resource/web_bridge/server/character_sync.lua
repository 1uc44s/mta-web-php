-- Karakter senkronu — docs/ROUTES.md §7.2. Gamemode karakter özetini export ile gönderir:
--   exports.web_bridge:syncCharacters(player, {
--       { ref = "c-551", name = "Kaan Demir", status = "active", level = 12, playtime_minutes = 5400,
--         faction_key = "lspd", job_key = "taxi", public_profile = { bio = "..." } },
--   })
-- Web yalnızca bağlı hesaplar için kabul eder; public_profile allowlist dışındaki alanları düşürür.
local MAX_PER_REQUEST = 20

function syncCharacters(player, characters)
    if not isElement(player) or type(characters) ~= "table" then return false end
    local ref = CONFIG.getAccountRef(player)
    if not ref then return false end

    local list = {}
    for i = 1, math.min(#characters, MAX_PER_REQUEST) do
        list[i] = characters[i]
    end

    Request.enqueue(EVENT_ENDPOINTS["characters.sync"], { event_id = Util.uuid(), account_ref = ref, characters = list })
    return true
end

-- Bağlantı kurulunca gamemode'dan güncel liste istenir.
addEvent("onWebBridgeRequestCharacters", false)
addEventHandler("onWebBridgeAccountLinked", root, function()
    triggerEvent("onWebBridgeRequestCharacters", source)
end)
