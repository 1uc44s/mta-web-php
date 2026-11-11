<x-layouts.auth :title="$open ? __('auth.ui.register.title') : __('auth.ui.register.closed_title')">
    @if (! $open)
        <p class="text-text-muted">{{ __('auth.ui.register.closed_text') }}</p>
        <x-ui.button :href="route('public.news.index')" variant="secondary" class="mt-6">{{ __('public.pages.news.eyebrow') }}</x-ui.button>
    @else
        <x-slot:intro>{{ __('auth.ui.register.intro') }}</x-slot:intro>
        <form method="POST" action="{{ route('register.store') }}" class="space-y-5" novalidate>
            @csrf
            <div class="hidden" aria-hidden="true"><label for="f-website">Web</label><input id="f-website" name="website" tabindex="-1" autocomplete="off"></div>
            <x-ui.field name="username" :label="__('auth.ui.register.username')" :hint="__('auth.ui.register.username_hint')" required>
                <x-ui.input name="username" autocomplete="username" hint required minlength="3" maxlength="20" pattern="[A-Za-z0-9_.]+" autofocus />
            </x-ui.field>
            <x-ui.field name="email" :label="__('auth.ui.register.email')" required>
                <x-ui.input name="email" type="email" autocomplete="email" required maxlength="254" />
            </x-ui.field>
            <x-ui.field name="password" :label="__('auth.ui.register.password')" :hint="__('auth.ui.register.password_hint')" required>
                <x-ui.input name="password" type="password" autocomplete="new-password" hint required minlength="10" maxlength="128" />
            </x-ui.field>
            <x-ui.field name="password_confirmation" :label="__('auth.ui.register.password_confirmation')" required>
                <x-ui.input name="password_confirmation" type="password" autocomplete="new-password" required />
            </x-ui.field>
            <div class="space-y-3 rounded-sm border border-border bg-surface-1 p-4">
                @foreach (['terms' => 'public.legal.terms', 'kvkk' => 'public.legal.kvkk'] as $field => $route)
                    <div>
                        <label class="flex cursor-pointer items-start gap-3 text-sm text-text-muted">
                            <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 size-4 shrink-0 accent-[var(--c-accent)]" @checked(old($field)) @error($field) aria-invalid="true" aria-describedby="f-{{ $field }}-error" @enderror>
                            <span><a href="{{ route($route) }}" target="_blank" class="text-accent underline underline-offset-2">{{ __('auth.ui.register.'.$field.'_link') }}</a> {{ trim(__('auth.ui.register.'.$field, ['link' => ''])) }}</span>
                        </label>
                        @error($field)<p id="f-{{ $field }}-error" class="mt-1.5 pl-7 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.register.submit') }}</x-ui.button>
        </form>
        <p class="mt-8 border-t border-border pt-6 text-center text-sm text-text-muted">
            {{ __('auth.ui.register.have_account') }} <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">{{ __('auth.ui.register.login') }}</a>
        </p>
    @endif
</x-layouts.auth>
