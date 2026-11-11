<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/** Bağlantı kaldırma talebi — docs/SRS.md FR-ACC-006 (gerekçe zorunlu). Sahiplik controller sorgusunda. */
final class UnlinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => __('game.link.unlink_reason')];
    }
}
