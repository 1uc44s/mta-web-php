{{-- Bölüm düzenleyici — docs/CMS.md §4, §9. Her bölüm: tür, görünürlük, şemaya göre doğrulanan JSON veri, klavye ile sıralama. --}}
<x-layouts.admin :title="__('content_admin.sections').' · '.$page->title">
    <x-admin.header :title="$page->title" :description="__('content_admin.sections')" :back="route('admin.content.pages.edit', ['id' => $page->id])" :back-label="__('content_admin.edit', ['name' => 'Sayfa'])" />

    @can('content.update')
        <x-admin.card class="mb-6">
            <form method="POST" action="{{ route('admin.content.pages.sections.store', $page) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <x-ui.field name="type" label="Bölüm türü" class="min-w-64">
                    <select id="f-type" name="type" class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
                        @foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </x-ui.field>
                <x-ui.button type="submit" size="sm" icon="plus">Bölüm ekle (gizli olarak)</x-ui.button>
            </form>
        </x-admin.card>
    @endcan

    <ol class="space-y-4">
        @forelse ($sections as $section)
            @php $schema = \App\Services\Content\SectionRegistry::schema($section->type); @endphp
            <li id="bolum-{{ $section->id }}">
                <x-admin.card>
                    <x-slot:title>{{ $loop->iteration }}. {{ $types[$section->type] ?? $section->type }}</x-slot:title>
                    <x-slot:actions>
                        @if ($section->is_visible)<x-ui.badge tone="success">Görünür</x-ui.badge>@else<x-ui.badge>Gizli</x-ui.badge>@endif
                        @can('content.update')
                            @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $direction => $icon)
                                <form method="POST" action="{{ route('admin.content.pages.sections.reorder', [$page, $section]) }}">@csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button type="submit" class="grid size-8 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('content_admin.move_'.$direction) }}"><x-dynamic-component :component="'lucide-'.$icon" class="size-4" aria-hidden="true" /></button>
                                </form>
                            @endforeach
                        @endcan
                    </x-slot:actions>

                    <form method="POST" action="{{ route('admin.content.pages.sections.update', [$page, $section]) }}" class="space-y-3">
                        @csrf
                        <fieldset @disabled(! auth()->user()->can('content.update')) class="space-y-3">
                            <label for="data-{{ $section->id }}" class="block text-xs text-text-subtle">Veri (JSON) · alanlar: <span class="font-mono">{{ implode(', ', array_keys($schema)) }}</span></label>
                            <textarea id="data-{{ $section->id }}" name="data" rows="{{ min(24, max(6, substr_count(json_encode($section->data, JSON_PRETTY_PRINT), "\n") + 2)) }}" class="w-full rounded-md border border-border-input bg-bg px-3 py-2 font-mono text-xs leading-relaxed text-text">{{ old('data', json_encode($section->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            <div class="flex flex-wrap items-center gap-4">
                                <input type="hidden" name="is_visible" value="0">
                                <label class="flex items-center gap-2 text-text"><input type="checkbox" name="is_visible" value="1" @checked($section->is_visible) class="size-4 accent-[var(--color-accent)]">Sitede göster</label>
                                <x-ui.button type="submit" size="sm" icon="save">{{ __('content_admin.save') }}</x-ui.button>
                            </div>
                        </fieldset>
                    </form>
                    @can('content.update')
                        <form method="POST" action="{{ route('admin.content.pages.sections.destroy', [$page, $section]) }}" class="mt-3">@csrf
                            <button type="submit" class="text-xs text-danger hover:underline">Bölümü sil</button>
                        </form>
                    @endcan
                </x-admin.card>
            </li>
        @empty
            <li class="text-text-muted">{{ __('content_admin.empty') }}</li>
        @endforelse
    </ol>
</x-layouts.admin>
