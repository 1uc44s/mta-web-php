-- Yardımcılar: uuid v4, JSON, hex, log (secret maskeleme) — docs/ARCHITECTURE.md §7.7.
Util = {}

local counter = 0
local LEVELS = { debug = 1, info = 2, warn = 3, error = 4 }

-- Nonce/UUID için entropi: zaman + tick + sayaç + math.random, sha256 ile karıştırılır. Nonce yalnızca tekil olmalıdır
-- (gizlilik gerekmez); imzanın gücü secret'tan gelir.
local function entropy()
    counter = counter + 1
    return hash("sha256", table.concat({ tostring(getRealTime().timestamp), tostring(getTickCount()), tostring(counter),
        tostring(math.random(0, 2147483647)), tostring(math.random(0, 2147483647)) }, ":")):lower()
end

function Util.nonce()
    return entropy() -- 64 hex = 32 bayt
end

function Util.uuid()
    local h = entropy()
    local variant = ({ "8", "9", "a", "b" })[math.random(1, 4)]
    return ("%s-%s-4%s-%s%s-%s"):format(h:sub(1, 8), h:sub(9, 12), h:sub(14, 16), variant, h:sub(18, 20), h:sub(21, 32))
end

function Util.hexToBin(hex)
    return (hex:gsub("..", function(cc) return string.char(tonumber(cc, 16)) end))
end

-- MTA toJSON değeri [ ] içine sarar; tek nesne için dış köşeli parantezler atılır.
function Util.encode(value)
    local json = toJSON(value, true)
    if json and json:sub(1, 1) == "[" then
        json = json:sub(2, -2)
    end
    return json or "{}"
end

function Util.decode(body)
    if type(body) ~= "string" or body == "" then return nil end
    local ok, value = pcall(fromJSON, "[" .. body .. "]")
    if ok then return value end
    return nil
end

function Util.isoTime(ts)
    local t = getRealTime(ts or getRealTime().timestamp, false)
    return ("%04d-%02d-%02dT%02d:%02d:%02dZ"):format(t.year + 1900, t.month + 1, t.monthday, t.hour, t.minute, t.second)
end

-- Log: secret ve imza asla yazılmaz.
function Util.log(level, message, ...)
    if (LEVELS[level] or 2) < (LEVELS[CONFIG and CONFIG.LOG_LEVEL or "info"] or 2) then return end
    local text = select("#", ...) > 0 and message:format(...) or message
    if CONFIG and type(CONFIG.SECRET) == "string" and #CONFIG.SECRET > 0 then
        text = text:gsub(CONFIG.SECRET:gsub("%p", "%%%0"), "***")
    end
    outputServerLog(("[web_bridge][%s] %s"):format(level, text))
end
