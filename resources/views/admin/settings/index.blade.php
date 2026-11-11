<x-layouts.admin :title="__('admin.settings.title')">
    <x-admin.header :title="__('admin.settings.title')" :description="__('admin.settings.intro')" />
    <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($groups as $group => $meta)
            <li>
                <a href="{{ route('admin.settings.edit', $group) }}" class="flex h-full items-center justify-between gap-3 rounded-lg border border-border bg-surface-1 p-4 hover:border-accent">
                    <span>
                        <span class="block font-medium text-text">{{ __('admin.settings.groups.'.$group) }}</span>
                        <span class="font-mono text-xs text-text-subtle">{{ $group }} · {{ $meta['count'] }}</span>
                    </span>
                    @if ($meta['super'])<x-ui.badge tone="amber" icon="lock">{{ __('admin.settings.super_only') }}</x-ui.badge>@endif
                </a>
            </li>
        @endforeach
    </ul>
</x-layouts.admin>
