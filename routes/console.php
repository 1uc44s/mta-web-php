<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
 * Zamanlanmış görevler (docs/ARCHITECTURE.md). Sunucuda: `* * * * * php artisan schedule:run`.
 */

Schedule::command('content:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('mrp:expire-roles')->hourly()->withoutOverlapping();
Schedule::command('mrp:maintenance-tick')->hourly()->withoutOverlapping();
Schedule::command('audit:verify')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('model:prune')->daily();
Schedule::command('mrp:health-check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('bridge:maintain')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('bridge:maintain --stats')->dailyAt('00:10')->withoutOverlapping();
