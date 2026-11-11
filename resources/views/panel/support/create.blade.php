{{-- Yeni destek talebi — docs/SRS.md FR-SUP-001..003. Gizli idempotency_key çift gönderimi tek talebe indirger. --}}
<x-layouts.panel :title="__('support.create')">
    <div class="mx-auto max-w-2xl">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('support.create') }}</h1>
        <p class="mt-2 text-text-muted">{{ __('support.create_intro') }}</p>

        <form method="POST" action="{{ route('panel.support.store') }}" enctype="multipart/form-data" class="mt-8 space-y-5 rounded-lg border border-border bg-surface-1 p-6">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
            <x-ui.field name="category_id" :label="__('support.category')" required>
                <select id="f-category_id" name="category_id" required class="h-11 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((int) old('category_id') === $c->id)>{{ $c->name }}{{ $c->description ? ' — '.$c->description : '' }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field name="subject" :label="__('support.subject')" required><x-ui.input name="subject" minlength="5" maxlength="120" required /></x-ui.field>
            <x-ui.field name="body" :label="__('support.body')" required><x-ui.input name="body" multiline rows="8" minlength="20" maxlength="5000" required /></x-ui.field>
            <x-ui.field name="attachments" :label="__('support.attachments')">
                <input id="f-attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="block w-full text-sm text-text file:mr-3 file:rounded-md file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-text">
            </x-ui.field>
            <x-ui.button type="submit" icon="send" data-submit-once>{{ __('support.submit') }}</x-ui.button>
        </form>
    </div>
</x-layouts.panel>
