-- Paylaşılan sabitler — docs/ARCHITECTURE.md §7.5. Komut allowlist'i config/roleplay.php `bridge.commands` ile birebir aynıdır.
BRIDGE_VERSION = "1.0.0"
API_PREFIX = "/api/v1/game"

COMMAND_ALLOWLIST = {
    ["player.warn"] = true,
    ["player.kick"] = true,
    ["player.mute"] = true,
    ["player.jail"] = true,
    ["player.ban"] = true,
    ["player.unban"] = true,
    ["server.announce"] = true,
    ["account.link_revoked"] = true,
}

EVENT_ENDPOINTS = {
    ["player.connected"] = "/player/connected",
    ["player.disconnected"] = "/player/disconnected",
    ["accounts.sync"] = "/accounts/sync",
    ["characters.sync"] = "/characters/sync",
}

-- Köprü hata kodları → oyuncuya gösterilen genel mesajlar (sebep ayrıntısı verilmez — ARCHITECTURE §7.4).
LINK_MESSAGES = {
    ok = "Hesabın web paneline bağlandı: %s",
    LINK_CODE_INVALID = "Kod geçersiz veya süresi dolmuş. Panelden yeni kod oluşturabilirsin.",
    ACCOUNT_ALREADY_LINKED = "Bu oyun hesabı zaten bir web hesabına bağlı. Destek ekibiyle iletişime geç.",
    LINK_LIMIT_REACHED = "Web hesabın en fazla bağlantı sayısına ulaştı.",
    RATE_LIMITED = "Çok fazla deneme yaptın. Birkaç dakika sonra tekrar dene.",
    default = "Şu anda doğrulama yapılamıyor. Lütfen daha sonra tekrar dene.",
}
