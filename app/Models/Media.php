<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * docs/DATABASE.md §10.1, docs/CMS.md §3.3
 *
 * @property array<int, string>|null $variants Genişlik (px) => dosya yolu (PHP sayısal anahtarları int yapar)
 */
final class Media extends Model
{
    use HasPublicUlid;

    protected $table = 'media';

    protected $fillable = [
        'disk', 'path', 'variants', 'original_name', 'mime', 'width', 'height', 'size_bytes',
        'alt_text', 'is_decorative', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'is_decorative' => 'boolean',
        ];
    }

    public function url(?int $width = null): string
    {
        $variants = $this->variants ?? [];
        $path = $width !== null && isset($variants[$width]) ? $variants[$width] : $this->path;

        return Storage::disk($this->disk)->url($path);
    }

    /** "url 480w, url 960w" biçiminde srcset. */
    public function srcset(): string
    {
        $parts = [];
        foreach ($this->variants ?? [] as $width => $path) {
            $parts[] = Storage::disk($this->disk)->url($path).' '.$width.'w';
        }

        return implode(', ', $parts);
    }

    public function altText(): string
    {
        return $this->is_decorative ? '' : (string) $this->alt_text;
    }
}
