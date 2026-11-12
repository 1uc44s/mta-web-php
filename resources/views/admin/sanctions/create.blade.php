{{-- Yeni ceza — docs/SRS.md FR-MOD-001..003. Oyun içi türler komut kuyruğuna eklenir. --}}
<x-layouts.admin :title="__('moderation.admin.new')">
    <x-admin.header :title="__('moderation.admin.new')" :back="route('admin.sanctions.index')" :back-label="__('moderation.admin.sanctions')" />

    <x-admin.card>
        <form method="POST" action="{{ route('admin.sanctions.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <x-ui.field name="type" :label="__('moderation.admin.type')" required>
                <select id="f-type" name="type" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text" required>
                    @foreach (__('moderation.types') as $value => $label)
                        @if ($value !== 'perm_ban' || $canPerm)<option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>@endif
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field name="duration_minutes" :label="__('moderation.admin.duration')" hint="Susturma, hapis, süreli yasak ve web askısı için zorunlu (1440 = 1 gün)."><x-ui.input name="duration_minutes" type="number" /></x-ui.field>
            <x-ui.field name="user" :label="__('moderation.admin.user')"><x-ui.input name="user" :value="$prefill['user']" /></x-ui.field>
            <x-ui.field name="account" :label="__('moderation.admin.account')"><x-ui.input name="account" :value="$prefill['account']" /></x-ui.field>
            <x-ui.field name="character" :label="__('moderation.admin.character')"><x-ui.input name="character" /></x-ui.field>
            <x-ui.field name="rule_id" :label="__('moderation.admin.rule')">
                <select id="f-rule_id" name="rule_id" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                    <option value="">—</option>
                    @foreach ($rules as $r)<option value="{{ $r->id }}" @selected((string) old('rule_id') === (string) $r->id)>{{ $r->code }} · {{ $r->title }}</option>@endforeach
                </select>
            </x-ui.field>
            <x-ui.field name="public_reason" :label="__('moderation.admin.public_reason')" class="sm:col-span-2" required><x-ui.input name="public_reason" multiline rows="2" required /></x-ui.field>
            <x-ui.field name="internal_note" :label="__('moderation.admin.internal_note')" class="sm:col-span-2"><x-ui.input name="internal_note" multiline rows="2" /></x-ui.field>
            <x-ui.field name="evidence_url" :label="__('moderation.admin.evidence')" class="sm:col-span-2"><x-ui.input name="evidence_url" type="url" /></x-ui.field>
            <label class="flex items-center gap-2 text-sm text-text sm:col-span-2"><input type="checkbox" name="appealable" value="1" @checked(old('appealable', '1'))> {{ __('moderation.admin.appealable') }}</label>
            <div class="sm:col-span-2"><x-ui.button type="submit" variant="danger" icon="gavel">{{ __('moderation.admin.issue') }}</x-ui.button></div>
        </form>
    </x-admin.card>
</x-layouts.admin>
