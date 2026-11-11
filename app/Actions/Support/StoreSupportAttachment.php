<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Ticket eki — docs/SRS.md FR-SUP-002, SEC-027. Uzantı + finfo MIME (FormRequest) sonrası içerik doğrulaması:
 * görseller decode edilip WebP olarak yeniden kodlanır (EXIF/polyglot yükü atılır), PDF imzası (%PDF-) ve düz metin
 * (NUL baytı yok, geçerli UTF-8) kontrol edilir. Dosya adı ULID; `local` (storage/app/private) diskine yazılır.
 */
final class StoreSupportAttachment
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function handle(UploadedFile $file, SupportMessage $message, User $uploader): SupportAttachment
    {
        $mime = (string) $file->getMimeType();
        $ulid = strtolower((string) Str::ulid());
        $dir = 'support/'.now()->format('Y/m');

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            try {
                $image = ImageManager::usingDriver(GdDriver::class)->decodePath((string) $file->getRealPath());
                $image->scaleDown(2400, 2400);
                $contents = $image->encode(new WebpEncoder(85, true))->toString();
            } catch (Throwable) {
                throw ValidationException::withMessages(['attachments' => 'Görsel okunamadı; dosya bozuk veya geçersiz.']);
            }
            [$path, $storedMime, $ext] = [$dir.'/'.$ulid.'.webp', 'image/webp', 'webp'];
        } elseif ($mime === 'application/pdf') {
            $contents = (string) file_get_contents((string) $file->getRealPath());
            if (! str_starts_with($contents, '%PDF-')) {
                throw ValidationException::withMessages(['attachments' => 'PDF dosyası geçersiz.']);
            }
            [$path, $storedMime, $ext] = [$dir.'/'.$ulid.'.pdf', 'application/pdf', 'pdf'];
        } elseif ($mime === 'text/plain') {
            $contents = (string) file_get_contents((string) $file->getRealPath());
            if (str_contains($contents, "\0") || ! mb_check_encoding($contents, 'UTF-8')) {
                throw ValidationException::withMessages(['attachments' => 'Metin dosyası geçersiz.']);
            }
            [$path, $storedMime, $ext] = [$dir.'/'.$ulid.'.txt', 'text/plain', 'txt'];
        } else {
            throw ValidationException::withMessages(['attachments' => 'Bu dosya türü desteklenmiyor.']);
        }

        Storage::disk('local')->put($path, $contents);

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $clean = trim((string) preg_replace('/[^\pL\pN ._-]+/u', '', $base));
        $safeName = mb_substr($clean !== '' ? $clean : 'ek', 0, 100).'.'.$ext;

        return SupportAttachment::query()->create([
            'ulid' => $ulid,
            'ticket_id' => $message->ticket_id,
            'message_id' => $message->id,
            'uploaded_by' => $uploader->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $safeName,
            'mime' => $storedMime,
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'scan_status' => 'skipped',
        ]);
    }
}
