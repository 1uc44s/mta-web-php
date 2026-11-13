<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/push/subscriptions — tarayıcı `PushSubscription.toJSON()` (docs/ROUTES.md §5.1). Yalnızca HTTPS endpoint. */
final class PushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2000', 'url:https', 'starts_with:https://'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+={0,2}$/'],
            'keys.auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+={0,2}$/'],
            'contentEncoding' => ['nullable', 'string', 'in:aes128gcm,aesgcm'],
        ];
    }

    /** @return array{endpoint: string, keys: array{p256dh: string, auth: string}, contentEncoding?: string|null} */
    public function subscription(): array
    {
        /** @var array{endpoint: string, keys: array{p256dh: string, auth: string}, contentEncoding?: string|null} $data */
        $data = $this->validated();

        return $data;
    }
}
