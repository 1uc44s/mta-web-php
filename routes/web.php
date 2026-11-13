<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Public\AcademyController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\EventController;
use App\Http\Controllers\Public\FactionController;
use App\Http\Controllers\Public\FakeCheckoutController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\GameJobController;
use App\Http\Controllers\Public\GameSystemController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ManifestController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PreferenceController;
use App\Http\Controllers\Public\RulesController;
use App\Http\Controllers\Public\SeoController;
use Illuminate\Support\Facades\Route;

/*
 * Public site rotaları (docs/ROUTES.md §3). Yeni rota önce ROUTES.md'ye eklenir.
 */

Route::get('/', HomeController::class)->name('public.home');
Route::get('/about', PageController::class)->defaults('slug', 'about')->name('public.about');

Route::get('/systems', [GameSystemController::class, 'index'])->name('public.systems.index');
Route::get('/systems/{slug}', [GameSystemController::class, 'show'])->name('public.systems.show');
Route::get('/jobs', [GameJobController::class, 'index'])->name('public.jobs.index');
Route::get('/jobs/{slug}', [GameJobController::class, 'show'])->name('public.jobs.show');
Route::get('/factions', [FactionController::class, 'index'])->name('public.factions.index');
Route::get('/factions/{slug}', [FactionController::class, 'show'])->name('public.factions.show');

Route::get('/start', [AcademyController::class, 'start'])->name('public.start');
Route::get('/rules', RulesController::class)->name('public.rules');
Route::get('/academy', [AcademyController::class, 'index'])->name('public.academy.index');
Route::get('/academy/{course}', [AcademyController::class, 'course'])->name('public.academy.course');
Route::get('/academy/{course}/{topic}', [AcademyController::class, 'topic'])->name('public.academy.topic');

Route::get('/news', [NewsController::class, 'index'])->name('public.news.index');
Route::get('/news/category/{category:slug}', [NewsController::class, 'index'])->name('public.news.category');
Route::get('/news/{post}', [NewsController::class, 'show'])->name('public.news.show');
Route::get('/events', [EventController::class, 'index'])->name('public.events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('public.events.show');
Route::get('/faq', FaqController::class)->name('public.faq');

Route::get('/contact', [ContactController::class, 'show'])->name('public.contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('public.contact.store');

// Faz 9 — sahte ödeme ekranı (yalnızca PAYMENTS_PROVIDER=fake, production dışı; controller ayrıca denetler). ROUTES §5.7
Route::middleware(['auth', 'signed', 'feature:payments'])->prefix('payments/fake')->name('payments.fake.')->group(function (): void {
    Route::get('{order}', [FakeCheckoutController::class, 'show'])->where('order', '[0-9a-z]{26}')->name('show');
    Route::post('{order}', [FakeCheckoutController::class, 'complete'])->where('order', '[0-9a-z]{26}')->name('complete');
});

foreach (['privacy', 'kvkk', 'terms', 'cookies', 'refund-policy'] as $legal) {
    Route::get('/'.$legal, PageController::class)->defaults('slug', $legal)->name('public.legal.'.$legal);
}
Route::get('/p/{slug}', PageController::class)->name('public.page');

Route::post('/preferences/theme', [PreferenceController::class, 'theme'])->middleware('throttle:preferences')->name('public.preferences.theme');
Route::post('/cookie-consent', [PreferenceController::class, 'cookieConsent'])->middleware('throttle:preferences')->name('public.cookie-consent');

/*
 * Kimlik doğrulama (docs/ROUTES.md §4). Giriş/çıkış/parola sıfırlama/2FA Fortify'dadır; kayıt ve doğrulama burada (D-15).
 */
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');
    Route::get('/register/check-email', [RegisterController::class, 'checkEmail'])->name('register.check-email');
});

Route::get('/email/verify/{ulid}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:verification')->name('verification.send');
});

Route::get('/manifest.webmanifest', ManifestController::class)->name('pwa.manifest');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('public.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('public.robots');
Route::get('/feed.xml', [SeoController::class, 'feed'])->name('public.feed');
