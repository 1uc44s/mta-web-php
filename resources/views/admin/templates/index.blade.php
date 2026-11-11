{{-- Mesaj şablonları — docs/CMS.md §6.4: anahtar × kanal. --}}
<x-layouts.admin :title="__('content_admin.nav.templates')">
    <x-admin.header :title="__('content_admin.nav.templates')" description="Sistem e-postalarının metinleri. Değişkenler kod tarafından sağlanır; listede olmayan değişken kullanılamaz." />
    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr><th scope="col" class="px-4 py-2.5 font-medium">Anahtar</th><th scope="col" class="px-4 py-2.5 font-medium">Kanal</th><th scope="col" class="px-4 py-2.5 font-medium">Konu</th><th scope="col" class="px-4 py-2.5 font-medium">Sürüm</th><th scope="col" class="px-4 py-2.5 font-medium">Durum</th></tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($templates as $t)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.templates.edit', $t) }}" class="font-mono text-xs text-text hover:text-accent">{{ $t->key }}</a></td>
                            <td class="px-4 text-text-muted">{{ $t->channel }}</td>
                            <td class="px-4 text-text-muted">{{ \Illuminate\Support\Str::limit((string) $t->subject, 60) }}</td>
                            <td class="px-4 font-mono tabular-nums text-text-muted">v{{ $t->version }}</td>
                            <td class="px-4"><x-ui.badge :tone="$t->status === 'active' ? 'success' : 'neutral'">{{ $t->status === 'active' ? 'Aktif' : $t->status }}</x-ui.badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-layouts.admin>
