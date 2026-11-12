<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Başvuruya yetkili mesajı — docs/ROUTES.md §6.5 (`visibility=public|internal`). */
final class StaffMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'visibility' => ['required', 'string', 'in:public,internal'],
        ];
    }
}
