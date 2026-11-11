<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Support\IpMasker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;

/** docs/SRS.md FR-PUB-013: honeypot + minimum doldurma süresi + throttle:contact. */
final class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact', ['formToken' => Crypt::encryptString((string) now()->getTimestamp())]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        // Bot sinyali varsa sessizce başarılı görün; kayıt oluşturma (bota geri bildirim verme).
        if (! $request->looksLikeBot()) {
            ContactMessage::query()->create([
                ...$request->safe()->only(['name', 'email', 'subject', 'body']),
                'ip_hash' => IpMasker::hash($request->ip()),
                'status' => 'new',
            ]);
        }

        return to_route('public.contact')->with('toast', __('public.contact.sent'))->setStatusCode(303);
    }
}
