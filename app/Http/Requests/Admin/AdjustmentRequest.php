<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Düzeltme / ters kayıt talebi — docs/SRS.md FR-WAL-006 (gerekçe ≥ 20 karakter). Ters kayıt talebinde
 * (`admin.economy.reversal-request`) yön ve tutar orijinal kayıttan alınır; yalnızca gerekçe istenir.
 */
final class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reason = ['reason' => ['required', 'string', 'min:20', 'max:1000']];

        return $this->routeIs('admin.economy.reversal-request') ? $reason : [
            'username' => ['required', 'string', 'max:32', 'exists:users,username'],
            'direction' => ['required', 'string', 'in:credit,debit'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            ...$reason,
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'username' => __('economy.admin.user'), 'direction' => __('economy.admin.direction'),
            'amount' => __('economy.admin.amount'), 'reason' => __('economy.admin.reason'),
        ];
    }
}
