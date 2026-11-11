<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * Public site ve kimlik doğrulama rotaları (docs/ROUTES.md §3–4).
 * Yeni rota önce ROUTES.md'ye eklenir.
 */

Route::view('/', 'public.home')->name('public.home');
