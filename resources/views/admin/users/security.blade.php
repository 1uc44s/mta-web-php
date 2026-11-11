<x-layouts.admin :title="__('admin.users.tabs.security').' · '.$user->username">
    <x-admin.header :title="__('admin.users.tabs.security')" :description="$user->username" :back="route('admin.users.show', $user)" :back-label="$user->username" />

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">Zaman</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Olay</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">IP (maskeli)</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Cihaz</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($events as $e)
                        <tr class="h-11">
                            <td class="px-4 whitespace-nowrap text-text-muted">{{ $e->created_at->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 font-mono text-xs text-text">{{ $e->event }}</td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $e->ip_masked ?? __('admin.none') }}</td>
                            <td class="px-4 text-text-muted">{{ $e->user_agent_summary ?? __('admin.none') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-text-muted">{{ __('admin.users.events_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $events->links() }}</div>
</x-layouts.admin>
