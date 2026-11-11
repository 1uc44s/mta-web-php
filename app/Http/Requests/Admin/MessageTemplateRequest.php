<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Messaging\TemplateRenderer;
use Illuminate\Foundation\Http\FormRequest;

/** POST /admin/templates/{template} ve önizleme — docs/ROUTES.md §6.9. Söz dizimi doğrulaması SaveMessageTemplate'te. */
final class MessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:'.TemplateRenderer::MAX_BYTES],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['subject' => 'Konu', 'body' => 'Gövde'];
    }
}
