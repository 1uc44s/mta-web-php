<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\ContentWorkflow;
use App\Actions\Content\SaveContent;
use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\AcademyTopic;
use App\Models\Media;
use App\Models\NewsPost;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentCache;
use App\Services\Content\ContentResourceRegistry;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Tüm CMS kaynakları için ortak liste/form/iş akışı — docs/ROUTES.md §6.8 (AdminResource deseni), CMS §2, §9.
 * Kaynak anahtarı rota `defaults('resource')` ile gelir; yetki rota `can:` middleware'iyle (registry::permission).
 */
final class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $resource = self::resource();
        $def = ContentResourceRegistry::get($resource);
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        /** @var Builder<Model> $query */
        $query = $def['model']::query();
        $items = $query
            ->when($q !== '', function (Builder $b) use ($q, $def): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                $b->where(function (Builder $w) use ($like, $def): void {
                    foreach ($def['search'] as $column) {
                        $w->orWhere($column, 'like', $like);
                    }
                });
            })
            ->when($def['publishable'] && ContentStatus::tryFrom($status) !== null, fn (Builder $b) => $b->where('status', $status))
            ->when($def['sortable'], fn (Builder $b) => $b->orderBy('sort'), fn (Builder $b) => $b->latest('id'))
            ->paginate(30)
            ->withQueryString();

        return view('admin.content.index', [
            'resource' => $resource, 'def' => $def, 'items' => $items, 'q' => $q, 'status' => $status,
            'lookups' => $this->lookups($def), 'routeName' => ContentResourceRegistry::routeName($resource),
        ]);
    }

    public function create(): View
    {
        $resource = self::resource();
        $def = ContentResourceRegistry::get($resource);

        return $this->form($resource, new $def['model']);
    }

    public function store(ContentRequest $request, SaveContent $save): RedirectResponse
    {
        $resource = self::resource();
        /** @var User $actor */
        $actor = $request->user();
        $model = $save->handle($resource, null, $request->validated(), $actor);

        return to_route(ContentResourceRegistry::routeName($resource).'.edit', ['id' => $model->getKey()])->with('toast', __('content_admin.saved'));
    }

    public function edit(string $id): View
    {
        $resource = self::resource();

        return $this->form($resource, $this->find($resource, $id));
    }

    public function update(ContentRequest $request, string $id, SaveContent $save): RedirectResponse
    {
        $resource = self::resource();
        /** @var User $actor */
        $actor = $request->user();
        $save->handle($resource, $this->find($resource, $id), $request->validated(), $actor);

        return back()->with('toast', __('content_admin.saved'));
    }

    public function transition(Request $request, string $id, ContentWorkflow $workflow): RedirectResponse
    {
        $resource = self::resource();
        $transition = (string) $request->route('transition');
        /** @var User $actor */
        $actor = $request->user();
        $to = $workflow->apply($transition, $this->find($resource, $id), $actor);

        $message = $to === ContentStatus::Scheduled ? 'scheduled' : $transition;

        return back()->with('toast', __('content_admin.transitioned.'.$message));
    }

    public function destroy(Request $request, string $id, AuditLogger $audit): RedirectResponse
    {
        $resource = self::resource();
        $def = ContentResourceRegistry::get($resource);
        $model = $this->find($resource, $id);

        abort_if(($def['system_guard'] ?? false) && (bool) $model->getAttribute('is_system'), 403, __('content_admin.system_record'));

        $snapshot = ['resource' => $resource, 'id' => $model->getKey(), 'title' => (string) $model->getAttribute($def['title'])];
        $model->delete();
        ContentCache::flush();
        /** @var User $actor */
        $actor = $request->user();
        $audit->record(AuditAction::ContentDeleted, null, ['before' => $snapshot], null, $actor);

        return to_route(ContentResourceRegistry::routeName($resource).'.index')->with('toast', __('content_admin.deleted'));
    }

    /** Klavyeyle erişilebilir sıralama: komşu kayıtla `sort` değiş tokuşu (NFR-A11Y-001). */
    public function reorder(Request $request): RedirectResponse
    {
        $resource = self::resource();
        $def = ContentResourceRegistry::get($resource);
        abort_unless($def['sortable'], 404);

        $model = $this->find($resource, (int) $request->integer('id'));
        $up = $request->input('direction') === 'up';
        $sort = (int) $model->getAttribute('sort');

        /** @var Model|null $neighbor */
        $neighbor = $def['model']::query()
            ->where('sort', $up ? '<' : '>', $sort)
            ->orderBy('sort', $up ? 'desc' : 'asc')
            ->first();

        if ($neighbor !== null) {
            $model->forceFill(['sort' => (int) $neighbor->getAttribute('sort')])->save();
            $neighbor->forceFill(['sort' => $sort])->save();
            ContentCache::flush();
        }

        return back();
    }

    public function preview(string $id, MarkdownRenderer $markdown): View
    {
        $resource = self::resource();
        $def = ContentResourceRegistry::get($resource);
        $model = $this->find($resource, $id);

        $blocks = [];
        foreach ($def['fields'] as $name => $field) {
            if ($field['type'] === 'markdown' && filled($model->getAttribute($name))) {
                $blocks[] = ['label' => $field['label'], 'html' => $markdown->render((string) $model->getAttribute($name))];
            }
        }

        return view('admin.content.preview', ['def' => $def, 'model' => $model, 'blocks' => $blocks, 'title' => (string) $model->getAttribute($def['title'])]);
    }

    /** Kaynak anahtarı rota `defaults('resource')` değerinden (konumsal parametreye güvenilmez). */
    private static function resource(): string
    {
        return (string) request()->route('resource');
    }

    private function find(string $resource, int|string $id): Model
    {
        $def = ContentResourceRegistry::get($resource);

        return $def['model']::query()->findOrFail((int) $id);
    }

    private function form(string $resource, Model $model): View
    {
        $def = ContentResourceRegistry::get($resource);
        $routeName = ContentResourceRegistry::routeName($resource);

        $public = null;
        if (isset($def['public']) && $model->exists && $model->getAttribute('slug') !== null && Route::has($def['public'])) {
            $public = route($def['public'], $model->getAttribute('slug'));
        }

        return view('admin.content.form', [
            'resource' => $resource,
            'def' => $def,
            'model' => $model,
            'routeName' => $routeName,
            'lookups' => $this->lookups($def),
            'media' => Media::query()->latest('id')->limit(300)->get(['id', 'alt_text', 'original_name']),
            'hasSeo' => in_array('seo_title', $model->getFillable(), true),
            'tags' => $model instanceof NewsPost && $model->exists ? $model->tags()->pluck('name')->implode(', ') : '',
            'ruleCodes' => $model instanceof AcademyTopic && $model->exists ? $model->rules()->pluck('code')->implode(', ') : '',
            'publicUrl' => $public,
            'previewUrl' => $model->exists ? URL::temporarySignedRoute($routeName.'.preview', now()->addMinutes(30), ['id' => $model->getKey()]) : null,
        ]);
    }

    /**
     * belongs alanları için seçenek listeleri (id → görünen ad).
     *
     * @param  array<string, mixed>  $def
     * @return array<string, array<int, string>>
     */
    private function lookups(array $def): array
    {
        $lookups = [];
        foreach ($def['fields'] as $name => $field) {
            if ($field['type'] === 'belongs') {
                $display = $field['display'] ?? 'name';
                $lookups[$name] = $field['model']::query()->orderBy($display)->pluck($display, 'id')->map(fn ($v): string => (string) $v)->all();
            }
        }

        return $lookups;
    }
}
