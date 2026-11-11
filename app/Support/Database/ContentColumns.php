<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;

/**
 * Yayınlanabilir içerik tablolarının ortak "yayın blok" kolonları (docs/DATABASE.md §10).
 */
final class ContentColumns
{
    public static function publishable(Blueprint $table, bool $withSeo = true): void
    {
        $table->string('status', 16)->default('draft');
        $table->dateTime('published_at')->nullable();
        $table->smallInteger('sort')->default(0);

        if ($withSeo) {
            $table->string('seo_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
        }

        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->datetimes();

        $table->index(['status', 'published_at']);
    }
}
