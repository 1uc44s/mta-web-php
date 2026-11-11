-- İmza protokolü — docs/ARCHITECTURE.md §7.2–7.3.
--   canonical = METHOD \n PATH \n TIMESTAMP \n NONCE \n hex(sha256(BODY))
--   signature = hex(hmac_sha256(secret, canonical))
-- HMAC, MTA'nın hash("sha256") fonksiyonu üzerine RFC 2104 ile uygulanır (yerel HMAC desteği varsayılmaz).
-- main.lua başlangıçta tests/vectors.lua ile öz-test yapar; uyuşmazlıkta köprü başlamaz.
Signature = {}

local BLOCK = 64

local function sha256bin(data)
    return Util.hexToBin(hash("sha256", data))
end

function Signature.hmacSha256(key, message)
    if #key > BLOCK then
        key = sha256bin(key)
    end
    key = key .. string.rep("\0", BLOCK - #key)

    local ipad, opad = {}, {}
    for i = 1, BLOCK do
        local b = key:byte(i)
        ipad[i] = string.char(bitXor(b, 0x36))
        opad[i] = string.char(bitXor(b, 0x5c))
    end

    local inner = sha256bin(table.concat(ipad) .. message)
    return hash("sha256", table.concat(opad) .. inner):lower()
end

function Signature.canonical(method, path, timestamp, nonce, body)
    return table.concat({ method:upper(), path, tostring(timestamp), nonce, hash("sha256", body or ""):lower() }, "\n")
end

function Signature.headers(method, path, body)
    local timestamp = tostring(getRealTime().timestamp)
    local nonce = Util.nonce()
    local signature = Signature.hmacSha256(CONFIG.SECRET, Signature.canonical(method, path, timestamp, nonce, body))

    return {
        ["X-Bridge-Key"] = CONFIG.KEY_ID,
        ["X-Bridge-Timestamp"] = timestamp,
        ["X-Bridge-Nonce"] = nonce,
        ["X-Bridge-Signature"] = signature,
        ["Content-Type"] = "application/json",
        ["Accept"] = "application/json",
    }
end

-- Ortak vektörlerle öz-test (Pest de aynı değerleri doğrular: tests/Feature/Bridge/BridgeSecurityTest.php).
function Signature.selfTest(vectors)
    for _, v in ipairs(vectors.hmac) do
        if Signature.hmacSha256(Util.hexToBin(v.key_hex), v.data) ~= v.hmac then
            return false, v.name
        end
    end
    for _, v in ipairs(vectors.canonical) do
        local canonical = Signature.canonical(v.method, v.path, v.timestamp, v.nonce, v.body)
        if Signature.hmacSha256(v.secret, canonical) ~= v.signature then
            return false, v.name
        end
    end
    return true
end
