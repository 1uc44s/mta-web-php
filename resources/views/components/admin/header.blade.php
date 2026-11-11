@props(['title', 'description' => null, 'back' => null, 'backLabel' => null])
{{-- ACP sayfa başlığı: başlık + açıklama + sağda birincil eylemler (docs/DESIGN.md §7.3). --}}
<div {{ $attributes->class(['mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs text-text-muted hover:text-accent">
                <x-lucide-arrow-left class="size-3.5" aria-hidden="true" />{{ $backLabel ?? 'Geri' }}
            </a>
        @endif
        <h1 class="font-display text-2xl font-bold font-expanded text-text">{{ $title }}</h1>
        @if ($description)<p class="mt-1 max-w-2xl text-text-muted">{{ $description }}</p>@endif
    </div>
    @if (isset($actions))<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endif
</div>
