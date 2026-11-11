{{-- İletişim mesajı detayı. Gövde düz metin olarak (escape) gösterilir. --}}
<x-layouts.admin :title="$message->subject">
    <x-admin.header :title="$message->subject" :back="route('admin.contact.index')" :back-label="__('content_admin.nav.contact')" />
    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <x-admin.card>
            <p class="whitespace-pre-line text-text">{{ $message->body }}</p>
        </x-admin.card>
        <x-admin.card>
            <dl class="space-y-3">
                <div><dt class="text-xs text-text-subtle">Gönderen</dt><dd class="text-text">{{ $message->name }}</dd></div>
                <div><dt class="text-xs text-text-subtle">E-posta</dt><dd class="font-mono text-xs break-all text-text">{{ $message->email }}</dd></div>
                <div><dt class="text-xs text-text-subtle">Tarih</dt><dd class="text-text">{{ $message->created_at?->format('d.m.Y H:i') }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('admin.contact.status', $message) }}" class="mt-5 space-y-3">@csrf
                <label for="f-status" class="block text-xs text-text-subtle">Durum</label>
                <select id="f-status" name="status" class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
                    @foreach (['new' => 'Yeni', 'read' => 'Okundu', 'replied' => 'Yanıtlandı', 'spam' => 'Spam'] as $value => $label)
                        <option value="{{ $value }}" @selected($message->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" size="sm" class="w-full">{{ __('content_admin.save') }}</x-ui.button>
            </form>
        </x-admin.card>
    </div>
</x-layouts.admin>
