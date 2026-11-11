{{-- MTA sunucu formu (oluştur/güncelle) — admin/integrations/mta içinden. --}}
<form method="POST" action="{{ $action }}" class="mt-3 grid gap-3 sm:grid-cols-2">
    @csrf
    @foreach (['slug', 'name', 'connect_host', 'connect_port', 'max_players'] as $field)
        <x-ui.field :name="$field" :label="__('game.admin.fields.'.$field)" :required="$field !== 'max_players'">
            <x-ui.input :name="$field" :type="in_array($field, ['connect_port', 'max_players'], true) ? 'number' : 'text'" :value="$server?->{$field}" />
        </x-ui.field>
    @endforeach
    <x-ui.field name="maintenance_note" :label="__('game.admin.maintenance_note')"><x-ui.input name="maintenance_note" :value="$server?->maintenance_note" /></x-ui.field>
    <div class="flex flex-wrap gap-4 sm:col-span-2 text-sm text-text">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_maintenance" value="1" @checked($server?->is_maintenance)> {{ __('game.admin.maintenance') }}</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_primary" value="1" @checked($server?->is_primary)> {{ __('game.admin.primary') }}</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($server === null || $server->getAttribute('is_active'))> {{ __('game.admin.active') }}</label>
    </div>
    <x-ui.field name="reason" :label="__('admin.reason')" class="sm:col-span-2" required><x-ui.input name="reason" required /></x-ui.field>
    <div class="sm:col-span-2"><x-ui.button type="submit" size="sm" icon="save">{{ $server ? __('game.admin.save_server') : __('game.admin.add_server') }}</x-ui.button></div>
</form>
