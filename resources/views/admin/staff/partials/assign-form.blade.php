{{-- Rol atama formu (kullanıcı detayı ve personel detayında ortak). $assignable: atayanın atayabileceği roller. --}}
<form method="POST" action="{{ route('admin.staff.roles.assign', $target) }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <x-ui.field name="role_id" :label="__('admin.staff.role')" required>
        <select id="f-role_id" name="role_id" required class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            @foreach ($assignable as $role)
                <option value="{{ $role->id }}" @selected((int) old('role_id') === $role->id)>{{ $role->name }} ({{ $role->priority }})</option>
            @endforeach
        </select>
    </x-ui.field>
    <x-ui.field name="expires_at" :label="__('admin.expires_at')">
        <x-ui.input name="expires_at" type="datetime-local" />
    </x-ui.field>
    <div class="sm:col-span-2">
        <x-ui.field name="reason" :label="__('admin.reason')" :hint="__('admin.reason_hint')" required>
            <x-ui.input name="reason" multiline rows="2" maxlength="1000" required />
        </x-ui.field>
    </div>
    <div class="sm:col-span-2">
        <x-ui.button type="submit" size="sm" icon="user-plus">{{ __('admin.staff.assign') }}</x-ui.button>
    </div>
</form>
