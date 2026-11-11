{{-- Medya kütüphanesi — docs/CMS.md §3.3. Yükleme: JPG/PNG/WebP/GIF ≤ 8 MB, SVG yok; WebP varyantları üretilir. Alt metin zorunlu. --}}
<x-layouts.admin :title="__('content_admin.nav.media')">
    <x-admin.header :title="__('content_admin.nav.media')" description="Görseller yeniden kodlanır (EXIF temizlenir) ve 480/960/1600 px WebP varyantları üretilir." />

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div>
            <form method="GET" role="search" class="mb-4 flex gap-2">
                <label for="q" class="sr-only">{{ __('content_admin.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Alt metin veya dosya adı" class="h-10 min-w-64 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
                <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('content_admin.search') }}</x-ui.button>
            </form>

            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($items as $m)
                    <li class="overflow-hidden rounded-lg border border-border bg-surface-1">
                        <img src="{{ $m->url(480) }}" alt="{{ $m->alt_text ?? '' }}" width="{{ $m->width }}" height="{{ $m->height }}" loading="lazy" decoding="async" class="aspect-video w-full bg-surface-2 object-cover">
                        <details class="p-3">
                            <summary class="cursor-pointer truncate text-xs text-text">#{{ $m->id }} · {{ $m->alt_text ?: ($m->is_decorative ? 'Dekoratif' : $m->original_name) }}</summary>
                            <p class="mt-2 font-mono text-[11px] text-text-subtle">{{ $m->width }}×{{ $m->height }} · {{ number_format($m->size_bytes / 1024, 0, ',', '.') }} KB</p>
                            @can('media.upload')
                                <form method="POST" action="{{ route('admin.media.update', $m) }}" class="mt-2 space-y-2">@csrf
                                    <label for="alt-{{ $m->id }}" class="sr-only">Alternatif metin</label>
                                    <input id="alt-{{ $m->id }}" name="alt_text" value="{{ $m->alt_text }}" maxlength="255" placeholder="Alternatif metin" class="h-9 w-full rounded-md border border-border-input bg-bg px-2 text-xs text-text">
                                    <label class="flex items-center gap-2 text-xs text-text"><input type="checkbox" name="is_decorative" value="1" @checked($m->is_decorative) class="size-4 accent-[var(--color-accent)]">Dekoratif</label>
                                    <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ __('content_admin.save') }}</x-ui.button>
                                </form>
                            @endcan
                            @can('media.delete')
                                <form method="POST" action="{{ route('admin.media.destroy', $m) }}" class="mt-2">@csrf
                                    <button type="submit" class="text-xs text-danger hover:underline">{{ __('content_admin.delete') }}</button>
                                </form>
                            @endcan
                        </details>
                    </li>
                @empty
                    <li class="col-span-full text-text-muted">{{ __('content_admin.empty') }}</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $items->links() }}</div>
        </div>

        @can('media.upload')
            <x-admin.card title="Görsel yükle">
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <x-ui.field name="file" label="Dosya (JPG, PNG, WebP, GIF · ≤ 8 MB)" required>
                        <input id="f-file" name="file" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required class="block w-full text-sm text-text file:mr-3 file:rounded-md file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-text">
                    </x-ui.field>
                    <x-ui.field name="alt_text" label="Alternatif metin" hint="Görseli göremeyen biri için ne anlatıyor? Dekoratifse boş bırakıp işaretle.">
                        <x-ui.input name="alt_text" maxlength="255" />
                    </x-ui.field>
                    <label class="flex items-center gap-2 text-text"><input type="checkbox" name="is_decorative" value="1" class="size-4 accent-[var(--color-accent)]">Dekoratif görsel</label>
                    <x-ui.button type="submit" size="sm" icon="upload">Yükle</x-ui.button>
                </form>
            </x-admin.card>
        @endcan
    </div>
</x-layouts.admin>
