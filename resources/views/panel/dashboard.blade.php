{{-- Faz 3 panel iskeleti. Tam panel düzeni (sidebar, kartlar) Faz 6'da (docs/DESIGN.md §7.2). --}}
<x-layouts.panel :title="'Genel bakış'">
    <section class="mx-auto max-w-5xl">
        <x-brand.eyebrow>Oyuncu paneli</x-brand.eyebrow>
        <h1 class="mt-4 font-display text-h1 font-bold font-expanded text-text">Hoş geldin, {{ $user->username }}</h1>

        @unless ($user->hasVerifiedEmail())
            <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-md border border-amber/40 bg-amber-soft px-5 py-4">
                <p class="flex items-start gap-2 text-sm text-text">
                    <x-lucide-mail-warning class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />
                    {{ __('auth.ui.verify.text') }}
                </p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="secondary">{{ __('auth.ui.verify.resend') }}</x-ui.button>
                </form>
            </div>
        @endunless

        @if ($rulesPending)
            {{-- docs/CMS.md §5.3: engellemeyen kural onayı bandı --}}
            <div role="status" class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-md border border-accent/40 bg-accent-soft px-5 py-4">
                <div class="text-sm text-text">
                    <p class="flex items-center gap-2 font-medium"><x-lucide-scale class="size-4 text-accent" aria-hidden="true" />Kurallar güncellendi · sürüm {{ $rulesVersion }}</p>
                    @if ($rulesNote)<p class="mt-1 text-text-muted">{{ $rulesNote }}</p>@endif
                    <a href="{{ route('public.rules') }}" class="mt-1 inline-block text-accent hover:underline">Kuralları oku</a>
                </div>
                <form method="POST" action="{{ route('panel.rules.acknowledge') }}">@csrf
                    <x-ui.button type="submit" size="sm" icon="check">Okudum, onaylıyorum</x-ui.button>
                </form>
            </div>
        @endif

        @if ($announcements->isNotEmpty())
            <section aria-labelledby="duyurular" class="mt-8">
                <h2 id="duyurular" class="font-display text-h4 font-semibold text-text">Duyurular</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($announcements as $a)
                        <li @class(['rounded-md border px-5 py-4', 'border-danger/50' => $a->priority === 'critical', 'border-amber/40' => $a->priority === 'important', 'border-border' => $a->priority === 'info', 'opacity-70' => $a->is_read])>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-text">{{ $a->title }}</p>
                                    <x-ui.prose class="mt-1 text-sm" :html="$a->getAttribute('html')" />
                                </div>
                                @unless ($a->is_read)
                                    <form method="POST" action="{{ route('panel.announcements.read', $a) }}">@csrf
                                        <button type="submit" class="text-xs text-accent hover:underline">Okundu işaretle</button>
                                    </form>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="mt-8 max-w-xl text-text-muted">Panelin tamamı (oyun hesabı bağlama, karakterler, destek, bildirimler) sonraki fazlarda bu alana eklenecek.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" icon="graduation-cap" :href="route('panel.academy')">Akademi ilerlemem</x-ui.button>
            <x-ui.button variant="secondary" icon="shield-check" :href="route('panel.security.two-factor')">{{ __('auth.ui.two_factor_setup.title') }}</x-ui.button>
            @if ($user->isStaff())
                <x-ui.button icon="shield" :href="route('admin.dashboard')">{{ __('admin.title') }}</x-ui.button>
            @endif
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <x-ui.button type="submit" variant="secondary" icon="log-out">Çıkış yap</x-ui.button>
        </form>
    </section>
</x-layouts.panel>
