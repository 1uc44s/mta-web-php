<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

final class StoreContactMessageRequest extends FormRequest
{
    private const MIN_FILL_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'website' => ['nullable', 'string', 'max:0'], // honeypot
            'form_token' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return trans('public.contact.attributes');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'subject' => trim((string) $this->input('subject')),
        ]);
    }

    /** Form sunucudan alındıktan 3 sn içinde gönderildiyse veya token bozuksa bot kabul edilir. */
    public function looksLikeBot(): bool
    {
        try {
            $issuedAt = (int) Crypt::decryptString((string) $this->input('form_token'));
        } catch (DecryptException) {
            return true;
        }

        return now()->getTimestamp() - $issuedAt < self::MIN_FILL_SECONDS;
    }
}
