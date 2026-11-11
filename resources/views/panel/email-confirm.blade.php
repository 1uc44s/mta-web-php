{{-- E-posta değişikliği onayı — docs/ROUTES.md §5.8: GET yalnızca ekranı gösterir, değişiklik POST ile uygulanır. --}}
<x-layouts.panel title="E-posta değişikliği">
    <div class="mx-auto max-w-xl rounded-lg border border-border bg-surface-1 p-6">
        <h1 class="font-display text-h3 font-bold text-text">E-posta değişikliğini onayla</h1>
        @if ($change)
            <p class="mt-3 text-text-muted">Hesabının e-posta adresi <span class="font-mono text-text">{{ \App\Support\EmailMask::mask($change->new_email) }}</span> olarak değiştirilecek.</p>
            <form method="POST" action="{{ route('panel.security.email.confirm.store', $token) }}" class="mt-5">@csrf
                <x-ui.button type="submit" icon="check">Değişikliği onayla</x-ui.button>
            </form>
        @else
            <p class="mt-3 text-text-muted">Bu bağlantı geçersiz veya süresi dolmuş. Güvenlik sayfasından yeni bir talep oluşturabilirsin.</p>
            <x-ui.button class="mt-5" variant="secondary" :href="route('panel.security')">Güvenlik sayfasına dön</x-ui.button>
        @endif
    </div>
</x-layouts.panel>
