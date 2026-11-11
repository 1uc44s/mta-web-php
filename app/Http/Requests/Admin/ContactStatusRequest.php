<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\ContactMessageController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** POST /admin/contact/{message}/status — docs/ROUTES.md §6.9. */
final class ContactStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(ContactMessageController::STATUSES)]];
    }
}
