<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\PasswordRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * docs/SRS.md FR-AUTH-001/002. E-posta için "unique" kuralı YOKTUR (hesap varlığı ifşa edilmez, SEC-005);
 * kullanıcı adları zaten herkese açıktır, benzersizlik hatası gösterilir.
 */
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => [
                'required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_.]+$/',
                // Rezerv adlar büyük/küçük harf duyarsız karşılaştırılır ("Admin" = "admin").
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (in_array(mb_strtolower((string) $value), config('roleplay.reserved_usernames'), true)) {
                        $fail(__('auth.username_reserved'));
                    }
                },
                Rule::unique('users', 'username'),
            ],
            'email' => ['required', 'string', 'email:rfc,strict', 'max:254'],
            'password' => PasswordRules::for($this->string('username')->toString(), $this->string('email')->toString()),
            'terms' => ['accepted'],
            'kvkk' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return trans('auth.attributes');
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.regex' => __('auth.username_format'),
            'terms.accepted' => __('auth.terms_required'),
            'kvkk.accepted' => __('auth.kvkk_required'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim((string) $this->input('username')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
