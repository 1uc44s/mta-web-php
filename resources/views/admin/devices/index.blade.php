{{-- Cihaz talepleri — docs/ROUTES.md §6.6 (R2). Ham serial yok; yalnızca parmak izi. --}}
<x-layouts.admin :title="__('game.device.admin_title')">
    <x-admin.header :title="__('game.device.admin_title')" />
    <nav aria-label="Durum" class="mb-4 flex gap-1 border-b border-border">
        @foreach (['pending_approval' => __('game.device.status.pending_approval'), 'active' => __('game.device.status.active'), 'all' => __('admin.all')] as $value => $label)
            <a href="{{ route('admin.devices.index', ['status' => $value]) }}" @if ($status === $value) aria-current="page" @endif
               class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
        @endforeach
    </nav>
    <x-admin.card flush>
        <ul class="divide-y divide-border">
            @forelse ($devices as $d)
                <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <span class="font-medium text-text">{{ $d->account?->account_name }}</span>
                    <span class="font-mono text-xs text-text-subtle">{{ $d->fingerprint() }}</span>
                    <x-ui.badge>{{ __('game.device.status.'.$d->status) }}</x-ui.badge>
                    <span class="text-xs text-text-subtle">{{ $d->created_at?->diffForHumans() }}</span>
                    @if ($d->status === 'pending_approval')
                        @can('devices.approve')
                            <form method="POST" class="ms-auto flex flex-wrap items-center gap-2">
                                @csrf
                                <label for="r-{{ $d->id }}" class="sr-only">{{ __('admin.reason') }}</label>
                                <input id="r-{{ $d->id }}" name="reason" required minlength="5" placeholder="{{ __('admin.reason') }}" class="h-8 w-44 rounded-md border border-border-input bg-surface-2 px-2 text-xs text-text">
                                <x-ui.button type="submit" size="sm" formaction="{{ route('admin.devices.approve', $d->id) }}">{{ __('game.device.approve') }}</x-ui.button>
                                <x-ui.button type="submit" size="sm" variant="ghost" formaction="{{ route('admin.devices.reject', $d->id) }}">{{ __('game.device.reject') }}</x-ui.button>
                            </form>
                        @endcan
                    @endif
                </li>
            @empty
                <li class="px-4 py-10 text-center text-text-muted">{{ __('admin.none') }}</li>
            @endforelse
        </ul>
    </x-admin.card>
    <div class="mt-4">{{ $devices->links() }}</div>
</x-layouts.admin>
