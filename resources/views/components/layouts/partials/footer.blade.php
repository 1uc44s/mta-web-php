{{-- Footer (docs/DESIGN.md §7.1): marka, keşfet, topluluk, yasal + feragat. --}}
@php($controller = $site['site.data_controller'] ?? null)
<footer class="relative isolate mt-24 overflow-hidden border-t border-border bg-surface-1">
    <x-brand.meridian-globe class="pointer-events-none absolute -bottom-96 -left-64 -z-10 size-[44rem] text-grid-line" />
    <div class="mx-auto grid max-w-[1240px] gap-10 px-4 py-14 sm:px-8 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div class="max-w-sm">
            <x-brand.logo />
            <p class="mt-4 text-sm text-text-muted">{{ $site['site.description'] ?? '' }}</p>
            @if (! empty($navigation['social']))
                <ul class="mt-5 flex flex-wrap gap-2">
                    @foreach ($navigation['social'] as $link)
                        <li>
                            <a href="{{ \App\Support\Url::safe($link['url']) }}" target="_blank" rel="noopener"
                               class="inline-flex min-h-9 items-center rounded-pill border border-border px-3 text-xs text-text-muted hover:border-accent hover:text-accent">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach (['footer_quick' => 'quick', 'footer_community' => 'community', 'footer_legal' => 'legal'] as $location => $label)
            @if (! empty($navigation[$location]))
                <nav aria-labelledby="footer-{{ $label }}">
                    <h2 id="footer-{{ $label }}" class="eyebrow">{{ __('public.footer.'.$label) }}</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($navigation[$location] as $item)
                            <li>
                                <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                   class="text-sm text-text-muted hover:text-text">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        @endforeach
    </div>

    <div class="border-t border-border">
        <div class="mx-auto flex max-w-[1240px] flex-col gap-3 px-4 py-6 text-xs text-text-subtle sm:px-8 md:flex-row md:items-start md:justify-between">
            <p>{{ __('public.footer.rights', ['year' => now()->year, 'name' => config('app.name')]) }}
                @if (is_array($controller) && ! empty($controller['name']))
                    · {{ __('public.footer.data_controller') }}: {{ $controller['name'] }}
                @endif
            </p>
            <p class="max-w-xl md:text-right">{{ __('public.footer.disclaimer') }}</p>
        </div>
    </div>
</footer>
