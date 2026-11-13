{{-- Ayarlar — docs/ROUTES.md §5.1, SRS FR-UCP-004, DATABASE §3.11. --}}
<x-layouts.panel title="Ayarlar">
    <div class="mx-auto max-w-3xl space-y-8">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">Ayarlar</h1>

        <section aria-labelledby="h-look" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-look" class="font-display text-h4 font-semibold text-text">Görünüm</h2>
            <form method="POST" action="{{ route('panel.settings.preferences') }}" class="mt-4 space-y-4">
                @csrf
                <fieldset>
                    <legend class="text-sm font-medium text-text">Tema</legend>
                    <div class="mt-2 flex flex-wrap gap-4">
                        @foreach (['system' => 'Sistem', 'dark' => 'Koyu', 'light' => 'Açık'] as $value => $label)
                            <label class="flex items-center gap-2 text-text"><input type="radio" name="theme" value="{{ $value }}" @checked($user->theme === $value) class="size-4 accent-[var(--color-accent)]">{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <input type="hidden" name="reduced_effects" value="0">
                <label class="flex items-center gap-2 text-text"><input type="checkbox" name="reduced_effects" value="1" @checked($user->reduced_effects) class="size-4 accent-[var(--color-accent)]">Düşük efekt modu (animasyonları azaltır)</label>
                <x-ui.button type="submit" size="sm">Kaydet</x-ui.button>
            </form>
        </section>

        <section aria-labelledby="h-notif" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-notif" class="font-display text-h4 font-semibold text-text">Bildirim tercihleri</h2>
            <p class="mt-1 text-sm text-text-muted">Güvenlik e-postaları (parola, e-posta değişikliği, doğrulama) her zaman gönderilir. Panel bildirimleri kapatılamaz.</p>
            <form method="POST" action="{{ route('panel.settings.notifications') }}" class="mt-4 space-y-4">
                @csrf
                @foreach (\App\Services\Notifications\NotificationPreferences::MATRIX as $category => $channels)
                    @php($visible = array_values(array_filter($channels, fn (string $c): bool => $c !== 'push' || $pushAvailable)))
                    @foreach (array_diff($channels, $visible) as $hidden)
                        {{-- Push kapalıyken mevcut tercih korunur (işaretsiz kutu = kapalı sayılır). --}}
                        @if ($prefs[$category][$hidden] ?? true)<input type="hidden" name="prefs[{{ $category }}][{{ $hidden }}]" value="1">@endif
                    @endforeach
                    @if ($visible !== [])
                        <fieldset>
                            <legend class="text-sm font-medium text-text">{{ __('notifications.categories.'.$category) }}</legend>
                            <div class="mt-1.5 flex flex-wrap gap-x-6 gap-y-2">
                                @foreach ($visible as $channel)
                                    <label class="flex items-center gap-2 text-sm text-text-muted">
                                        <input type="checkbox" name="prefs[{{ $category }}][{{ $channel }}]" value="1" @checked($prefs[$category][$channel] ?? true) class="size-4 accent-[var(--color-accent)]">{{ __('notifications.channels.'.$channel) }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                @endforeach
                <x-ui.button type="submit" size="sm">Kaydet</x-ui.button>
            </form>
        </section>

        @if ($pushAvailable)
            <section aria-labelledby="h-push" class="rounded-lg border border-border bg-surface-1 p-6" data-push-control
                     data-msg-on="{{ __('notifications.push.on') }}" data-msg-off="{{ __('notifications.push.off') }}" data-msg-denied="{{ __('notifications.push.denied') }}"
                     data-msg-unsupported="{{ __('notifications.push.unsupported') }}" data-msg-error="{{ __('notifications.push.error') }}">
                <h2 id="h-push" class="font-display text-h4 font-semibold text-text">{{ __('notifications.push.title') }}</h2>
                <p class="mt-1 text-sm text-text-muted">{{ __('notifications.push.intro') }}</p>
                <p class="mt-3 text-sm text-text" data-push-status role="status" aria-live="polite"></p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-ui.button type="button" size="sm" icon="bell-ring" data-push-enable hidden>{{ __('notifications.push.enable') }}</x-ui.button>
                    <x-ui.button type="button" size="sm" variant="secondary" icon="bell-off" data-push-disable hidden>{{ __('notifications.push.disable') }}</x-ui.button>
                </div>
            </section>
        @endif
    </div>
</x-layouts.panel>
