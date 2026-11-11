<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsRepository;
use Illuminate\Validation\ValidationException;

/**
 * Kural seti sürümünü artırır — docs/CMS.md §5.3. Değişiklik notu zorunludur ve oyunculara gösterilir;
 * oturum açan oyuncular panelde yeniden onay bandı görür.
 */
final class BumpRulesVersion
{
    public function __construct(private readonly SettingsRepository $settings, private readonly AuditLogger $audit) {}

    public function handle(User $actor, string $version, string $note): void
    {
        $current = (string) $this->settings->get('rules.current_version');

        if (version_compare($version, $current, '<=')) {
            throw ValidationException::withMessages(['version' => "Yeni sürüm mevcut sürümden ({$current}) büyük olmalı."]);
        }

        $this->settings->set('rules.current_version', $version, $actor->id);
        $this->settings->set('rules.change_note', $note, $actor->id);

        $this->audit->record(AuditAction::RulesVersionBumped, null, ['before' => $current, 'after' => $version], $note, $actor);
    }
}
