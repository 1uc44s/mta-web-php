<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Yalnızca gerekçe isteyen kritik işlemler (rol kaldırma vb.) — docs/ADMIN.md §8. Yetki rota `can:` ile. */
final class ReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:1000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => __('admin.reason')];
    }
}
