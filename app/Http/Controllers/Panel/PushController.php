<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PushSubscriptionRequest;
use App\Models\User;
use App\Services\Push\PushSubscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Web Push abonelikleri — docs/ROUTES.md §5.1 (F:push). Yalnızca oturum sahibinin abonelikleri. */
final class PushController extends Controller
{
    public function __construct(private readonly PushSubscriptions $push) {}

    public function subscribe(PushSubscriptionRequest $request): JsonResponse
    {
        abort_unless($this->push->available(), 404);
        /** @var User $user */
        $user = $request->user();
        $this->push->subscribe($user, $request->subscription(), $request->userAgent());

        return response()->json(['success' => true, 'data' => ['subscribed' => true]], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $endpoint = $request->validate(['endpoint' => ['required', 'string', 'max:2000']])['endpoint'];
        $removed = $this->push->unsubscribe($user, (string) $endpoint);

        return response()->json(['success' => true, 'data' => ['removed' => $removed]]);
    }
}
