@props(['name', 'type' => 'text', 'value' => null, 'hint' => false, 'multiline' => false, 'rows' => 5])
{{-- docs/DESIGN.md §6.1 x-ui.input / textarea. Sınır: border-input (≥ 3:1). --}}
@php
    $id = 'f-'.$name;
    $hasError = $errors->has($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($hasError ? $id.'-error' : ''));
    $classes = 'w-full rounded-sm border bg-surface-2 px-3 py-2.5 text-text placeholder:text-text-subtle transition-colors duration-[var(--dur-fast)] hover:border-text-subtle focus-visible:border-accent '
        .($hasError ? 'border-danger' : 'border-border-input');
@endphp
@if ($multiline)
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class([$classes, 'min-h-32 resize-y']) }}>{{ old($name, $value) }}</textarea>
@else
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class([$classes, 'min-h-11']) }}>
@endif
