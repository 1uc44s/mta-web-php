<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Integrations\ManageIntegrations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CredentialRequest;
use App\Http\Requests\Admin\MtaServerRequest;
use App\Http\Requests\Admin\ReasonRequest;
use App\Models\IntegrationCredential;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Bridge\ServerStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MTA entegrasyonu — docs/ROUTES.md §6.11. Yeni anahtarın secret'ı flash/oturuma yazılmaz; oluşturma isteğinin
 * yanıtında doğrudan view ile bir kez gösterilir (yenilemede kaybolur).
 */
final class IntegrationController extends Controller
{
    public function __construct(private readonly ManageIntegrations $integrations, private readonly ServerStatusResolver $resolver) {}

    public function index(): View
    {
        return $this->page();
    }

    public function storeServer(MtaServerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('reason');
        $this->integrations->saveServer($this->actor($request), null, $data, (string) $request->validated('reason'));

        return redirect()->route('admin.integrations.mta')->with('status', __('game.admin.done'));
    }

    public function updateServer(MtaServerRequest $request, string $server): RedirectResponse
    {
        $data = $request->safe()->except('reason');
        $this->integrations->saveServer($this->actor($request), MtaServer::query()->findOrFail((int) $server), $data, (string) $request->validated('reason'));

        return redirect()->route('admin.integrations.mta')->with('status', __('game.admin.done'));
    }

    public function storeCredential(CredentialRequest $request, string $server): View
    {
        $data = $request->validated();
        $issued = $this->integrations->issueCredential(
            $this->actor($request),
            MtaServer::query()->findOrFail((int) $server),
            array_values($data['scopes']),
            isset($data['expires_at']) ? CarbonImmutable::parse($data['expires_at']) : null,
            (string) $data['reason'],
        );

        return $this->page(['key_id' => $issued['credential']->key_id, 'secret' => $issued['secret']]);
    }

    public function revokeCredential(ReasonRequest $request, string $credential): RedirectResponse
    {
        $this->integrations->revokeCredential($this->actor($request), IntegrationCredential::query()->findOrFail((int) $credential), (string) $request->validated('reason'));

        return redirect()->route('admin.integrations.mta')->with('status', __('game.admin.done'));
    }

    /** @param  array{key_id: string, secret: string}|null  $issued */
    private function page(?array $issued = null): View
    {
        $servers = MtaServer::query()->orderByDesc('is_primary')->orderBy('name')->get();

        return view('admin.integrations.mta', [
            'servers' => $servers,
            'statuses' => $servers->mapWithKeys(fn (MtaServer $s): array => [$s->id => $this->resolver->resolve($s)]),
            'credentials' => IntegrationCredential::query()->with('server:id,name')->latest('id')->get(),
            'scopes' => IntegrationCredential::SCOPES,
            'issued' => $issued,
        ]);
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
