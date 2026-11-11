<x-layouts.admin :title="__('admin.settings.groups.'.$group)">
    <x-admin.header :title="__('admin.settings.groups.'.$group)" :description="__('admin.settings.intro')" :back="route('admin.settings.index')" :back-label="__('admin.settings.title')" />

    <x-admin.card>
        <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="space-y-5">
            @csrf
            <fieldset @disabled(! $canUpdate) class="space-y-5">
                @foreach ($fields as $field)
                    <div class="grid gap-2 md:grid-cols-[18rem_1fr] md:items-start">
                        <div>
                            <label for="f-{{ $field['name'] }}" class="font-mono text-xs text-text">{{ $field['key'] }}</label>
                            @if ($field['super'])<x-ui.badge tone="amber" icon="lock" class="ml-1">{{ __('admin.settings.super_only') }}</x-ui.badge>@endif
                        </div>
                        <div>
                            @if ($field['type'] === 'bool')
                                <input type="hidden" name="{{ $field['name'] }}" value="0" @disabled($field['locked'])>
                                <input id="f-{{ $field['name'] }}" type="checkbox" name="{{ $field['name'] }}" value="1" @checked(old($field['name'], $field['value'])) @disabled($field['locked']) class="size-5 accent-[var(--color-accent)]">
                            @elseif ($field['type'] === 'json')
                                <textarea id="f-{{ $field['name'] }}" name="{{ $field['name'] }}" rows="3" @disabled($field['locked']) class="w-full rounded-md border border-border-input bg-surface-1 px-3 py-2 font-mono text-xs text-text">{{ old($field['name'], $field['value']) }}</textarea>
                                <p class="mt-1 text-xs text-text-subtle">{{ __('admin.settings.json_hint') }}</p>
                            @else
                                <input id="f-{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] === 'int' ? 'number' : ($field['type'] === 'url' ? 'url' : 'text') }}" value="{{ old($field['name'], $field['value']) }}" @disabled($field['locked'])
                                       class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text disabled:opacity-60">
                            @endif
                            @error($field['name'])<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
                @if ($canUpdate)
                    <x-ui.button type="submit" icon="save">{{ __('admin.settings.save') }}</x-ui.button>
                @endif
            </fieldset>
        </form>
    </x-admin.card>
</x-layouts.admin>
