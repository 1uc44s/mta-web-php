<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\DeleteMedia;
use App\Actions\Content\StoreMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUpdateRequest;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Medya kütüphanesi — docs/ROUTES.md §6.8, CMS §3.3. */
final class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.media.index', [
            'q' => $q,
            'items' => Media::query()
                ->when($q !== '', function ($b) use ($q): void {
                    $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
                    $b->where(fn ($w) => $w->where('alt_text', 'like', $like)->orWhere('original_name', 'like', $like));
                })
                ->latest('id')->paginate(36)->withQueryString(),
        ]);
    }

    public function store(MediaUploadRequest $request, StoreMedia $store): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $store->handle($request->file('file'), (string) ($data['alt_text'] ?? ''), (bool) $data['is_decorative'], $actor);

        return back()->with('toast', __('content_admin.saved'));
    }

    public function update(MediaUpdateRequest $request, Media $media): RedirectResponse
    {
        $data = $request->validated();
        $media->update(['is_decorative' => (bool) $data['is_decorative'], 'alt_text' => $data['is_decorative'] ? null : $data['alt_text']]);

        return back()->with('toast', __('content_admin.saved'));
    }

    public function destroy(Media $media, DeleteMedia $delete): RedirectResponse
    {
        abort_unless($delete->handle($media), 409, __('content_admin.errors.media_in_use'));

        return back()->with('toast', __('content_admin.deleted'));
    }
}
