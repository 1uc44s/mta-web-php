<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use App\Models\RuleCategory;
use App\Services\Content\MarkdownRenderer;
use App\Services\Settings\SettingsRepository;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-010 */
final class RulesController extends Controller
{
    public function __invoke(MarkdownRenderer $markdown, SettingsRepository $settings): View
    {
        $categories = RuleCategory::query()->published()
            ->with(['rules' => fn ($q) => $q->published()->ordered()])
            ->ordered()
            ->get()
            ->filter(fn (RuleCategory $category): bool => $category->rules->isNotEmpty())
            ->values();

        $rendered = $categories->flatMap(fn (RuleCategory $c) => $c->rules)
            ->mapWithKeys(fn (Rule $rule): array => [$rule->id => $markdown->render($rule->body)]);

        $lastUpdated = $categories->flatMap(fn (RuleCategory $c) => $c->rules)->max('updated_at');

        return view('public.rules', [
            'categories' => $categories,
            'rendered' => $rendered,
            'lastUpdated' => $lastUpdated,
            'version' => $settings->get('rules.current_version'),
        ]);
    }
}
