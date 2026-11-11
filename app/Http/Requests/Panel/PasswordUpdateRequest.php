<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/security/password — docs/SRS.md FR-AUTH-019 (mevcut parola zorunlu). */
final class PasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [...PasswordRules::for($user->username, $user->email), 'different:current_password'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['current_password' => 'Mevcut parola', 'password' => 'Yeni parola'];
    }
}
