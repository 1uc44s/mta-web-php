<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Models\AcademyCourse;
use App\Models\AcademyTopic;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Rule;
use App\Models\Tag;
use App\Models\User;
use App\Services\Content\ContentCache;
use App\Services\Content\ContentResourceRegistry;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CMS kaydı oluşturma/güncelleme — docs/CMS.md §2–3. Slug, revizyon (sayfa/kural), ilişki senkronu ve cache temizliği.
 * Yayın durumu burada değişmez; iş akışı ContentWorkflow'dadır.
 */
final class SaveContent
{
    /** @param  array<string, mixed>  $data  ContentRequest::validated() */
    public function handle(string $resource, ?Model $model, array $data, User $actor): Model
    {
        $def = ContentResourceRegistry::get($resource);
        /** @var class-string<Model> $class */
        $class = $def['model'];
        $model ??= new $class;
        $creating = ! $model->exists;

        $saved = DB::transaction(function () use ($def, $model, $data, $actor, $creating): Model {
            if (! $creating && isset($def['revisions'])) {
                $this->revision($def['revisions'], $model, $actor, (string) ($data['revision_note'] ?? ''));
            }

            $attributes = [];
            foreach ($def['fields'] as $name => $field) {
                if (in_array($field['type'], ['tags', 'rules'], true)) {
                    continue;
                }
                $value = $data[$name] ?? null;
                if (($value === null || $value === '') && array_key_exists('default', $field)) {
                    $value = $field['default']; // DB'de NOT NULL + varsayılanlı kolonlar (ör. author_label)
                }
                $attributes[$name] = match ($field['type']) {
                    'bool' => (bool) $value,
                    'int', 'media', 'belongs' => $value === null || $value === '' ? null : (int) $value,
                    'channels' => array_values(array_intersect(array_keys($field['options'] ?? []), (array) $value)),
                    default => $value === '' ? null : $value,
                };
            }

            if ($def['slug_from'] !== null) {
                $attributes['slug'] = ($data['slug'] ?? '') !== '' ? (string) $data['slug'] : $this->uniqueSlug($model, (string) $attributes[$def['slug_from']]);
            }

            foreach (['seo_title', 'meta_description'] as $seo) {
                if (array_key_exists($seo, $data)) {
                    $attributes[$seo] = $data[$seo] === '' ? null : $data[$seo];
                }
            }

            if ($def['publishable']) {
                if (array_key_exists('published_at', $data) && $data['published_at'] !== null && $data['published_at'] !== '') {
                    $attributes['published_at'] = $data['published_at'];
                }
                $attributes['updated_by'] = $actor->id;
                if ($creating) {
                    $attributes['created_by'] = $actor->id;
                }
            }

            $model->fill($attributes);
            if ($creating && $def['sortable']) {
                $model->setAttribute('sort', (int) $model->newQuery()->max('sort') + 1);
            }
            $model->save();

            $this->syncRelations($model, $data);

            if ($model instanceof AcademyCourse && $model->is_onboarding) {
                AcademyCourse::query()->whereKeyNot($model->id)->update(['is_onboarding' => false]);
            }

            return $model;
        });

        ContentCache::flush();

        return $saved;
    }

    private function uniqueSlug(Model $model, string $source): string
    {
        $base = Slug::make($source);
        $slug = $base;
        $i = 2;

        while ($model->newQuery()->where('slug', $slug)->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))->exists()) {
            $slug = mb_substr($base, 0, 115).'-'.$i++;
        }

        return $slug;
    }

    private function revision(string $type, Model $model, User $actor, string $note): void
    {
        if ($type === 'page' && $model instanceof Page) {
            DB::table('page_revisions')->insert([
                'page_id' => $model->id,
                'snapshot' => json_encode([
                    'page' => $model->only(['title', 'template', 'body', 'version_label', 'seo_title', 'meta_description']),
                    'sections' => $model->sections()->get(['type', 'data', 'sort', 'is_visible'])->toArray(),
                ], JSON_UNESCAPED_UNICODE),
                'note' => mb_substr($note, 0, 255) ?: null,
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);
        }

        if ($type === 'rule' && $model instanceof Rule) {
            DB::table('rule_revisions')->insert([
                'rule_id' => $model->id, 'title' => $model->title, 'body' => $model->body,
                'changed_by' => $actor->id, 'change_note' => mb_substr($note, 0, 255) ?: null, 'created_at' => now(),
            ]);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function syncRelations(Model $model, array $data): void
    {
        if ($model instanceof NewsPost && array_key_exists('tags', $data)) {
            $ids = collect(explode(',', (string) $data['tags']))
                ->map(fn (string $t): string => trim($t))->filter()->unique()->take(10)
                ->map(fn (string $name): int => Tag::query()->firstOrCreate(['slug' => Slug::make($name)], ['name' => mb_substr($name, 0, 50)])->id)
                ->values()->all();
            $model->tags()->sync($ids);
        }

        if ($model instanceof AcademyTopic && array_key_exists('rules', $data)) {
            $codes = collect(explode(',', (string) $data['rules']))->map(fn (string $c): string => mb_strtoupper(trim($c)))->filter()->values()->all();
            $model->rules()->sync(Rule::query()->whereIn('code', $codes)->pluck('id')->all());
        }
    }
}
