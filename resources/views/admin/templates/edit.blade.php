{{-- Şablon editörü — docs/CMS.md §6.2–6.5: değişken paneli, kayıt anında doğrulama, örnek veriyle önizleme, test gönderimi, revizyonlar. --}}
<x-layouts.admin :title="$template->key">
    <x-admin.header :title="$template->key" :description="'Kanal: '.$template->channel.' · v'.$template->version" :back="route('admin.templates.index')" :back-label="__('content_admin.nav.templates')">
        <x-slot:actions>
            @can('templates.manage')
                <form method="POST" action="{{ route('admin.templates.test-send', $template) }}">@csrf
                    <x-ui.button type="submit" size="sm" variant="secondary" icon="send">Kendime test gönder</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-admin.card title="Şablon">
                <form method="POST" action="{{ route('admin.templates.update', $template) }}" class="space-y-4">
                    @csrf
                    <fieldset @disabled(! auth()->user()->can('templates.manage')) class="space-y-4">
                        <x-ui.field name="subject" label="Konu (≤ 200)">
                            <x-ui.input name="subject" :value="$template->subject" maxlength="200" class="font-mono text-xs" />
                        </x-ui.field>
                        <x-ui.field name="body" label="Gövde (Markdown + şablon söz dizimi)" required>
                            <textarea id="f-body" name="body" rows="16" required class="w-full rounded-md border border-border-input bg-bg px-3 py-2 font-mono text-[13px] leading-relaxed text-text">{{ old('body', $template->body) }}</textarea>
                        </x-ui.field>
                        <x-ui.button type="submit" size="sm" icon="save">{{ __('content_admin.save') }}</x-ui.button>
                    </fieldset>
                </form>
            </x-admin.card>

            <x-admin.card title="Önizleme (kayıtlı sürüm, örnek veriyle)">
                @if ($preview['error'])
                    <p role="alert" class="text-danger">{{ $preview['error'] }}</p>
                @else
                    <p class="mb-3 text-xs text-text-subtle">Konu: <span class="text-text">{{ $preview['subject'] }}</span></p>
                    <div class="rounded-md border border-border bg-bg p-4"><x-ui.prose :html="$preview['html']" /></div>
                @endif
            </x-admin.card>
        </div>

        <aside class="space-y-6">
            <x-admin.card title="Kullanılabilir değişkenler">
                <ul class="space-y-1 font-mono text-xs text-text">
                    @foreach ($template->variables as $var)<li>@{{ {{ $var }} }}</li>@endforeach
                </ul>
                <p class="mt-4 text-xs text-text-muted">Filtreler: <span class="font-mono">{{ implode(', ', $filters) }}</span> — ör. <span class="font-mono">@{{ user.username | upper }}</span>, <span class="font-mono">@{{ x | truncate(80) }}</span>, <span class="font-mono">@{{ x | default("—") }}</span>.</p>
                <p class="mt-2 text-xs text-text-muted">Koşul: <span class="font-mono">@{{#if değişken}} … @{{/if}}</span> (tek seviye). Ham çıktı (<span class="font-mono">@{{{ }}}</span>), kod veya ifade desteklenmez.</p>
            </x-admin.card>

            <x-admin.card title="Revizyonlar" flush>
                <ul class="divide-y divide-border">
                    @forelse ($revisions as $rev)
                        <li class="flex items-center justify-between gap-2 px-4 py-2.5">
                            <span class="text-xs text-text-muted">v{{ $rev->version }} · {{ $rev->created_at->format('d.m.Y H:i') }} · {{ $rev->author?->username ?? '—' }}</span>
                            @can('templates.manage')
                                <form method="POST" action="{{ route('admin.templates.revisions.restore', [$template, $rev->id]) }}">@csrf
                                    <button type="submit" class="text-xs text-accent hover:underline">Geri yükle</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="px-4 py-4 text-xs text-text-muted">Henüz revizyon yok.</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </aside>
    </div>
</x-layouts.admin>
