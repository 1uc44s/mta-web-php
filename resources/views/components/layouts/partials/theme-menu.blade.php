{{--
    Tema menüsü (docs/DESIGN.md §4). JS yoksa form POST eder; JS varsa değişiklik anında uygulanır (modules/theme.js).
--}}
@php
    $currentTheme = \App\Support\ThemePreference::theme();
    $currentEffects = \App\Support\ThemePreference::effects();
@endphp
<details data-theme-menu class="group/theme relative">
    <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text [&::-webkit-details-marker]:hidden" aria-label="{{ __('public.theme.label') }}">
        <x-lucide-moon class="size-5" aria-hidden="true" />
    </summary>
    <form method="POST" action="{{ route('public.preferences.theme') }}" data-theme-form
          class="absolute right-0 z-50 mt-2 w-64 rounded-md border border-border-strong bg-surface-1 p-3 shadow-2">
        @csrf
        <input type="hidden" name="return" value="{{ request()->getRequestUri() }}">
        <fieldset>
            <legend class="eyebrow px-1 pb-2">{{ __('public.theme.label') }}</legend>
            <div class="grid grid-cols-3 gap-1">
                @foreach (['dark' => 'moon', 'light' => 'sun', 'system' => 'monitor'] as $value => $icon)
                    <label class="flex cursor-pointer flex-col items-center gap-1 rounded-sm border border-transparent px-2 py-2 text-xs text-text-muted hover:bg-surface-2 has-checked:border-accent has-checked:bg-accent-soft has-checked:text-accent has-focus-visible:ring-2 has-focus-visible:ring-accent">
                        <input type="radio" name="theme" value="{{ $value }}" class="sr-only" @checked($currentTheme === $value)>
                        <x-dynamic-component :component="'lucide-'.$icon" class="size-4" aria-hidden="true" />
                        {{ __('public.theme.'.$value) }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-sm px-1 py-2">
            <input type="hidden" name="effects" value="full">
            <input type="checkbox" name="effects" value="low" class="mt-1 size-4 accent-[var(--c-accent)]" @checked($currentEffects === 'low')>
            <span>
                <span class="block text-sm text-text">{{ __('public.theme.effects') }}</span>
                <span class="block text-xs text-text-subtle">{{ __('public.theme.effects_hint') }}</span>
            </span>
        </label>
        <noscript><button class="mt-2 w-full rounded-sm bg-accent py-2 text-sm text-accent-fg">{{ __('public.theme.save') }}</button></noscript>
    </form>
</details>
