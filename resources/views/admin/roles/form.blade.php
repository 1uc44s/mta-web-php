@php
    $editable = ! $role->exists || (! $role->is_system && auth()->user()->can('roles.manage'));
    $scoped = $scoped ?? [];
@endphp
<x-layouts.admin :title="$role->exists ? $role->name : __('admin.roles.create')">
    <x-admin.header :title="$role->exists ? $role->name : __('admin.roles.create')" :description="$role->is_system ? __('admin.roles.system_note') : null" :back="route('admin.roles.index')" :back-label="__('admin.roles.title')" />

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6">
        @csrf
        <fieldset @disabled(! $editable) class="space-y-6">
            <x-admin.card :title="__('admin.roles.edit')">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field name="name" :label="__('admin.roles.fields.name')" required><x-ui.input name="name" :value="$role->name" maxlength="100" required /></x-ui.field>
                    <x-ui.field name="slug" :label="__('admin.roles.fields.slug')" required><x-ui.input name="slug" :value="$role->slug" maxlength="64" pattern="[a-z][a-z0-9_]*" required class="font-mono" /></x-ui.field>
                    <x-ui.field name="priority" :label="__('admin.roles.fields.priority')" required><x-ui.input name="priority" type="number" min="1" max="89" :value="$role->priority" required /></x-ui.field>
                    <x-ui.field name="color" :label="__('admin.roles.fields.color')">
                        <select id="f-color" name="color" class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
                            @foreach (['accent', 'amber', 'info', 'success', 'neutral'] as $c)<option value="{{ $c }}" @selected(old('color', $role->color) === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </x-ui.field>
                    <div class="md:col-span-2"><x-ui.field name="description" :label="__('admin.roles.fields.description')"><x-ui.input name="description" :value="$role->description" maxlength="500" /></x-ui.field></div>
                    <label class="flex items-center gap-2 text-text md:col-span-2">
                        <input type="hidden" name="is_staff" value="0">
                        <input type="checkbox" name="is_staff" value="1" @checked(old('is_staff', $role->is_staff)) class="size-4 accent-[var(--color-accent)]">
                        {{ __('admin.roles.fields.is_staff') }}
                    </label>
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.roles.fields.permissions')">
                @if ($role->exists && $role->isSuperAdmin())
                    <p class="text-text-muted">{{ __('admin.roles.all_permissions') }} (Gate::before)</p>
                @else
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($groups as $group => $perms)
                            <fieldset>
                                <legend class="mb-2 font-mono text-xs font-semibold tracking-wider text-text-subtle uppercase">{{ $group }}</legend>
                                <ul class="space-y-1.5">
                                    @foreach ($perms as $perm)
                                        <li>
                                            <label class="flex items-start gap-2">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm['key'] }}" @checked(in_array($perm['key'], old('permissions', $granted), true)) class="mt-0.5 size-4 shrink-0 accent-[var(--color-accent)]">
                                                <span>
                                                    <span class="font-mono text-xs text-text">{{ $perm['key'] }}</span>
                                                    @if ($perm['sensitive'])<x-ui.badge tone="amber" class="ml-1">{{ __('admin.roles.sensitive') }}</x-ui.badge>@endif
                                                    @if (in_array($perm['key'], $scoped, true))<x-ui.badge tone="info" class="ml-1">{{ __('admin.roles.scoped') }}</x-ui.badge>@endif
                                                    <span class="block text-xs text-text-muted">{{ $perm['description'] }}</span>
                                                </span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </fieldset>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            @if ($editable)
                <x-admin.card>
                    <x-ui.field name="reason" :label="__('admin.reason')" :hint="__('admin.reason_hint')" required><x-ui.input name="reason" multiline rows="2" maxlength="1000" required /></x-ui.field>
                    <x-ui.button type="submit" class="mt-4" icon="save">{{ __('admin.roles.save') }}</x-ui.button>
                </x-admin.card>
            @endif
        </fieldset>
    </form>

    @if ($role->exists && $editable)
        <details class="mt-6">
            <summary class="cursor-pointer text-sm text-danger hover:underline">{{ __('admin.roles.delete') }}</summary>
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="mt-3 max-w-md space-y-3">
                @csrf
                <x-ui.field name="reason" :label="__('admin.reason')" required><x-ui.input name="reason" maxlength="1000" required /></x-ui.field>
                <x-ui.button type="submit" size="sm" variant="danger">{{ __('admin.roles.delete') }}</x-ui.button>
            </form>
        </details>
    @endif
</x-layouts.admin>
