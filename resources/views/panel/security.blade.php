{{-- Güvenlik — docs/ROUTES.md §5.8, SRS FR-AUTH-014..020. --}}
<x-layouts.panel title="Güvenlik">
    <div class="mx-auto max-w-4xl space-y-8">
        <header>
            <x-brand.eyebrow>Hesap</x-brand.eyebrow>
            <h1 class="mt-3 font-display text-h2 font-bold font-expanded text-text">Güvenlik</h1>
        </header>

        @if ($user->status->value === 'pending_deletion')
            <div role="alert" class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-danger/40 bg-danger/10 px-5 py-4 text-sm text-text">
                <p>Hesabın {{ $user->deletion_requested_at?->addDays(14)->format('d.m.Y') }} tarihinde kalıcı olarak anonimleştirilecek.</p>
                <form method="POST" action="{{ route('panel.account.delete-cancel') }}">@csrf<x-ui.button type="submit" size="sm" variant="secondary">Silme talebini iptal et</x-ui.button></form>
            </div>
        @endif

        <section aria-labelledby="h-2fa" class="rounded-lg border border-border bg-surface-1 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="h-2fa" class="font-display text-h4 font-semibold text-text">İki adımlı doğrulama</h2>
                    <p class="mt-1 text-sm text-text-muted">{{ $user->hasTwoFactorEnabled() ? 'Etkin. Girişlerde uygulamandaki kod istenir.' : 'Kapalı. Hesabını korumak için etkinleştirmeni öneririz.' }}</p>
                </div>
                <x-ui.button size="sm" variant="secondary" icon="shield-check" :href="route('panel.security.two-factor')">{{ $user->hasTwoFactorEnabled() ? 'Yönet' : 'Etkinleştir' }}</x-ui.button>
            </div>
        </section>

        <section aria-labelledby="h-pw" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-pw" class="font-display text-h4 font-semibold text-text">Parola</h2>
            <p class="mt-1 text-sm text-text-muted">Parolanı değiştirdiğinde diğer tüm cihazlardaki oturumların kapatılır.</p>
            <form method="POST" action="{{ route('panel.security.password') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2"><x-ui.field name="current_password" label="Mevcut parola" required><x-ui.input name="current_password" type="password" autocomplete="current-password" required /></x-ui.field></div>
                <x-ui.field name="password" label="Yeni parola (en az 10 karakter)" required><x-ui.input name="password" type="password" autocomplete="new-password" required /></x-ui.field>
                <x-ui.field name="password_confirmation" label="Yeni parola (tekrar)" required><x-ui.input name="password_confirmation" type="password" autocomplete="new-password" required /></x-ui.field>
                <div class="sm:col-span-2"><x-ui.button type="submit" size="sm" icon="key-round">Parolayı değiştir</x-ui.button></div>
            </form>
        </section>

        <section aria-labelledby="h-mail" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-mail" class="font-display text-h4 font-semibold text-text">E-posta adresi</h2>
            <p class="mt-1 text-sm text-text-muted">Şu anki adres: <span class="font-mono text-text">{{ $user->email }}</span></p>
            @if ($pendingEmail)
                <p class="mt-2 text-sm text-amber">Bekleyen değişiklik: {{ \App\Support\EmailMask::mask($pendingEmail) }} — yeni adresine gönderilen bağlantıyla onayla.</p>
            @endif
            <form method="POST" action="{{ route('panel.security.email') }}" class="mt-5 flex flex-wrap items-end gap-3">
                @csrf
                <x-ui.field name="email" label="Yeni e-posta adresi" class="min-w-64 flex-1"><x-ui.input name="email" type="email" autocomplete="email" required /></x-ui.field>
                <x-ui.button type="submit" size="sm" variant="secondary" icon="mail">Onay bağlantısı gönder</x-ui.button>
            </form>
        </section>

        <section aria-labelledby="h-sessions" class="rounded-lg border border-border bg-surface-1 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="h-sessions" class="font-display text-h4 font-semibold text-text">Oturumlar</h2>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('panel.security.sessions.revoke-others') }}">@csrf<x-ui.button type="submit" size="sm" variant="secondary">Diğerlerini kapat</x-ui.button></form>
                    <form method="POST" action="{{ route('panel.security.sessions.revoke-all') }}">@csrf<x-ui.button type="submit" size="sm" variant="ghost">Hepsini kapat (çıkış dahil)</x-ui.button></form>
                </div>
            </div>
            <ul class="mt-4 divide-y divide-border">
                @foreach ($sessions as $s)
                    <li class="flex flex-wrap items-center gap-3 py-3 text-sm">
                        <x-lucide-monitor-smartphone class="size-5 text-text-subtle" aria-hidden="true" />
                        <span class="text-text">{{ $s['device'] }}</span>
                        <span class="font-mono text-xs text-text-muted">{{ $s['ip'] ?? '—' }}</span>
                        <span class="text-xs text-text-subtle">{{ $s['last_active']->diffForHumans() }}</span>
                        @if ($s['current'])
                            <x-ui.badge tone="success" class="ml-auto">Bu cihaz</x-ui.badge>
                        @else
                            <form method="POST" action="{{ route('panel.security.sessions.revoke', $s['id']) }}" class="ml-auto">@csrf
                                <button type="submit" class="text-xs text-danger hover:underline">Kapat</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('panel.security.events') }}" class="mt-4 inline-block text-sm text-accent hover:underline">Son 90 günün güvenlik olayları →</a>
        </section>

        @if ($user->status->value !== 'pending_deletion' && ! $user->isStaff())
            <section aria-labelledby="h-delete" class="rounded-lg border border-danger/30 p-6">
                <h2 id="h-delete" class="font-display text-h4 font-semibold text-text">Hesabı sil</h2>
                <p class="mt-1 max-w-2xl text-sm text-text-muted">Talepten 14 gün sonra hesabın anonimleştirilir: kullanıcı adı ve e-posta silinir, oturumların kapanır. Destek geçmişi ve yasal saklama gerektiren kayıtlar anonim olarak korunur. 14 gün içinde iptal edebilirsin.</p>
                <form method="POST" action="{{ route('panel.account.delete-request') }}" class="mt-4">@csrf
                    <x-ui.button type="submit" size="sm" variant="danger" icon="trash-2">Hesap silme talebi oluştur</x-ui.button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.panel>
