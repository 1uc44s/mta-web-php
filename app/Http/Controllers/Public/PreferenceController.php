<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\ThemePreference;
use App\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Tema ve düşük efekt tercihi (docs/DESIGN.md §4, docs/ROUTES.md §3 public.preferences.theme). */
final class PreferenceController extends Controller
{
    private const ONE_YEAR_MINUTES = 525_600;

    public function theme(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', Rule::in(ThemePreference::THEMES)],
            'effects' => ['nullable', Rule::in(ThemePreference::EFFECTS)],
        ]);

        $user = $request->user();
        if ($user !== null) {
            $user->forceFill(array_filter([
                'theme' => $validated['theme'] ?? null,
                'reduced_effects' => isset($validated['effects']) ? $validated['effects'] === 'low' : null,
            ], fn ($v) => $v !== null))->save();
        }

        $response = $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->to(Url::safeRedirect($request->input('return'), route('public.home')))->setStatusCode(303);

        foreach (['theme' => ThemePreference::THEME_COOKIE, 'effects' => ThemePreference::EFFECTS_COOKIE] as $field => $cookie) {
            if (isset($validated[$field])) {
                $response->withCookie(cookie($cookie, $validated[$field], self::ONE_YEAR_MINUTES, httpOnly: false, sameSite: 'lax'));
            }
        }

        return $response;
    }

    /** Çerez onayı (docs/SRS.md FR-PUB-020). Girişli kullanıcılar için consents kaydı Faz 3'te eklenir. */
    public function cookieConsent(Request $request): RedirectResponse|JsonResponse
    {
        $choice = $request->validate(['analytics' => ['required', 'boolean']]);
        $value = $choice['analytics'] ? 'analytics' : 'essential';

        $response = $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->to(Url::safeRedirect($request->input('return'), route('public.home')))->setStatusCode(303);

        return $response->withCookie(cookie('mrp_consent', $value, self::ONE_YEAR_MINUTES, httpOnly: false, sameSite: 'lax'));
    }
}
