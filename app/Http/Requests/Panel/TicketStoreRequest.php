<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * POST /panel/support — docs/SRS.md FR-SUP-001 (kategori aktif, konu 5–120, açıklama 20–5000, ≤ 3 ek),
 * FR-SUP-002 (uzantı + finfo MIME + ≤ 5 MB; içerik doğrulaması StoreSupportAttachment'ta), FR-SUP-003 (idempotency).
 */
final class TicketStoreRequest extends FormRequest
{
    public const ATTACHMENT_RULES = ['file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,txt', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('support_categories', 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'min:5', 'max:120'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'idempotency_key' => ['required', 'uuid'],
            'attachments' => ['array', 'max:3'],
            'attachments.*' => self::ATTACHMENT_RULES,
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
        return ['category_id' => 'Kategori', 'subject' => 'Konu', 'body' => 'Açıklama', 'attachments' => 'Ekler', 'attachments.*' => 'Ek'];
    }
}
