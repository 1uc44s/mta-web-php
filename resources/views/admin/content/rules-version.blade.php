{{-- Kural seti sürümü — docs/CMS.md §5.3. Sürüm artınca oyunculardan yeniden onay istenir. --}}
<x-layouts.admin :title="__('content_admin.rules_version.title')">
    <x-admin.header :title="__('content_admin.rules_version.title')" :description="__('content_admin.rules_version.hint')" :back="route('admin.content.rules.index')" :back-label="'Kurallar'" />
    <x-admin.card class="max-w-2xl">
        <p class="mb-1 font-mono text-text">{{ __('content_admin.rules_version.current', ['version' => $current]) }}</p>
        @if ($note)<p class="mb-5 text-text-muted">{{ $note }}</p>@endif
        <form method="POST" action="{{ route('admin.content.rules.bump-version') }}" class="space-y-4">
            @csrf
            <x-ui.field name="version" :label="__('content_admin.rules_version.new')" required><x-ui.input name="version" maxlength="32" required class="font-mono" /></x-ui.field>
            <x-ui.field name="note" :label="__('content_admin.rules_version.note')" required><x-ui.input name="note" multiline rows="3" maxlength="500" required /></x-ui.field>
            <x-ui.button type="submit" icon="badge-check">{{ __('content_admin.rules_version.submit') }}</x-ui.button>
        </form>
    </x-admin.card>
</x-layouts.admin>
