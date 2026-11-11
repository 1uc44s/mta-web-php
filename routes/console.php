<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
 * Zamanlanmış görevler (docs/ARCHITECTURE.md). Sunucuda: `* * * * * php artisan schedule:run`.
 */

Schedule::command('mrp:expire-roles')->hourly()->withoutOverlapping();
Schedule::command('audit:verify')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('model:prune')->daily();
