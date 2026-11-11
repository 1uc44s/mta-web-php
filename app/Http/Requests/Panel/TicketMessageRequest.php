<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Ticket mesajı (oyuncu ve yetkili) — docs/ROUTES.md §5.4, §6.4. Yetkili için `visibility` (public|internal) ve
 * isteğe bağlı makro kimliği; iç not izni (support.internal_note) controller'da kontrol edilir.
 */
final class TicketMessageRequest extends FormRequest
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
            'visibility' => ['nullable', 'in:public,internal'],
            'attachments' => ['array', 'max:3'],
            'attachments.*' => TicketStoreRequest::ATTACHMENT_RULES,
        ];
    }

    /** @return list<UploadedFile> */
    public function attachmentFiles(): array
    {
        return array_values(array_filter((array) $this->file('attachments', []), fn ($f): bool => $f instanceof UploadedFile));
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'Mesaj', 'attachments.*' => 'Ek'];
    }
}
