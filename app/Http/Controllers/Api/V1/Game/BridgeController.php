<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Game;

use App\Actions\Bridge\RecordHeartbeat;
use App\Actions\Bridge\SyncCharacters;
use App\Actions\Bridge\SyncPresence;
use App\Actions\GameAccounts\VerifyLinkCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bridge\AccountLinkVerifyRequest;
use App\Http\Requests\Bridge\AccountsSyncRequest;
use App\Http\Requests\Bridge\CharactersSyncRequest;
use App\Http\Requests\Bridge\HeartbeatRequest;
use App\Http\Requests\Bridge\PlayerEventRequest;
use App\Models\MtaServer;
use App\Services\Bridge\BridgeException;
use App\Support\BridgeResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/game/*` — docs/ROUTES.md §7.2. İmza, zaman penceresi, nonce ve kapsam `bridge:{scope}` middleware'inde
 * doğrulanmıştır; sunucu bağlamı `bridge.server` istek özniteliğinden okunur. İş mantığı Action katmanında.
 */
final class BridgeController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        return BridgeResponse::ok(['server' => $this->server($request)->slug, 'time' => time()]);
    }

    public function heartbeat(HeartbeatRequest $request, RecordHeartbeat $action): JsonResponse
    {
        return BridgeResponse::ok($action->handle($this->server($request), $request->validated()));
    }

    public function playerConnected(PlayerEventRequest $request, SyncPresence $action): JsonResponse
    {
        return BridgeResponse::ok($action->player($this->server($request), 'player.connected', $request->validated()));
    }

    public function playerDisconnected(PlayerEventRequest $request, SyncPresence $action): JsonResponse
    {
        return BridgeResponse::ok($action->player($this->server($request), 'player.disconnected', $request->validated()));
    }

    public function accountsSync(AccountsSyncRequest $request, SyncPresence $action): JsonResponse
    {
        return BridgeResponse::ok($action->accounts($this->server($request), $request->validated()));
    }

    public function charactersSync(CharactersSyncRequest $request, SyncCharacters $action): JsonResponse
    {
        return BridgeResponse::ok($action->handle($this->server($request), $request->validated()));
    }

    public function verifyLink(AccountLinkVerifyRequest $request, VerifyLinkCode $action): JsonResponse
    {
        try {
            return BridgeResponse::ok($action->handle($this->server($request), $request->validated()));
        } catch (BridgeException $e) {
            return BridgeResponse::error($e->status, $e->errorCode, $e->getMessage());
        }
    }

    private function server(Request $request): MtaServer
    {
        $server = $request->attributes->get('bridge.server');
        abort_unless($server instanceof MtaServer, 401);

        return $server;
    }
}
