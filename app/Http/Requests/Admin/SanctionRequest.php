<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Sanction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ceza oluşturma — docs/ROUTES.md §6.7, SRS FR-MOD-001/002, ADMIN §8 (kalıcı yasakta kanıt zorunlu). */
final class SanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['appealable' => $this->boolean('appealable')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(Sanction::TYPES)],
            'user' => ['nullable', 'string', 'max:32', 'required_without_all:account,character'],
            'account' => ['nullable', 'string', 'regex:/^[0-9a-z]{26}$/'],
            'character' => ['nullable', 'string', 'regex:/^[0-9a-z]{26}$/'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600', Rule::requiredIf(in_array($this->input('type'), Sanction::TIMED, true))],
            'public_reason' => ['required', 'string', 'min:5', 'max:500'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'evidence_url' => ['nullable', 'url:https', 'max:500', Rule::requiredIf($this->input('type') === 'perm_ban')],
            'rule_id' => ['nullable', 'integer', 'exists:rules,id'],
            'appealable' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'type' => __('moderation.admin.type'), 'user' => __('moderation.admin.user'), 'account' => __('moderation.admin.account'),
            'character' => __('moderation.admin.character'), 'duration_minutes' => __('moderation.admin.duration'),
            'public_reason' => __('moderation.admin.public_reason'), 'internal_note' => __('moderation.admin.internal_note'),
            'evidence_url' => __('moderation.admin.evidence'), 'rule_id' => __('moderation.admin.rule'),
        ];
    }
}
