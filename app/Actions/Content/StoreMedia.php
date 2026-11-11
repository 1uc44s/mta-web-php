<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Models\Media;
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
 * Medya işleme hattı — docs/CMS.md §3.3: decode (bozuk dosya reddedilir) → metadata temizliği + yeniden kodlama →
 * WebP varyantları (480 · 960 · 1600) + en fazla 2400 px orijinal. Ham yüklenen dosya saklanmaz.
 */
final class StoreMedia
{
    /** @var list<int> */
    public const WIDTHS = [480, 960, 1600];

    private const MAX_EDGE = 2400;

    private const QUALITY = 82;

    public function handle(UploadedFile $file, string $altText, bool $decorative, User $actor): Media
    {
        $manager = ImageManager::usingDriver(GdDriver::class);

        try {
            $image = $manager->decodePath($file->getRealPath());
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Görsel okunamadı; dosya bozuk veya desteklenmeyen biçimde.']);
        }

        $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
        $disk = Storage::disk('public');
        $base = 'media/'.now()->format('Y/m').'/'.strtolower((string) Str::ulid());

        $originalPath = $base.'.webp';
        $disk->put($originalPath, $image->encode(new WebpEncoder(self::QUALITY, true))->toString());
        [$width, $height] = [$image->width(), $image->height()];

        $variants = [];
        foreach (self::WIDTHS as $target) {
            if ($width <= $target) {
                continue;
            }
            $variant = clone $image;
            $variant->scaleDown($target);
            $path = $base.'-w'.$target.'.webp';
            $disk->put($path, $variant->encode(new WebpEncoder(self::QUALITY, true))->toString());
            $variants[$target] = $path;
        }

        return Media::query()->create([
            'disk' => 'public',
            'path' => $originalPath,
            'variants' => $variants,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => 'image/webp',
            'width' => $width,
            'height' => $height,
            'size_bytes' => (int) $disk->size($originalPath),
            'alt_text' => $decorative ? null : mb_substr($altText, 0, 255),
            'is_decorative' => $decorative,
            'uploaded_by' => $actor->id,
        ]);
    }
}
