{{-- Footer (v2, docs/DESIGN.md §7.1): dev marka adı, marka + slogan, keşfet, topluluk, yasal + feragat. --}}
@php($controller = $site['site.data_controller'] ?? null)
<footer class="relative isolate mt-16 overflow-hidden">
    <p aria-hidden="true" class="ghost-word mx-auto max-w-[1240px] overflow-hidden px-4 text-[clamp(4rem,15vw,14rem)] sm:px-8">{{ \App\Support\Brand::shortName() }}</p>
    <div class="mx-auto max-w-[1240px] px-4 pb-8 sm:px-8">
        <div class="glass grid gap-10 rounded-[var(--radius-xl)] p-8 sm:p-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div class="max-w-sm">
                <x-brand.logo />
                @if (\App\Support\Brand::tagline())
                    <p class="mt-4 font-display font-black font-wide text-text">{{ \App\Support\Brand::tagline() }}</p>
                @endif
                <p class="mt-2 text-sm text-text-muted">{{ $site['site.description'] ?? '' }}</p>
                @if (! empty($navigation['social']))
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($navigation['social'] as $link)
                            <li>
                                <a href="{{ \App\Support\Url::safe($link['url']) }}" target="_blank" rel="noopener"
                                   class="inline-flex min-h-9 items-center rounded-pill border border-border px-3.5 text-xs font-semibold text-text-muted transition-colors hover:border-accent hover:text-accent">
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
                        <h2 id="footer-{{ $label }}" class="eyebrow text-text">{{ __('public.footer.'.$label) }}</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($navigation[$location] as $item)
                                <li>
                                    <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                                       class="text-sm text-text-muted transition-colors hover:text-accent">{{ $item['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endforeach
        </div>

        <div class="flex flex-col gap-3 px-2 pt-6 text-xs text-text-subtle md:flex-row md:items-start md:justify-between">
            <p>{{ __('public.footer.rights', ['year' => now()->year, 'name' => config('app.name')]) }}
                @if (is_array($controller) && ! empty($controller['name']))
                    · {{ __('public.footer.data_controller') }}: {{ $controller['name'] }}
                @endif
            </p>
            <p class="max-w-xl md:text-right">{{ __('public.footer.disclaimer') }}</p>
        </div>
    </div>
</footer>