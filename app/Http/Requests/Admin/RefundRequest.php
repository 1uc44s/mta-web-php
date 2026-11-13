<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** İade talebi — docs/SRS.md FR-PAY-005. Tutar kuruş (minor) cinsindendir; üst sınır Action'da kalan tutara göre. */
final class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount_minor' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['amount_minor' => __('economy.admin.refund_amount'), 'reason' => __('admin.reason')];
    }
}
