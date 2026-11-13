<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * KVKK veri dökümü — docs/SRS.md §6.3 (R2, Faz 10), ROUTES §5.8 `panel.account.export`. Yalnızca oturum sahibinin
 * verisi; iç notlar, yetkili kimlikleri, hash/secret alanları (parola, 2FA, IP hash, cihaz seri HMAC, idempotency
 * anahtarları) dışında tutulur. Destek talebinde yalnızca public mesajlar yer alır.
 */
final class ExportPersonalData
{
    public const FORMAT_VERSION = 1;

    public function __construct(private readonly SecurityEventLogger $security) {}

    /**
     * İndirme: döküm + `data_exported` güvenlik olayı.
     *
     * @return array<string, mixed>
     */
    public function download(User $user, Request $request): array
    {
        $data = $this->handle($user);
        $this->security->record($user, 'data_exported', $request);

        return $data;
    }

    /** @return array<string, mixed> */
    public function handle(User $user): array
    {
        $user = $user->fresh() ?? $user; // tüm kolonlar (oturumdaki örnek kısmi olabilir)
        $id = $user->id;

        $ticketIds = DB::table('support_tickets')->where('user_id', $id)->pluck('id');
        $applicationIds = DB::table('applications')->where('user_id', $id)->pluck('id');
        $walletIds = DB::table('wallets')->where('user_id', $id)->pluck('id');
        $accountIds = DB::table('game_account_links')->where('user_id', $id)->pluck('mta_account_id');

        return [
            'format_version' => self::FORMAT_VERSION,
            'generated_at' => now()->toIso8601String(),
            'profile' => [
                'ulid' => $user->ulid,
                'username' => $user->username,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'status' => $user->status->value,
                'locale' => $user->locale,
                'theme' => $user->theme,
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'consents' => $this->rows('consents', ['type', 'version', 'granted', 'ip_masked', 'created_at'], $id),
            'notification_preferences' => $this->rows('user_notification_preferences', ['category', 'channel', 'enabled', 'updated_at'], $id),
            'push_subscriptions' => $this->rows('push_subscriptions', ['user_agent_summary', 'last_used_at', 'created_at'], $id),
            'security_events' => $this->rows('user_security_events', ['event', 'ip_masked', 'user_agent_summary', 'created_at'], $id, 500),
            'rule_acknowledgements' => $this->rows('rule_acknowledgements', ['version', 'acknowledged_at'], $id),
            'academy_progress' => DB::table('academy_progress as p')->join('academy_topics as t', 't.id', '=', 'p.academy_topic_id')
                ->where('p.user_id', $id)->get(['t.title as topic', 'p.completed_at'])->all(),
            'academy_attempts' => DB::table('academy_attempts as a')->join('academy_quizzes as q', 'q.id', '=', 'a.academy_quiz_id')
                ->where('a.user_id', $id)->get(['q.title as quiz', 'a.score_percent', 'a.passed', 'a.created_at'])->all(),
            'support_tickets' => DB::table('support_tickets')->where('user_id', $id)
                ->get(['ulid', 'number', 'subject', 'status', 'priority', 'created_at', 'resolved_at', 'closed_at'])->all(),
            'support_messages' => DB::table('support_messages as m')->join('support_tickets as t', 't.id', '=', 'm.ticket_id')
                ->whereIn('m.ticket_id', $ticketIds)->where('m.visibility', 'public')
                ->orderBy('m.id')->get(['t.number as ticket', 'm.author_type', 'm.body', 'm.created_at'])->all(),
            'applications' => DB::table('applications as a')->join('application_types as t', 't.id', '=', 'a.application_type_id')
                ->where('a.user_id', $id)->get(['a.ulid', 't.name as type', 'a.status', 'a.submitted_at', 'a.decided_at', 'a.decision_note_public', 'a.created_at'])->all(),
            'application_answers' => DB::table('application_answers as x')->join('applications as a', 'a.id', '=', 'x.application_id')
                ->whereIn('x.application_id', $applicationIds)->get(['a.ulid as application', 'x.question_label', 'x.answer'])->all(),
            'sanctions' => DB::table('sanctions')->where('user_id', $id)
                ->get(['ulid', 'type', 'public_reason', 'starts_at', 'ends_at', 'status', 'appealable', 'revoked_at', 'created_at'])->all(),
            'sanction_appeals' => DB::table('sanction_appeals')->where('user_id', $id)
                ->get(['ulid', 'body', 'status', 'decision_note_public', 'decided_at', 'created_at'])->all(),
            'wallets' => DB::table('wallets as w')->join('wallet_types as t', 't.id', '=', 'w.wallet_type_id')
                ->where('w.user_id', $id)->get(['t.key as type', 'w.balance', 'w.status', 'w.created_at'])->all(),
            'wallet_transactions' => DB::table('wallet_transactions')->whereIn('wallet_id', $walletIds)->orderBy('id')
                ->get(['ulid', 'direction', 'amount', 'balance_after', 'reason', 'description', 'created_at'])->all(),
            'payment_orders' => DB::table('payment_orders')->where('user_id', $id)
                ->get(['ulid', 'order_number', 'provider', 'amount_minor', 'currency', 'credit_amount', 'status', 'refunded_minor', 'paid_at', 'created_at'])->all(),
            'game_account_links' => DB::table('game_account_links as l')->join('mta_accounts as a', 'a.id', '=', 'l.mta_account_id')
                ->where('l.user_id', $id)->get(['a.account_name', 'l.status', 'l.verification_method', 'l.linked_at', 'l.unlinked_at'])->all(),
            'characters' => DB::table('characters')->whereIn('mta_account_id', $accountIds)
                ->get(['ulid', 'name', 'status', 'level', 'playtime_minutes', 'last_seen_at', 'created_in_game_at'])->all(),
            'notifications' => DB::table('notifications')->where(['notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $id])
                ->orderByDesc('created_at')->limit(500)->get(['data', 'read_at', 'created_at'])
                ->map(fn ($n): array => ['data' => json_decode((string) $n->data, true), 'read_at' => $n->read_at, 'created_at' => $n->created_at])->all(),
        ];
    }

    /**
     * @param  non-empty-list<string>  $columns  son kolon sıralama anahtarıdır
     * @return list<object>
     */
    private function rows(string $table, array $columns, int $userId, int $limit = 1000): array
    {
        return DB::table($table)->where('user_id', $userId)->orderByDesc(end($columns))->limit($limit)->get($columns)->all();
    }
}
