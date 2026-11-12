{{-- Başvuru kuyruğu — docs/ROUTES.md §6.5 (branş kapsamlı). --}}
<x-layouts.admin :title="__('applications.admin.queue')">
    <x-admin.header :title="__('applications.admin.queue')" />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <label for="filter" class="sr-only">{{ __('admin.filter') }}</label>
        <select id="filter" name="filter" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            @foreach (__('applications.admin.filter') as $value => $label)<option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>@endforeach
        </select>
        <label for="type" class="sr-only">{{ __('applications.admin.type') }}</label>
        <select id="type" name="type" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.all') }}</option>
            @foreach ($types as $t)<option value="{{ $t->id }}" @selected($typeId === $t->id)>{{ $t->name }}</option>@endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('applications.admin.applicant') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('applications.admin.type') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('admin.users.columns.status') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('applications.admin.assigned') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('applications.admin.submitted_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($applications as $a)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.applications.show', $a) }}" class="font-medium text-text hover:text-accent">{{ $a->user?->username }}</a></td>
                            <td class="px-4 text-text-muted">{{ $a->type?->name }}</td>
                            <td class="px-4"><x-ui.badge :tone="match ($a->status) { 'approved' => 'success', 'rejected' => 'danger', 'needs_info' => 'amber', 'submitted' => 'accent', default => 'neutral' }">{{ __('applications.status.'.$a->status) }}</x-ui.badge></td>
                            <td class="px-4 text-text-muted">{{ $a->assignee?->username ?? '—' }}</td>
                            <td class="px-4 text-text-muted">{{ $a->submitted_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('applications.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin>
