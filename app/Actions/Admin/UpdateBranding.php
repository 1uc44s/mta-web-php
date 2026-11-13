<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use App\Services\Content\ContentCache;
use App\Services\Settings\SettingsRepository;
use App\Support\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

/**
 * ACP → Marka — docs/DESIGN.md §2, ROUTES §6. Ad/kısa ad/slogan `UpdateSettings` üzerinden (audit), logo ve sekme ikonu
 * yeniden kodlanarak `public` diske yazılır (ham dosya, metadata ve SVG saklanmaz). Eski görsel dosyaları silinir.
 */
final class UpdateBranding
{
    public const LOGO_MAX_WIDTH = 640;

    public const LOGO_MAX_HEIGHT = 200;

    public const ICON_MIN_EDGE = 64;

    public function __construct(private readonly UpdateSettings $settings, private readonly SettingsRepository $repository) {}

    /** @param  array{name: string, short_name: string, tagline: string|null}  $text */
    public function handle(User $actor, array $text, ?UploadedFile $logo, ?UploadedFile $icon): int
    {
        $values = ['site.name' => $text['name'], 'site.short_name' => $text['short_name'], 'site.tagline' => $text['tagline']];

        if ($logo !== null) {
            $values['site.logo_path'] = $this->storeLogo($logo);
        }
        if ($icon !== null) {
            $values['site.icon_set'] = $this->storeIcons($icon);
        }

        $old = ['site.logo_path' => $this->repository->get('site.logo_path'), 'site.icon_set' => $this->repository->get('site.icon_set')];
        $changed = $this->settings->handle($actor, 'site', $values);
        ContentCache::flush();
        config(['app.name' => Brand::name()]); // aynı istekte (ve uzun süreli işçilerde) yeni ad

        foreach (['site.logo_path', 'site.icon_set'] as $key) {
            if (array_key_exists($key, $values)) {
                $this->deleteAsset($old[$key]);
            }
        }

        return $changed;
    }

    public function remove(User $actor, string $asset): void
    {
        $key = $asset === 'logo' ? 'site.logo_path' : 'site.icon_set';
        $old = $this->repository->get($key);
        $this->settings->handle($actor, 'site', [$key => null]);
        ContentCache::flush();
        $this->deleteAsset($old);
    }

    private function storeLogo(UploadedFile $file): string
    {
        $image = $this->decode($file, 'logo');
        $image->scaleDown(self::LOGO_MAX_WIDTH, self::LOGO_MAX_HEIGHT);

        $path = 'branding/logo-'.strtolower((string) Str::ulid()).'.webp';
        Storage::disk('public')->put($path, $image->encode(new WebpEncoder(90, true))->toString());

        return $path;
    }

    private function storeIcons(UploadedFile $file): string
    {
        $image = $this->decode($file, 'icon');
        if (min($image->width(), $image->height()) < self::ICON_MIN_EDGE) {
            throw ValidationException::withMessages(['icon' => __('admin.branding.errors.icon_small', ['min' => self::ICON_MIN_EDGE])]);
        }

        $dir = 'branding/icons-'.strtolower((string) Str::ulid());
        foreach (Brand::ICON_SIZES as $size) {
            $variant = clone $image;
            $variant->cover($size, $size);
            Storage::disk('public')->put($dir.'/icon-'.$size.'.png', $variant->encode(new PngEncoder)->toString());
        }

        return $dir;
    }

    private function decode(UploadedFile $file, string $field): ImageInterface
    {
        try {
            return ImageManager::usingDriver(GdDriver::class)->decodePath((string) $file->getRealPath());
        } catch (Throwable) {
            throw ValidationException::withMessages([$field => __('admin.branding.errors.unreadable')]);
        }
    }

    /** Yalnızca `branding/` altındaki eski dosya/klasör silinir (yol enjeksiyonu yok). */
    private function deleteAsset(mixed $path): void
    {
        if (! is_string($path) || preg_match('#^branding/(logo-[0-9a-z]{26}\.webp|icons-[0-9a-z]{26})$#', $path) !== 1) {
            return;
        }

        $disk = Storage::disk('public');
        str_ends_with($path, '.webp') ? $disk->delete($path) : $disk->deleteDirectory($path);
    }
}
