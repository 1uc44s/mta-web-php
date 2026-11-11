{{-- CMS kaynak listesi — docs/CMS.md §9: durum sekmeleri, arama, tablo, sayfalama; sıralanabilir kaynaklarda klavye ile taşıma. --}}
<x-layouts.admin :title="$def['label']">
    <x-admin.header :title="$def['label']">
        <x-slot:actions>
            @if ($resource === 'rules')
                @can('content.publish')<x-ui.button size="sm" variant="secondary" icon="badge-check" :href="route('admin.content.rules.version')">{{ __('content_admin.rules_version.title') }}</x-ui.button>@endcan
            @endif
            @can(\App\Services\Content\ContentResourceRegistry::permission($resource, 'create'))
                <x-ui.button size="sm" icon="plus" :href="route($routeName.'.create')">{{ __('content_admin.create', ['name' => mb_strtolower($def['singular'])]) }}</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    @if ($def['publishable'])
        <nav aria-label="{{ __('content_admin.status_label') }}" class="mb-4 flex flex-wrap gap-1 border-b border-border">
            @foreach (__('content_admin.status') as $value => $label)
                @php $active = ($value === 'all' && $status === '') || $value === $status; @endphp
                <a href="{{ route($routeName.'.index', array_filter(['status' => $value === 'all' ? null : $value, 'q' => $q ?: null])) }}" @if ($active) aria-current="page" @endif
                   class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <form method="GET" role="search" class="mb-4 flex flex-wrap gap-2">
        @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
        <label for="q" class="sr-only">{{ __('content_admin.search') }}</label>
        <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('content_admin.search') }}" class="h-10 min-w-64 flex-1 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text placeholder:text-text-subtle sm:flex-none">
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('content_admin.search') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        @foreach ($def['columns'] as $column)
                            <th scope="col" class="px-4 py-2.5 font-medium">{{ $def['fields'][$column]['label'] ?? ($column === 'slug' ? __('content_admin.slug') : $column) }}</th>
                        @endforeach
                        @if ($def['publishable'])<th scope="col" class="px-4 py-2.5 font-medium">{{ __('content_admin.status_label') }}</th>@endif
                        @if ($def['sortable'] && $q === '' && $status === '')<th scope="col" class="w-24 px-4 py-2.5 font-medium"><span class="sr-only">Sıra</span></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($items as $item)
                        <tr class="h-11 hover:bg-surface-2/60">
                            @foreach ($def['columns'] as $i => $column)
                                @php
                                    $field = $def['fields'][$column] ?? ['type' => 'text'];
                                    $value = $item->getAttribute($column);
                                    $display = match ($field['type']) {
                                        'bool' => $value ? __('content_admin.yes') : __('content_admin.no'),
                                        'belongs' => $lookups[$column][$value] ?? __('content_admin.none'),
                                        'select' => $field['options'][$value instanceof \BackedEnum ? $value->value : (string) $value] ?? (string) $value,
                                        'datetime' => $value?->format('d.m.Y H:i'),
                                        default => \Illuminate\Support\Str::limit((string) $value, 70),
                                    };
                                @endphp
                                <td class="px-4 {{ in_array($column, ['slug', 'code'], true) ? 'font-mono text-xs text-text-muted' : 'text-text-muted' }}">
                                    @if ($i === 0)
                                        <a href="{{ route($routeName.'.edit', ['id' => $item->getKey()]) }}" class="font-medium text-text hover:text-accent">{{ $display ?: __('content_admin.none') }}</a>
                                    @else
                                        {{ $display ?: __('content_admin.none') }}
                                    @endif
                                </td>
                            @endforeach
                            @if ($def['publishable'])
                                <td class="px-4">@include('admin.content.partials.status', ['status' => $item->getAttribute('status')])</td>
                            @endif
                            @if ($def['sortable'] && $q === '' && $status === '')
                                <td class="px-4">
                                    <div class="flex gap-1">
                                        @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $direction => $icon)
                                            <form method="POST" action="{{ route($routeName.'.reorder') }}">@csrf
                                                <input type="hidden" name="id" value="{{ $item->getKey() }}"><input type="hidden" name="direction" value="{{ $direction }}">
                                                <button type="submit" class="grid size-8 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('content_admin.move_'.$direction) }}: {{ $item->getAttribute($def['title']) }}">
                                                    <x-dynamic-component :component="'lucide-'.$icon" class="size-4" aria-hidden="true" />
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-text-muted">{{ $q !== '' || $status !== '' ? __('content_admin.empty_filtered') : __('content_admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.admin>
