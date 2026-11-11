<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\BumpRulesVersion;
use App\Actions\Content\ManagePageSections;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageSectionRequest;
use App\Http\Requests\Admin\RulesVersionRequest;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageSection;
use App\Models\Rule;
use App\Models\RuleRevision;
use App\Models\User;
use App\Services\Content\SectionRegistry;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sayfa bölüm düzenleyici, sayfa/kural revizyonları ve kural sürümü — docs/ROUTES.md §6.8, CMS §4, §5.3.
 */
final class PageSectionController extends Controller
{
    public function __construct(private readonly ManagePageSections $sections) {}

    public function index(Page $page): View
    {
        return view('admin.content.sections', [
            'page' => $page,
            'sections' => $page->sections()->orderBy('sort')->get(),
            'types' => SectionRegistry::LABELS,
        ]);
    }

    public function store(PageSectionRequest $request, Page $page): RedirectResponse
    {
        $section = $this->sections->add($page, (string) $request->validated('type'));

        return to_route('admin.content.pages.sections', $page)->withFragment('bolum-'.$section->id)->with('toast', __('content_admin.saved'));
    }

    public function update(PageSectionRequest $request, Page $page, PageSection $section): RedirectResponse
    {
        abort_unless($section->page_id === $page->id, 404);
        $this->sections->update($section, $request->sectionData(), (bool) $request->validated('is_visible'));

        return back()->withFragment('bolum-'.$section->id)->with('toast', __('content_admin.saved'));
    }

    public function destroy(Page $page, PageSection $section): RedirectResponse
    {
        abort_unless($section->page_id === $page->id, 404);
        $this->sections->delete($section);

        return back()->with('toast', __('content_admin.deleted'));
    }

    public function move(Request $request, Page $page, PageSection $section): RedirectResponse
    {
        abort_unless($section->page_id === $page->id, 404);
        $this->sections->move($page, $section, $request->input('direction') === 'up');

        return back()->withFragment('bolum-'.$section->id);
    }

    public function pageRevisions(Page $page): View
    {
        return view('admin.content.revisions', [
            'title' => $page->title,
            'back' => route('admin.content.pages.edit', ['id' => $page->id]),
            'revisions' => PageRevision::query()->where('page_id', $page->id)->with('author:id,username')->latest('id')->limit(50)->get()
                ->map(fn (PageRevision $r): array => ['id' => $r->id, 'at' => $r->created_at, 'author' => $r->author?->username, 'note' => $r->note,
                    'body' => (string) ($r->snapshot['page']['body'] ?? ''), 'restore' => route('admin.content.pages.revisions.restore', [$page, $r->id])]),
            'current' => (string) $page->body,
        ]);
    }

    public function restorePage(Request $request, Page $page, string $revision): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->sections->restore($page, (int) $revision, $actor);

        return to_route('admin.content.pages.edit', ['id' => $page->id])->with('toast', __('content_admin.saved'));
    }

    public function ruleRevisions(Rule $rule): View
    {
        return view('admin.content.revisions', [
            'title' => $rule->code.' · '.$rule->title,
            'back' => route('admin.content.rules.edit', ['id' => $rule->id]),
            'revisions' => RuleRevision::query()->where('rule_id', $rule->id)->with('author:id,username')->latest('id')->limit(50)->get()
                ->map(fn (RuleRevision $r): array => ['id' => $r->id, 'at' => $r->created_at, 'author' => $r->author?->username, 'note' => $r->change_note, 'body' => $r->body, 'restore' => null]),
            'current' => $rule->body,
        ]);
    }

    public function rulesVersion(SettingsRepository $settings): View
    {
        return view('admin.content.rules-version', [
            'current' => (string) $settings->get('rules.current_version'),
            'note' => (string) $settings->get('rules.change_note'),
        ]);
    }

    public function bumpRulesVersion(RulesVersionRequest $request, BumpRulesVersion $bump): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $bump->handle($actor, (string) $request->validated('version'), (string) $request->validated('note'));

        return back()->with('toast', __('content_admin.rules_version.done', ['version' => $request->validated('version')]));
    }
}
