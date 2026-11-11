<?php

declare(strict_types=1);

namespace App\Actions\Panel;

use App\Models\Consent;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\IpMasker;
use Illuminate\Support\Facades\DB;

/**
 * Geçerli kural sürümünü onaylar — docs/CMS.md §5.3: `rule_acknowledgements` + `consents(rules_ack)`. İdempotent.
 */
final class AcknowledgeRules
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function needsAcknowledgement(User $user): bool
    {
        return ! DB::table('rule_acknowledgements')
            ->where(['user_id' => $user->id, 'version' => $this->currentVersion()])
            ->exists();
    }

    public function currentVersion(): string
    {
        return (string) $this->settings->get('rules.current_version');
    }

    public function handle(User $user, ?string $ip): void
    {
        $version = $this->currentVersion();

        DB::transaction(function () use ($user, $version, $ip): void {
            $inserted = DB::table('rule_acknowledgements')->insertOrIgnore([
                'user_id' => $user->id, 'version' => $version, 'acknowledged_at' => now(),
            ]);

            if ($inserted > 0) {
                Consent::query()->create([
                    'user_id' => $user->id, 'type' => 'rules_ack', 'version' => $version, 'granted' => true, 'ip_masked' => IpMasker::mask($ip),
                ]);
            }
        });
    }
}
