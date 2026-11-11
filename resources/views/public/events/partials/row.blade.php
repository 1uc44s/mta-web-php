{{-- Etkinlik satırı: tarih bloğu (mono) + başlık + konum. --}}
@php($tz = config('roleplay.display_timezone'))
<li class="group relative flex items-center gap-5 px-5 py-4 hover:bg-surface-2">
    <time datetime="{{ $event->starts_at->toIso8601String() }}" class="grid w-14 shrink-0 place-items-center rounded-md border border-border bg-surface-2 py-2 text-center">
        <span class="font-mono text-xl leading-none font-semibold text-text">{{ $event->starts_at->timezone($tz)->format('d') }}</span>
        <span class="mt-1 text-[0.65rem] tracking-widest text-text-subtle uppercase">{{ $event->starts_at->timezone($tz)->locale('tr')->translatedFormat('M') }}</span>
    </time>
    <div class="min-w-0 flex-1">
        <a href="{{ route('public.events.show', $event->slug) }}" class="font-medium text-text after:absolute after:inset-0">{{ $event->title }}</a>
        <p class="mt-0.5 truncate text-sm text-text-muted">{{ $event->summary }}</p>
    </div>
    <span class="hidden font-mono text-xs text-text-subtle sm:block">{{ $event->starts_at->timezone($tz)->format('H:i') }}</span>
    <x-lucide-chevron-right class="size-4 text-text-subtle group-hover:text-accent" aria-hidden="true" />
</li>
