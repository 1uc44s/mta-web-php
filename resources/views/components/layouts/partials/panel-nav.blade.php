{{-- Panel sidebar içeriği (masaüstü aside + mobil drawer). docs/DESIGN.md §7.2 --}}
<div class="flex h-16 shrink-0 items-center border-b border-border px-5">
    <a href="{{ route('public.home') }}" class="rounded-sm" aria-label="{{ config('app.name') }} — ana sayfa"><x-brand.logo /></a>
</div>
<div class="flex items-center gap-3 border-b border-border px-5 py-4">
    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-accent-soft font-display font-bold text-accent" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $panelUser?->username, 0, 1)) }}</span>
    <div class="min-w-0">
        <p class="truncate font-medium text-text">{{ $panelUser?->username }}</p>
        <p class="text-xs text-text-subtle">{{ $panelUser?->hasVerifiedEmail() ? 'E-posta doğrulandı' : 'E-posta doğrulanmadı' }}</p>
    </div>
</div>
<nav aria-label="Panel menüsü" class="flex-1 overflow-y-auto px-3 py-4">
    <ul class="space-y-0.5">
        @foreach ($panelMenu as $item)
            <li>
                <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                   class="flex min-h-11 items-center gap-3 rounded-md px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text aria-[current=page]:bg-accent-soft aria-[current=page]:font-medium aria-[current=page]:text-accent">
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4 shrink-0" aria-hidden="true" />{{ $item['label'] }}
                </a>
            </li>
        @endforeach
        @if ($panelUser?->isStaff())
            <li class="mt-3 border-t border-border pt-3">
                <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-3 rounded-md px-3 text-sm text-amber hover:bg-surface-2">
                    <x-lucide-shield class="size-4 shrink-0" aria-hidden="true" />Yönetim paneli
                </a>
            </li>
        @endif
    </ul>
</nav>
<div class="shrink-0 border-t border-border px-5 py-4">
    <x-brand.status-pill :status="$serverStatus" />
</div>
