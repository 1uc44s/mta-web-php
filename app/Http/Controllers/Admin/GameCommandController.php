<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Bridge\GameCommandQueue;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameCommandRequest;
use App\Models\GameCommand;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Oyun komut kuyruğu — docs/ROUTES.md §6.7, ARCHITECTURE §7.5. Elle komutta tür → izin eşlemesi
 * `config/roleplay.php bridge.commands`; süresiz `player.ban` ayrıca `game_commands.ban_perm` ister.
 */
final class GameCommandController extends Controller
{
    public function __construct(private readonly GameCommandQueue $queue, private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        return view('admin.game-commands.index', [
            'status' => $status,
            'servers' => MtaServer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'types' => GameCommandRequest::TYPES,
            'commands' => GameCommand::query()->with(['requester:id,username', 'target:id,ulid,account_name'])
                ->when($status !== '', fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function store(GameCommandRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $type = (string) $data['type'];
        $minutes = isset($data['minutes']) ? (int) $data['minutes'] : null;

        // Tür adları nokta içerir (`player.kick`); config yolu yerine dizi üzerinden okunur.
        $permission = (string) (((array) config('roleplay.bridge.commands'))[$type] ?? '');
        abort_unless($permission !== '' && $actor->hasPermission($permission), 403);
        abort_if($type === 'player.ban' && $minutes === null && ! $actor->hasPermission('game_commands.ban_perm'), 403);

        $account = isset($data['account']) ? MtaAccount::query()->where('ulid', $data['account'])->firstOrFail() : null;
        $message = $data['message'] ?? null;
        $payload = match ($type) {
            'server.announce' => ['message' => $message],
            'player.warn' => ['account_ref' => $account?->mta_account_ref, 'message' => $message],
            'player.unban' => ['account_ref' => $account?->mta_account_ref],
            'player.kick' => ['account_ref' => $account?->mta_account_ref, 'reason' => $message],
            default => ['account_ref' => $account?->mta_account_ref, 'minutes' => $minutes, 'reason' => $message],
        };

        $command = $this->queue->enqueue((int) $data['mta_server_id'], $type, $payload, 'manual:'.Str::ulid(), $account, $actor, 'manual');
        $this->audit->record(AuditAction::GameCommandsDispatched, $command, ['after' => ['type' => $type, 'target' => $account?->ulid]], null, $actor);

        return redirect()->route('admin.game-commands.index')->with('status', __('moderation.admin.done'));
    }

    public function cancel(Request $request, string $command): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $model = GameCommand::query()->where('ulid', $command)->firstOrFail();

        abort_unless($model->getAttribute('requested_by') === $actor->id || $actor->isSuperAdmin(), 403);
        $this->queue->cancel($model);

        return back()->with('status', __('moderation.admin.done'));
    }
}
