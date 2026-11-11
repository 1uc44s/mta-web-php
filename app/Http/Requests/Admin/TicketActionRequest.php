<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** ACP ticket işlemleri — docs/ROUTES.md §6.4. Kurallar rota adına göre seçilir. */
final class TicketActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'admin.support.status' => ['status' => ['required', Rule::in(SupportTicket::STATUSES)]],
            'admin.support.assign' => ['assignee' => ['nullable', 'string', 'max:254']],
            'admin.support.priority' => ['priority' => ['required', Rule::in(SupportTicket::PRIORITIES)]],
            'admin.support.escalate' => ['branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('is_active', true)], 'note' => ['required', 'string', 'min:5', 'max:500']],
            'admin.support.reopen-decision' => ['decision' => ['required', 'in:approve,reject']],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['status' => 'Durum', 'assignee' => __('support.admin.assignee'), 'priority' => 'Öncelik', 'branch_id' => 'Branş', 'note' => __('support.admin.escalate_note')];
    }
}
