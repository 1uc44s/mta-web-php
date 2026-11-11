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
            <h2 id="h-notif" class="font-display text-h4 font-semibold text-text">E-posta bildirimleri</h2>
            <p class="mt-1 text-sm text-text-muted">Güvenlik e-postaları (parola, e-posta değişikliği, doğrulama) her zaman gönderilir. Panel bildirimleri kapatılamaz.</p>
            <form method="POST" action="{{ route('panel.settings.notifications') }}" class="mt-4 space-y-3">
                @csrf
                @foreach (['support' => 'Destek talebime yanıt geldiğinde', 'announcements' => 'Duyurular (e-posta kanalı seçildiğinde)'] as $category => $label)
                    <label class="flex items-center gap-2 text-text">
                        <input type="checkbox" name="prefs[{{ $category }}][mail]" value="1" @checked($prefs[$category]['mail'] ?? true) class="size-4 accent-[var(--color-accent)]">{{ $label }}
                    </label>
                @endforeach
                <x-ui.button type="submit" size="sm">Kaydet</x-ui.button>
            </form>
        </section>
    </div>
</x-layouts.panel>
