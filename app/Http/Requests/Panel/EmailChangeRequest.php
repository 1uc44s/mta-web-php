<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/security/email — docs/SRS.md FR-AUTH-018. `unique` yok: hesap varlığı ifşa edilmez (SEC-005). */
final class EmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email:rfc,strict', 'max:254']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['email' => 'Yeni e-posta adresi'];
    }
}
