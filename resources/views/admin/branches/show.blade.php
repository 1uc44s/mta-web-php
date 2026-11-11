<x-layouts.admin :title="$branch->name">
    <x-admin.header :title="$branch->name" :description="$branch->description" :back="route('admin.branches.index')" :back-label="__('admin.branches.title')">
        <x-slot:actions>
            <x-ui.badge tone="info">{{ __('admin.branches.types.'.$branch->type) }}</x-ui.badge>
            @if ($branch->is_active)<x-ui.badge tone="success">{{ __('admin.branches.active') }}</x-ui.badge>@else<x-ui.badge>{{ __('admin.branches.inactive') }}</x-ui.badge>@endif
        </x-slot:actions>
    </x-admin.header>

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <x-admin.card :title="__('admin.branches.members')" flush>
            <ul class="divide-y divide-border">
                @forelse ($memberList as $member)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                        <a href="{{ route('admin.staff.show', $member) }}" class="font-medium text-text hover:text-accent">{{ $member->username }}</a>
                        @if ($member->pivot->is_lead)<x-ui.badge tone="amber" icon="crown">{{ __('admin.branches.lead') }}</x-ui.badge>@endif
                        @if ($member->pivot->expires_at)<span class="text-xs text-text-subtle">→ {{ \Illuminate\Support\Carbon::parse($member->pivot->expires_at)->format('d.m.Y') }}</span>@endif
                        <div class="ml-auto flex items-center gap-2">
                            @can('branches.manage')
                                <form method="POST" action="{{ route('admin.branches.members.lead', [$branch, $member]) }}">@csrf
                                    <button type="submit" class="text-xs text-text-muted hover:text-accent">{{ $member->pivot->is_lead ? __('admin.branches.remove_lead') : __('admin.branches.make_lead') }}</button>
                                </form>
                            @endcan
                            @if ($canManageMembers && (! $member->pivot->is_lead || auth()->user()->can('branches.manage')))
                                <form method="POST" action="{{ route('admin.branches.members.destroy', [$branch, $member]) }}">@csrf
                                    <button type="submit" class="text-xs text-danger hover:underline">{{ __('admin.branches.remove') }}</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-text-muted">{{ __('admin.branches.members_empty') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        <div class="space-y-6">
            @if ($canManageMembers)
                <x-admin.card :title="__('admin.branches.add_member')">
                    <form method="POST" action="{{ route('admin.branches.members.store', $branch) }}" class="space-y-4">
                        @csrf
                        <x-ui.field name="user" :label="__('admin.staff.user')" required><x-ui.input name="user" maxlength="254" required autocomplete="off" /></x-ui.field>
                        <x-ui.field name="expires_at" :label="__('admin.expires_at')"><x-ui.input name="expires_at" type="datetime-local" /></x-ui.field>
                        @can('branches.manage')
                            <label class="flex items-center gap-2 text-text"><input type="checkbox" name="is_lead" value="1" class="size-4 accent-[var(--color-accent)]">{{ __('admin.branches.is_lead') }}</label>
                        @endcan
                        <x-ui.button type="submit" size="sm" icon="user-plus">{{ __('admin.branches.add_member') }}</x-ui.button>
                    </form>
                </x-admin.card>
            @endif

            @can('branches.manage')
                <x-admin.card :title="__('admin.branches.edit')">
                    <form method="POST" action="{{ route('admin.branches.update', $branch) }}" class="space-y-4">
                        @csrf
                        @include('admin.branches.partials.fields')
                        <x-ui.button type="submit" size="sm" icon="save">{{ __('admin.branches.save') }}</x-ui.button>
                    </form>
                </x-admin.card>
            @endcan
        </div>
    </div>
</x-layouts.admin>
