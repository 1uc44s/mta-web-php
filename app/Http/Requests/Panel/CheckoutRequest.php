<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Checkout — docs/SRS.md FR-PAY-002. Yalnızca paket kimliği ve form idempotency anahtarı kabul edilir; tutar alanı
 * gönderilse bile `validated()` dışında kalır (tutar sunucuda paketten alınır).
 */
final class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'uuid'],
        ];
    }
}
