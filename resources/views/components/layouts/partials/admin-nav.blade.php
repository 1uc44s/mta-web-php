{{-- ACP sidebar içeriği (masaüstü aside + mobil drawer). docs/DESIGN.md §7.3 --}}
<div class="h-1 shrink-0 bg-amber" aria-hidden="true"></div>
<div class="flex h-14 shrink-0 items-center gap-3 border-b border-border px-5">
    <a href="{{ route('admin.dashboard') }}" class="rounded-sm" aria-label="{{ __('admin.title') }}"><x-brand.logo /></a>
</div>
<p class="px-5 pt-5 pb-2 font-mono text-[11px] font-semibold tracking-[0.18em] text-amber uppercase">{{ __('admin.area') }}</p>
<nav aria-label="{{ __('admin.menu') }}" class="flex-1 overflow-y-auto px-3 pb-6">
    @foreach ($adminMenu as $group)
        <p class="mt-4 px-2 pb-1 text-xs font-medium text-text-subtle">{{ $group['label'] }}</p>
        <ul class="space-y-0.5">
            @foreach ($group['items'] as $item)
                <li>
                    <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                       class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-text-muted hover:bg-surface-2 hover:text-text aria-[current=page]:bg-accent-soft aria-[current=page]:font-medium aria-[current=page]:text-accent">
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4 shrink-0" aria-hidden="true" />
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach
</nav>
<div class="shrink-0 border-t border-border px-5 py-4">
    <p class="truncate text-sm font-medium text-text">{{ $adminUser?->username }}</p>
    <p class="mt-0.5 truncate text-xs text-text-subtle">{{ implode(' · ', array_map(fn ($r) => config('roles.'.$r.'.name', $r), $adminUser?->authz()['roles'] ?? [])) }}</p>
    <a href="{{ route('panel.dashboard') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs text-accent hover:underline">
        <x-lucide-arrow-left class="size-3.5" aria-hidden="true" />{{ __('admin.back_to_panel') }}
    </a>
</div>
