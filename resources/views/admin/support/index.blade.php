{{-- Destek kuyruğu — docs/SRS.md FR-SUP-007: branş kapsamlı; filtreler: durum, öncelik, kategori, atanan, arama. --}}
<x-layouts.admin :title="__('support.admin.queue')">
    <x-admin.header :title="__('support.admin.queue')">
        <x-slot:actions>
            @can('support.manage')
                <x-ui.button size="sm" variant="secondary" :href="route('admin.support.categories.index')">Kategoriler</x-ui.button>
                <x-ui.button size="sm" variant="secondary" :href="route('admin.support.macros.index')">Makrolar</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <x-ui.realtime-banner :channels="implode(' ', array_map(fn (int $b): string => 'staff.branch.'.$b, $realtimeBranches))" event="support.queue" :message="__('notifications.realtime.queue')" />
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <label class="sr-only" for="status">Durum</label>
        <select id="status" name="status" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="active" @selected($status === 'active')>Aktif (açık)</option>
            @foreach (__('support.status') as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
            <option value="all" @selected($status === 'all')>Tümü</option>
        </select>
        <label class="sr-only" for="assigned">{{ __('support.admin.assigned') }}</label>
        <select id="assigned" name="assigned" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('support.admin.everyone') }}</option>
            <option value="me" @selected($assigned === 'me')>{{ __('support.admin.mine') }}</option>
            <option value="none" @selected($assigned === 'none')>{{ __('support.admin.unassigned') }}</option>
        </select>
        <label class="sr-only" for="category">Kategori</label>
        <select id="category" name="category" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">Tüm kategoriler</option>
            @foreach ($categories as $id => $name)<option value="{{ $id }}" @selected((int) request('category') === $id)>{{ $name }}</option>@endforeach
        </select>
        <label class="sr-only" for="q">Ara</label>
        <input id="q" name="q" value="{{ $q }}" placeholder="Numara (T-000123) veya konu" class="h-10 min-w-56 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
        <x-ui.button type="submit" size="sm" variant="secondary" icon="filter">Filtrele</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">Talep</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('support.admin.player') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Durum</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Öncelik</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('support.admin.branch') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('support.admin.assigned') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('support.admin.last_activity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($tickets as $t)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4">
                                @if ($t->staff_unread)<span class="mr-1 inline-block size-2 rounded-full bg-accent" aria-label="Okunmamış"></span>@endif
                                <a href="{{ route('admin.support.show', $t) }}" class="font-mono text-xs text-text-subtle hover:text-accent">{{ $t->number }}</a>
                                <a href="{{ route('admin.support.show', $t) }}" class="ml-2 font-medium text-text hover:text-accent">{{ \Illuminate\Support\Str::limit($t->subject, 50) }}</a>
                            </td>
                            <td class="px-4 text-text-muted">{{ $t->user->username }}</td>
                            <td class="px-4"><x-ui.badge :tone="in_array($t->status, ['open', 'waiting_for_staff'], true) ? 'accent' : 'neutral'">{{ __('support.status.'.$t->status) }}</x-ui.badge></td>
                            <td class="px-4"><x-ui.badge :tone="match ($t->priority) { 'urgent' => 'danger', 'high' => 'amber', default => 'neutral' }">{{ __('support.priority.'.$t->priority) }}</x-ui.badge></td>
                            <td class="px-4 text-text-muted">{{ $t->branch->name }}</td>
                            <td class="px-4 text-text-muted">{{ $t->assignee?->username ?? __('support.admin.unassigned') }}</td>
                            <td class="px-4 whitespace-nowrap text-text-muted">{{ $t->last_message_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-text-muted">{{ __('support.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layouts.admin>
