<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Karar formları — başvuru durum geçişi (`status`, FR-APP-006) ve itiraz kararı (`decision`, FR-MOD-005).
 * Herkese açık not oyuncuya gösterilir, iç not gösterilmez.
 */
final class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->routeIs('admin.appeals.decide')
            ? [
                'decision' => ['required', 'string', 'in:accepted,rejected'],
                'note_public' => ['required', 'string', 'min:5', 'max:1000'],
                'note_internal' => ['nullable', 'string', 'max:5000'],
            ]
            : [
                'status' => ['required', 'string', 'in:in_review,needs_info,approved,rejected'],
                'note_public' => ['nullable', 'string', 'max:2000'],
                'note_internal' => ['nullable', 'string', 'max:2000'],
            ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['note_public' => __('applications.admin.note_public'), 'note_internal' => __('applications.admin.note_internal')];
    }
}
