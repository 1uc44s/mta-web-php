<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tek gövde alanlı oyuncu mesajları — başvuru yanıtı (`needs_info`) ve ceza itirazı (FR-APP-003, FR-MOD-005).
 * İtiraz rotasında en az 30 karakter istenir (`appeal` rota adı).
 */
final class MessageBodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $min = $this->routeIs('panel.sanctions.appeal') ? 30 : 2;

        return ['body' => ['required', 'string', 'min:'.$min, 'max:5000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => $this->routeIs('panel.sanctions.appeal') ? __('moderation.appeal_body') : __('applications.reply')];
    }
}
