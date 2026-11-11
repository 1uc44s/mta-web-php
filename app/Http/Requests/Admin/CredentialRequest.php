<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\IntegrationCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Köprü anahtarı üretimi — docs/ROUTES.md §6.11, SRS FR-BRG-004. */
final class CredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(IntegrationCredential::SCOPES)],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['scopes' => __('game.admin.scopes'), 'expires_at' => __('game.admin.expires_at'), 'reason' => __('admin.reason')];
    }
}
