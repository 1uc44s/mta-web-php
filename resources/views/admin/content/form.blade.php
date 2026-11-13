{{-- CMS kaynak formu — docs/CMS.md §9: solda içerik, sağda yan panel (durum, yayın, slug, SEO). Alanlar registry'den üretilir. --}}
@php
    $exists = $model->exists;
    $status = $def['publishable'] && $exists ? $model->getAttribute('status') : null;
    $statusValue = $status instanceof \BackedEnum ? $status->value : null;
    $canUpdate = auth()->user()->can(\App\Services\Content\ContentResourceRegistry::permission($resource, $exists ? 'update' : 'create'));
    $canPublish = auth()->user()->can(\App\Services\Content\ContentResourceRegistry::permission($resource, 'publish'));
    $title = $exists ? (string) $model->getAttribute($def['title']) : __('content_admin.create', ['name' => mb_strtolower($def['singular'])]);
    $inputClass = 'w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text';
    $transitions = match ($statusValue) {
        'draft' => ['submit-review', 'publish', 'archive'],
        'in_review' => ['publish', 'unpublish'],
        'scheduled' => ['publish', 'unpublish'],
        'published' => ['unpublish', 'archive'],
        'archived' => ['restore'],
        default => [],
    };
@endphp
<x-layouts.admin :title="$title">
    <x-admin.header :title="$title" :back="route($routeName.'.index')" :back-label="$def['label']">
        <x-slot:actions>
            @if ($status)@include('admin.content.partials.status', ['status' => $status])@endif
            @if ($resource === 'pages' && $exists)
                <x-ui.button size="sm" variant="secondary" icon="layout-list" :href="route('admin.content.pages.sections', $model->getKey())">{{ __('content_admin.sections') }}</x-ui.button>
            @endif
            @if ($resource === 'academy-quizzes' && $exists)
                <x-ui.button size="sm" variant="secondary" icon="list-checks" :href="route('admin.academy-quizzes.questions', $model->getKey())">Sorular</x-ui.button>
            @endif
            @if (isset($def['revisions']) && $exists)
                <x-ui.button size="sm" variant="secondary" icon="history" :href="route('admin.content.'.$resource.'.revisions', $model->getKey())">{{ __('content_admin.revisions') }}</x-ui.button>
            @endif
            @if ($previewUrl)<x-ui.button size="sm" variant="ghost" icon="eye" :href="$previewUrl">{{ __('content_admin.preview') }}</x-ui.button>@endif
            @if ($publicUrl && $statusValue === 'published')<x-ui.button size="sm" variant="ghost" icon="external-link" :href="$publicUrl" target="_blank" rel="noopener">{{ __('content_admin.view_on_site') }}</x-ui.button>@endif
        </x-slot:actions>
    </x-admin.header>

    @if ($statusValue === 'published')
        <p role="status" class="mb-4 flex items-center gap-2 rounded-md border border-amber/40 bg-amber-soft px-4 py-2.5 text-text">
            <x-lucide-radio class="size-4 text-amber" aria-hidden="true" />{{ __('content_admin.live_warning') }}
        </p>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <form id="content-form" method="POST" action="{{ $exists ? route($routeName.'.update', ['id' => $model->getKey()]) : route($routeName.'.store') }}" class="space-y-6">
            @csrf
            <fieldset @disabled(! $canUpdate) class="space-y-6">
                <x-admin.card>
                    <div class="grid gap-5 md:grid-cols-2">
                        @foreach ($def['fields'] as $name => $field)
                            @php
                                $value = old($name, match ($field['type']) {
                                    'tags' => $tags,
                                    'rules' => $ruleCodes,
                                    default => $model->getAttribute($name),
                                });
                                if ($value instanceof \DateTimeInterface) { $value = $value->format('Y-m-d\TH:i'); }
                                if ($value instanceof \BackedEnum) { $value = $value->value; }
                                $wide = ($field['wide'] ?? false) || in_array($field['type'], ['markdown', 'textarea', 'channels'], true);
                                $required = in_array('required', $field['rules'] ?? [], true);
                            @endphp
                            <div @class(['md:col-span-2' => $wide])>
                                @if ($field['type'] === 'bool')
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <label class="flex min-h-11 items-center gap-2 text-text">
                                        <input type="checkbox" name="{{ $name }}" value="1" @checked($value) class="size-4 accent-[var(--color-accent)]">{{ $field['label'] }}
                                    </label>
                                    @if (isset($field['help']))<p class="text-xs text-text-subtle">{{ $field['help'] }}</p>@endif
                                @elseif ($field['type'] === 'channels')
                                    <fieldset>
                                        <legend class="mb-2 text-sm font-medium text-text">{{ $field['label'] }}</legend>
                                        <div class="flex flex-wrap gap-4">
                                            @foreach ($field['options'] as $optValue => $optLabel)
                                                <label class="flex items-center gap-2 text-text"><input type="checkbox" name="{{ $name }}[]" value="{{ $optValue }}" @checked(in_array($optValue, (array) $value, true)) class="size-4 accent-[var(--color-accent)]">{{ $optLabel }}</label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @else
                                    <x-ui.field :name="$name" :label="$field['label']" :hint="$field['type'] === 'markdown' ? __('content_admin.markdown_hint') : ($field['help'] ?? null)" :required="$required">
                                        @switch($field['type'])
                                            @case('markdown')
                                                <textarea id="f-{{ $name }}" name="{{ $name }}" rows="14" class="{{ $inputClass }} py-2 font-mono text-[13px] leading-relaxed" @required($required)>{{ $value }}</textarea>
                                                @break
                                            @case('textarea')
                                                <textarea id="f-{{ $name }}" name="{{ $name }}" rows="3" class="{{ $inputClass }} py-2" @required($required)>{{ $value }}</textarea>
                                                @break
                                            @case('select')
                                                <select id="f-{{ $name }}" name="{{ $name }}" class="{{ $inputClass }} h-11" @required($required)>
                                                    @foreach ($field['options'] as $optValue => $optLabel)<option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>@endforeach
                                                </select>
                                                @break
                                            @case('belongs')
                                                <select id="f-{{ $name }}" name="{{ $name }}" class="{{ $inputClass }} h-11" @required($required)>
                                                    @unless ($required)<option value="">{{ __('content_admin.none') }}</option>@endunless
                                                    @foreach ($lookups[$name] ?? [] as $optValue => $optLabel)<option value="{{ $optValue }}" @selected((int) $value === (int) $optValue)>{{ $optLabel }}</option>@endforeach
                                                </select>
                                                @break
                                            @case('media')
                                                <select id="f-{{ $name }}" name="{{ $name }}" class="{{ $inputClass }} h-11">
                                                    <option value="">{{ __('content_admin.no_media') }}</option>
                                                    @foreach ($media as $m)<option value="{{ $m->id }}" @selected((int) $value === $m->id)>#{{ $m->id }} · {{ $m->alt_text ?: $m->original_name }}</option>@endforeach
                                                </select>
                                                @break
                                            @case('int')
                                                <input id="f-{{ $name }}" name="{{ $name }}" type="number" value="{{ $value }}" class="{{ $inputClass }} h-11">
                                                @break
                                            @case('datetime')
                                                <input id="f-{{ $name }}" name="{{ $name }}" type="datetime-local" value="{{ $value }}" class="{{ $inputClass }} h-11" @required($required)>
                                                @break
                                            @case('url')
                                                <input id="f-{{ $name }}" name="{{ $name }}" type="url" value="{{ $value }}" inputmode="url" class="{{ $inputClass }} h-11" @required($required)>
                                                @break
                                            @default
                                                <input id="f-{{ $name }}" name="{{ $name }}" type="text" value="{{ $value }}" class="{{ $inputClass }} h-11" @required($required)>
                                        @endswitch
                                    </x-ui.field>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-admin.card>

                @if ($resource === 'academy-quizzes' && $exists)
                <x-ui.button size="sm" variant="secondary" icon="list-checks" :href="route('admin.academy-quizzes.questions', $model->getKey())">Sorular</x-ui.button>
            @endif
            @if (isset($def['revisions']) && $exists)
                    <x-admin.card>
                        <x-ui.field name="revision_note" :label="__('content_admin.revision_note')"><x-ui.input name="revision_note" maxlength="255" /></x-ui.field>
                    </x-admin.card>
                @endif

                @if ($canUpdate)
                    <x-ui.button type="submit" icon="save">{{ __('content_admin.save') }}</x-ui.button>
                @endif
            </fieldset>
        </form>

        <aside class="space-y-6">
            @if ($def['slug_from'] !== null || $def['publishable'] || $hasSeo)
                <x-admin.card :title="__('content_admin.publishing')">
                    <div class="space-y-4">
                        @if ($def['slug_from'] !== null)
                            <x-ui.field name="slug" :label="__('content_admin.slug')" :hint="__('content_admin.slug_hint')">
                                <input id="f-slug" name="slug" form="content-form" value="{{ old('slug', $model->getAttribute('slug')) }}" maxlength="120" class="{{ $inputClass }} h-10 font-mono text-xs" @disabled(! $canUpdate)>
                            </x-ui.field>
                        @endif
                        @if ($def['publishable'])
                            <x-ui.field name="published_at" :label="__('content_admin.published_at')" :hint="__('content_admin.published_at_hint')">
                                <input id="f-published_at" name="published_at" form="content-form" type="datetime-local" value="{{ old('published_at', $model->getAttribute('published_at')?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }} h-10" @disabled(! $canUpdate)>
                            </x-ui.field>
                        @endif
                        @if ($hasSeo)
                            <x-ui.field name="seo_title" :label="__('content_admin.seo_title')">
                                <input id="f-seo_title" name="seo_title" form="content-form" value="{{ old('seo_title', $model->getAttribute('seo_title')) }}" maxlength="70" class="{{ $inputClass }} h-10" @disabled(! $canUpdate)>
                            </x-ui.field>
                            <x-ui.field name="meta_description" :label="__('content_admin.meta_description')">
                                <textarea id="f-meta_description" name="meta_description" form="content-form" maxlength="170" rows="3" class="{{ $inputClass }} py-2" @disabled(! $canUpdate)>{{ old('meta_description', $model->getAttribute('meta_description')) }}</textarea>
                            </x-ui.field>
                        @endif
                    </div>
                </x-admin.card>
            @endif

            @if ($transitions !== [])
                <x-admin.card :title="__('content_admin.status_label')">
                    <div class="flex flex-col gap-2">
                        @foreach ($transitions as $t)
                            @php $ability = $t === 'submit-review' ? 'update' : 'publish'; @endphp
                            @can(\App\Services\Content\ContentResourceRegistry::permission($resource, $ability))
                                <form method="POST" action="{{ route($routeName.'.'.$t, ['id' => $model->getKey()]) }}">@csrf
                                    <x-ui.button type="submit" size="sm" :variant="$t === 'publish' ? 'primary' : 'secondary'" class="w-full">{{ __('content_admin.actions.'.$t) }}</x-ui.button>
                                </form>
                            @endcan
                        @endforeach
                    </div>
                </x-admin.card>
            @endif

            @if ($exists)
                @can(\App\Services\Content\ContentResourceRegistry::permission($resource, 'delete'))
                    @if (($def['system_guard'] ?? false) && $model->getAttribute('is_system'))
                        <p class="text-xs text-text-subtle">{{ __('content_admin.system_record') }}</p>
                    @else
                        <details>
                            <summary class="cursor-pointer text-sm text-danger hover:underline">{{ __('content_admin.delete') }}</summary>
                            <form method="POST" action="{{ route($routeName.'.destroy', ['id' => $model->getKey()]) }}" class="mt-3 space-y-3">@csrf
                                <p class="text-xs text-text-muted">{{ __('content_admin.delete_confirm') }}</p>
                                <x-ui.button type="submit" size="sm" variant="danger">{{ __('content_admin.delete') }}</x-ui.button>
                            </form>
                        </details>
                    @endif
                @endcan
            @endif
        </aside>
    </div>
</x-layouts.admin>
