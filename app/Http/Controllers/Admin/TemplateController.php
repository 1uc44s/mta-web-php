<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\SaveMessageTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Content\MarkdownRenderer;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Messaging\TemplateRenderer;
use App\Services\Messaging\TemplateSyntaxException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Mesaj şablonları — docs/ROUTES.md §6.9, CMS §6. */
final class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => MessageTemplate::query()->orderBy('key')->orderBy('channel')->get(),
        ]);
    }

    public function edit(MessageTemplate $template, TemplateRenderer $renderer, MarkdownRenderer $markdown): View
    {
        $sample = SaveMessageTemplate::sampleContext($template->variables);

        try {
            $preview = [
                'subject' => $renderer->render((string) $template->subject, $sample, $template->variables, 'text'),
                'html' => $markdown->render($renderer->render($template->body, $sample, $template->variables, 'markdown')),
                'error' => null,
            ];
        } catch (TemplateSyntaxException $e) {
            $preview = ['subject' => '', 'html' => null, 'error' => $e->getMessage()];
        }

        return view('admin.templates.edit', [
            'template' => $template,
            'preview' => $preview,
            'filters' => TemplateRenderer::FILTERS,
            'revisions' => $template->revisions()->with('author:id,username')->limit(20)->get(),
        ]);
    }

    public function update(MessageTemplateRequest $request, MessageTemplate $template, SaveMessageTemplate $save): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $save->handle($template, $data['subject'] ?? null, (string) $data['body'], $actor);

        return back()->with('toast', __('content_admin.saved'));
    }

    /** Kaydetmeden canlı önizleme (JSON). Hatalı söz dizimi 422 ile mesaj döner. */
    public function preview(MessageTemplateRequest $request, MessageTemplate $template, TemplateRenderer $renderer, MarkdownRenderer $markdown): JsonResponse
    {
        $data = $request->validated();
        $sample = SaveMessageTemplate::sampleContext($template->variables);

        try {
            return response()->json(['success' => true, 'data' => [
                'subject' => $renderer->render((string) ($data['subject'] ?? ''), $sample, $template->variables, 'text'),
                'html' => $markdown->render($renderer->render((string) $data['body'], $sample, $template->variables, 'markdown'))->toHtml(),
            ]]);
        } catch (TemplateSyntaxException $e) {
            return response()->json(['success' => false, 'error' => ['code' => 'TEMPLATE_SYNTAX', 'message' => $e->getMessage()]], 422);
        }
    }

    /** Yalnızca düzenleyen yetkilinin kendi adresine, kayıtlı (aktif) sürümle (CMS §6.5). */
    public function testSend(Request $request, MessageTemplate $template, MessageDispatcher $messages): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $messages->send($template->key, $actor, SaveMessageTemplate::sampleContext($template->variables));

        return back()->with('toast', 'Test e-postası kendi adresine kuyruğa alındı.');
    }

    public function restore(Request $request, MessageTemplate $template, string $revision, SaveMessageTemplate $save): RedirectResponse
    {
        $rev = $template->revisions()->whereKey((int) $revision)->firstOrFail();
        /** @var User $actor */
        $actor = $request->user();
        $save->handle($template, $rev->subject, $rev->body, $actor);

        return back()->with('toast', "Sürüm {$rev->version} geri yüklendi.");
    }
}
