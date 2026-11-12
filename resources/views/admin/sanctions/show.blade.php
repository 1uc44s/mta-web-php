{{-- Ceza detayı (ACP) — iç not, kanıt ve komut sonuçları yalnızca personel. --}}
<x-layouts.admin :title="__('moderation.types.'.$sanction->type)">
    <x-admin.header :title="__('moderation.types.'.$sanction->type)" :description="__('moderation.status.'.$sanction->status)" :back="route('admin.sanctions.index')" :back-label="__('moderation.admin.sanctions')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <x-admin.card>
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-text-subtle">{{ __('moderation.admin.user') }}</dt><dd class="text-text">{{ $sanction->user?->username ?? '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('moderation.admin.account') }}</dt><dd class="text-text">{{ $sanction->account?->account_name ?? '—' }} @if ($sanction->account)<span class="font-mono text-xs text-text-subtle">({{ $sanction->account->mta_account_ref }})</span>@endif</dd></div>
                <div><dt class="text-text-subtle">{{ __('moderation.starts') }}</dt><dd class="text-text">{{ $sanction->starts_at->format('d.m.Y H:i') }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('moderation.ends') }}</dt><dd class="text-text">{{ $sanction->ends_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('moderation.admin.issued_by') }}</dt><dd class="text-text">{{ $sanction->issuer?->username }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('moderation.admin.rule') }}</dt><dd class="text-text">{{ $sanction->rule ? $sanction->rule->code.' · '.$sanction->rule->title : '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-text-subtle">{{ __('moderation.admin.public_reason') }}</dt><dd class="whitespace-pre-line text-text">{{ $sanction->public_reason }}</dd></div>
                <div class="rounded-md border border-dashed border-amber bg-amber/5 p-3 sm:col-span-2"><dt class="text-amber">{{ __('moderation.admin.internal_note') }}</dt><dd class="whitespace-pre-line text-text">{{ $sanction->internal_note ?? '—' }}</dd></div>
                @if ($sanction->evidence_url)
                    <div class="sm:col-span-2"><dt class="text-text-subtle">{{ __('moderation.admin.evidence') }}</dt><dd><a href="{{ $sanction->evidence_url }}" rel="noopener noreferrer" target="_blank" class="break-all text-accent underline">{{ $sanction->evidence_url }}</a></dd></div>
                @endif
                @if ($sanction->revoke_reason)
                    <div class="sm:col-span-2"><dt class="text-text-subtle">{{ __('moderation.admin.revoke') }}</dt><dd class="text-text">{{ $sanction->revoke_reason }}</dd></div>
                @endif
            </dl>
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card :title="__('moderation.admin.command')">
                <ul class="space-y-2 text-sm">
                    @forelse ($commands as $c)
                        <li><span class="font-mono text-xs text-text">{{ $c->type }}</span> · <x-ui.badge :tone="match ($c->status) { 'succeeded' => 'success', 'failed' => 'danger', default => 'neutral' }">{{ __('moderation.admin.command_status.'.$c->status) }}</x-ui.badge>
                            @if (is_array($c->result) && isset($c->result['error']))<span class="text-xs text-danger">{{ $c->result['error'] }}</span>@endif</li>
                    @empty
                        <li class="text-text-muted">—</li>
                    @endforelse
                </ul>
            </x-admin.card>

            @if ($sanction->status === 'active')
                @can('sanctions.revoke')
                    <x-admin.card :title="__('moderation.admin.revoke')">
                        <form method="POST" action="{{ route('admin.sanctions.revoke', $sanction) }}" class="space-y-3">
                            @csrf
                            <x-ui.field name="reason" :label="__('admin.reason')" required><x-ui.input name="reason" multiline rows="2" required /></x-ui.field>
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('moderation.admin.revoke') }}</x-ui.button>
                        </form>
                    </x-admin.card>
                @endcan
            @endif

            @if ($sanction->appeal)
                <x-admin.card :title="__('moderation.admin.appeals')">
                    <p class="text-sm"><x-ui.badge>{{ __('moderation.appeal_statuses.'.$sanction->appeal->status) }}</x-ui.badge></p>
                    @can('sanctions.appeals.review')<a href="{{ route('admin.appeals.show', $sanction->appeal) }}" class="mt-2 inline-block text-sm text-accent underline">{{ __('moderation.admin.decide') }}</a>@endcan
                </x-admin.card>
            @endif
        </div>
    </div>
</x-layouts.admin>
