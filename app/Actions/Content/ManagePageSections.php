<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Enums\AuditAction;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentCache;
use App\Services\Content\SectionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Sayfa bölümleri — docs/CMS.md §4. Veri şemaya göre doğrulanır; şemada olmayan anahtarlar atılır.
 * Sayfa revizyonları (snapshot) ve geri yükleme de buradadır (ROUTES §6.8).
 */
final class ManagePageSections
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function add(Page $page, string $type): PageSection
    {
        if (! in_array($type, SectionRegistry::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Bilinmeyen bölüm türü.']);
        }

        $section = $page->sections()->create([
            'type' => $type, 'data' => SectionRegistry::defaults($type),
            'sort' => (int) $page->sections()->max('sort') + 1, 'is_visible' => false,
        ]);
        ContentCache::flush();

        return $section;
    }

    /** @param  array<string, mixed>  $data */
    public function update(PageSection $section, array $data, bool $visible): void
    {
        $schema = SectionRegistry::schema($section->type);
        $validator = Validator::make($data, $schema);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['data' => $validator->errors()->all()]);
        }

        $allowed = array_unique(array_map(fn (string $k): string => explode('.', $k)[0], array_keys($schema)));
        $clean = array_intersect_key($validator->validated(), array_flip($allowed));

        $section->update(['data' => $clean, 'is_visible' => $visible]);
        ContentCache::flush();
    }

    public function delete(PageSection $section): void
    {
        $section->delete();
        ContentCache::flush();
    }

    public function move(Page $page, PageSection $section, bool $up): void
    {
        $neighbor = $page->sections()->where('sort', $up ? '<' : '>', $section->sort)->orderBy('sort', $up ? 'desc' : 'asc')->first();

        if ($neighbor !== null) {
            DB::transaction(function () use ($section, $neighbor): void {
                [$a, $b] = [$section->sort, $neighbor->sort];
                $section->update(['sort' => $b]);
                $neighbor->update(['sort' => $a]);
            });
            ContentCache::flush();
        }
    }

    /** Revizyon snapshot'ını geri yükler; mevcut hâl önce yeni revizyon olarak saklanır. */
    public function restore(Page $page, int $revisionId, User $actor): void
    {
        $revision = DB::table('page_revisions')->where(['id' => $revisionId, 'page_id' => $page->id])->first();
        abort_if($revision === null, 404);

        /** @var array{page: array<string, mixed>, sections: list<array{type: string, data: array<string, mixed>|string|null, sort: int, is_visible: bool}>} $snapshot */
        $snapshot = json_decode((string) $revision->snapshot, true);

        DB::transaction(function () use ($page, $snapshot, $actor, $revisionId): void {
            DB::table('page_revisions')->insert([
                'page_id' => $page->id,
                'snapshot' => json_encode([
                    'page' => $page->only(['title', 'template', 'body', 'version_label', 'seo_title', 'meta_description']),
                    'sections' => $page->sections()->get(['type', 'data', 'sort', 'is_visible'])->toArray(),
                ], JSON_UNESCAPED_UNICODE),
                'note' => "#{$revisionId} geri yüklenmeden önceki hâl",
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            $page->fill(array_intersect_key($snapshot['page'], array_flip(['title', 'template', 'body', 'version_label', 'seo_title', 'meta_description'])))->save();
            $page->sections()->delete();
            foreach ($snapshot['sections'] as $s) {
                $page->sections()->create([
                    'type' => $s['type'], 'data' => is_string($s['data']) ? json_decode($s['data'], true) : $s['data'],
                    'sort' => $s['sort'], 'is_visible' => (bool) $s['is_visible'],
                ]);
            }
        });

        ContentCache::flush();
        $this->audit->record(AuditAction::ContentPublished, $page, ['after' => ['restored_revision' => $revisionId]], null, $actor);
    }
}
