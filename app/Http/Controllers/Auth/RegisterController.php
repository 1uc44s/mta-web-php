<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Settings\SettingsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Kayıt — docs/ROUTES.md §4, docs/PRD.md D-15. Otomatik giriş yok; yanıt her durumda aynı. */
final class RegisterController extends Controller
{
    public function create(SettingsRepository $settings): View
    {
        return view('auth.register', ['open' => $settings->bool('features.registration')]);
    }

    public function store(RegisterRequest $request, RegisterUser $register, SettingsRepository $settings): RedirectResponse
    {
        abort_unless($settings->bool('features.registration'), 404);

        /** @var array{username: string, email: string, password: string} $data */
        $data = $request->safe()->only(['username', 'email', 'password']);
        $register->handle($data, $request);

        return to_route('register.check-email')->with('registered_email', $data['email'])->setStatusCode(303);
    }

    public function checkEmail(): View
    {
        return view('auth.check-email', ['email' => session('registered_email')]);
    }
}
