<x-layouts.admin :title="__('admin.branches.create')">
    <x-admin.header :title="__('admin.branches.create')" :back="route('admin.branches.index')" :back-label="__('admin.branches.title')" />
    <x-admin.card>
        <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-6">
            @csrf
            @include('admin.branches.partials.fields')
            <x-ui.button type="submit" icon="save">{{ __('admin.branches.save') }}</x-ui.button>
        </form>
    </x-admin.card>
</x-layouts.admin>
