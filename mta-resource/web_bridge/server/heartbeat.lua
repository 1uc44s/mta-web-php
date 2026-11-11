-- Heartbeat — docs/SRS.md FR-BRG-009, ARCHITECTURE §7.8 (30 sn). Yanıttaki bakım bayrağı oyun içinde gösterilebilir.
Heartbeat = { maintenance = false }

local startedAt = getRealTime().timestamp

function Heartbeat.send()
    Request.send("POST", "/heartbeat", {
        players = getPlayerCount(),
        max_players = getMaxPlayers(),
        server_name = getServerName(),
        game_mode = getGameType() or "Roleplay",
        map = getMapName() or "San Andreas",
        resource_version = BRIDGE_VERSION,
        uptime_sec = getRealTime().timestamp - startedAt,
    }, function(ok, data)
        if ok and type(data) == "table" then
            if data.maintenance ~= Heartbeat.maintenance then
                Heartbeat.maintenance = data.maintenance == true
                triggerEvent("onWebBridgeMaintenanceChanged", root, Heartbeat.maintenance)
            end
        end
    end)
end

addEvent("onWebBridgeMaintenanceChanged", false)
