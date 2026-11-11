@props(['name', 'label', 'hint' => null, 'required' => false])
{{-- docs/DESIGN.md §6.1 x-ui.field: görünür etiket + yardım + hata, aria-describedby ile bağlı (NFR-A11Y-005). --}}
@php
    $id = 'f-'.$name;
    $error = $errors->first($name);
@endphp
<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    <label for="{{ $id }}" class="text-sm font-medium text-text">
        {{ $label }}
        @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only"> (zorunlu)</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-text-subtle">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="flex items-center gap-1.5 text-sm text-danger">
            <x-lucide-circle-alert class="size-4 shrink-0" aria-hidden="true" />{{ $error }}
        </p>
    @endif
</div>
