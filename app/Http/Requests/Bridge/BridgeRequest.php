<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Köprü istek şemalarının tabanı — docs/SRS.md FR-BRG-005. Yetki `bridge:{scope}` middleware'inde doğrulanır;
 * bilinmeyen alanlar `validated()` dışında kalır ve hiçbir yere yazılmaz. Hatalar `VALIDATION_ERROR` JSON zarfıyla döner.
 */
abstract class BridgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    protected static function eventRules(): array
    {
        return ['event_id' => ['required', 'string', 'uuid']];
    }

    /** @return array<string, mixed> */
    protected static function accountRules(string $prefix = 'account'): array
    {
        return [
            $prefix => ['required', 'array'],
            $prefix.'.ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            $prefix.'.name' => ['required', 'string', 'max:64'],
        ];
    }
}
