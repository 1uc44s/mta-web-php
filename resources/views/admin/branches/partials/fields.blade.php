{{-- Branş form alanları (oluşturma + düzenleme). --}}
<div class="grid gap-4 md:grid-cols-2">
    <x-ui.field name="name" :label="__('admin.branches.fields.name')" required><x-ui.input name="name" :value="$branch->name" maxlength="100" required /></x-ui.field>
    <x-ui.field name="slug" :label="__('admin.branches.fields.slug')" required><x-ui.input name="slug" :value="$branch->slug" maxlength="64" pattern="[a-z0-9]+(-[a-z0-9]+)*" required class="font-mono" /></x-ui.field>
    <x-ui.field name="type" :label="__('admin.branches.fields.type')" required>
        <select id="f-type" name="type" class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            @foreach (__('admin.branches.types') as $value => $label)<option value="{{ $value }}" @selected(old('type', $branch->type) === $value)>{{ $label }}</option>@endforeach
        </select>
    </x-ui.field>
    <x-ui.field name="parent_id" :label="__('admin.branches.fields.parent')">
        <select id="f-parent_id" name="parent_id" class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.none') }}</option>
            @foreach ($parents as $p)<option value="{{ $p->id }}" @selected((int) old('parent_id', $branch->parent_id) === $p->id)>{{ $p->name }}</option>@endforeach
        </select>
    </x-ui.field>
    <x-ui.field name="faction_id" label="Fraksiyon">
        <select id="f-faction_id" name="faction_id" class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.none') }}</option>
            @foreach ($factions as $f)<option value="{{ $f->id }}" @selected((int) old('faction_id', $branch->faction_id) === $f->id)>{{ $f->name }}</option>@endforeach
        </select>
    </x-ui.field>
    <x-ui.field name="description" :label="__('admin.branches.fields.description')"><x-ui.input name="description" :value="$branch->description" maxlength="500" /></x-ui.field>
    <label class="flex items-center gap-2 text-text md:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active)) class="size-4 accent-[var(--color-accent)]">
        {{ __('admin.branches.fields.is_active') }}
    </label>
</div>
