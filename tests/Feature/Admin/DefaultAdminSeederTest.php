<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DefaultAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/* docs/ADMIN.md §4.4 — yerel varsayılan süper admin. */

uses(RefreshDatabase::class);

it('varsayılan süper admini oluşturur (idempotent); kullanıcı adı ile giriş yapar ve 2FA kurulumuna yönlenir', function (): void {
    (new AccessControlSeeder)->run();
    $this->seed(DefaultAdminSeeder::class);
    $this->seed(DefaultAdminSeeder::class);

    $admin = User::query()->where('email', '1uc44s@git.hub')->sole();
    expect($admin->username)->toBe('1uc44s')->and($admin->isSuperAdmin())->toBeTrue()->and($admin->email_verified_at)->not->toBeNull();

    $this->post('/login', ['login' => '1uc44s', 'password' => 'lucasisthebest'])->assertRedirect();
    $this->assertAuthenticatedAs($admin);
    $this->get('/admin')->assertRedirect(route('panel.security.two-factor'));
});

it('production ortamında çalışmayı reddeder', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    // artisan db:seed production'da onay sorar; seeder doğrudan çağrılır.
    expect(fn () => app()->call([new DefaultAdminSeeder, 'run']))->toThrow(RuntimeException::class);
    expect(User::query()->count())->toBe(0);
});
