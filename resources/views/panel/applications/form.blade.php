{{-- Başvuru formu (yeni / taslak) — docs/SRS.md FR-APP-002. Alanlar sabit şemadan (App\Support\ApplicationForms). --}}
<x-layouts.panel :title="$type->name">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-ui.breadcrumb :items="[['label' => __('applications.title'), 'href' => route('panel.applications.index')], ['label' => $type->name]]" />
        <header>
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ $type->name }}</h1>
            @if ($type->description)<p class="mt-2 text-text-muted">{{ $type->description }}</p>@endif
            @if ($application)<p class="mt-2"><x-ui.badge>{{ __('applications.status.draft') }}</x-ui.badge></p>@endif
        </header>

        @if ($blocked)
            <div role="alert" class="rounded-md border border-amber/40 bg-amber/10 px-5 py-4 text-sm text-text">{{ $blocked }}</div>
        @else
            @error('type')<div role="alert" class="rounded-md border border-danger/40 bg-danger/10 px-5 py-4 text-sm text-text">{{ $message }}</div>@enderror

            <form method="POST" action="{{ $application ? route('panel.applications.update', $application) : route('panel.applications.store', $type->slug) }}" class="space-y-5 rounded-lg border border-border bg-surface-1 p-6">
                @csrf
                @if ($characters->isNotEmpty() || $type->requires_character)
                    <x-ui.field name="character" :label="__('applications.character')" :required="$type->requires_character">
                        <select id="f-character" name="character" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                            <option value="">{{ __('applications.character_none') }}</option>
                            @foreach ($characters as $c)
                                <option value="{{ $c->ulid }}" @selected(old('character', $application?->character?->ulid) === $c->ulid)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                @endif

                @foreach ($fields as $key => $def)
                    @php($name = 'answers['.$key.']')
                    @php($value = old('answers.'.$key, $answers[$key] ?? null))
                    @php($required = in_array('required', $def['rules'], true))
                    <x-ui.field :name="'answers.'.$key" :label="$def['label']" :hint="$def['hint'] ?? null" :required="$required">
                        @if ($def['type'] === 'textarea')
                            <textarea id="f-answers.{{ $key }}" name="{{ $name }}" rows="5" class="min-h-32 w-full resize-y rounded-sm border border-border-input bg-surface-2 px-3 py-2.5 text-text">{{ $value }}</textarea>
                        @elseif ($def['type'] === 'select')
                            <select id="f-answers.{{ $key }}" name="{{ $name }}" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                                <option value="">—</option>
                                @foreach ($def['options'] ?? [] as $optValue => $optLabel)
                                    <option value="{{ $optValue }}" @selected($value === $optValue)>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="f-answers.{{ $key }}" name="{{ $name }}" type="{{ $def['type'] === 'number' ? 'number' : 'text' }}" value="{{ $value }}"
                                   class="h-10 w-full rounded-sm border border-border-input bg-surface-2 px-3 text-text">
                        @endif
                    </x-ui.field>
                @endforeach

                <div class="flex flex-wrap gap-2 border-t border-border pt-5">
                    <x-ui.button type="submit" name="action" value="submit" icon="send">{{ __('applications.submit') }}</x-ui.button>
                    <x-ui.button type="submit" name="action" value="save_draft" variant="secondary">{{ __('applications.save_draft') }}</x-ui.button>
                </div>
            </form>

            @if ($application)
                <form method="POST" action="{{ route('panel.applications.cancel', $application) }}">@csrf<x-ui.button type="submit" size="sm" variant="ghost">{{ __('applications.cancel') }}</x-ui.button></form>
            @endif
        @endif
    </div>
</x-layouts.panel>
