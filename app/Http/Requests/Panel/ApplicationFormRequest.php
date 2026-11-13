<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use App\Models\Application;
use App\Models\ApplicationType;
use App\Support\ApplicationForms\ApplicationForms;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Başvuru formu — docs/SRS.md FR-APP-002. Kurallar türün sabit şemasından türetilir: `action=save_draft` gevşek,
 * `action=submit` tam doğrulama. Tür, rotadaki `{type}` slug'ı veya `{application}` kaydından okunur.
 */
final class ApplicationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:save_draft,submit'],
            'character' => ['nullable', 'string', 'regex:/^[0-9A-Za-z]{26}$/'],
            'answers' => ['nullable', 'array'],
            ...ApplicationForms::rules($this->formKey(), $this->input('action') !== 'submit'),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ApplicationForms::attributes($this->formKey());
    }

    private function formKey(): string
    {
        $type = $this->route('type');
        $application = $this->route('application');

        $key = match (true) {
            is_string($type) => ApplicationType::query()->where('slug', $type)->value('form_key'),
            // Yalnızca oturum sahibinin başvurusu: başkasınınki doğrulamaya girmeden 404 (varlık sızmaz — R2 IDOR taraması).
            is_string($application) => Application::query()->where('ulid', $application)->where('user_id', $this->user()?->getAuthIdentifier())->first()?->type?->form_key,
            default => null,
        };

        abort_unless(is_string($key) && ApplicationForms::exists($key), 404);

        return $key;
    }
}
