<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsRepository;
use Illuminate\Validation\ValidationException;

/**
 * Ayar grubunu günceller — docs/SRS.md FR-ADM-009, ADMIN §7. Süper-only anahtarlar yalnızca süper admin; değişen her anahtar audit'e.
 */
final class UpdateSettings
{
    public function __construct(private readonly SettingsRepository $settings, private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $values  Yalnızca bu gruba ait, doğrulanmış anahtarlar */
    public function handle(User $actor, string $group, array $values): int
    {
        $catalog = $this->settings->catalog();
        $changed = 0;

        foreach ($values as $key => $value) {
            $meta = $catalog[$key] ?? null;

            if ($meta === null || $meta['group'] !== $group) {
                throw ValidationException::withMessages([$key => __('admin.settings.errors.unknown')]);
            }

            if (($meta['super'] ?? false) && ! $actor->isSuperAdmin()) {
                throw ValidationException::withMessages([$key => __('admin.staff.errors.super_only')]);
            }

            $old = $this->settings->get($key);

            if ($old === $value) {
                continue;
            }

            $this->settings->set($key, $value, $actor->id);
            $changed++;

            $this->audit->record(AuditAction::SettingsUpdated, null, [
                'key' => $key, 'before' => $old, 'after' => $value, 'super' => (bool) ($meta['super'] ?? false),
            ], null, $actor);
        }

        return $changed;
    }
}
