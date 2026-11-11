@props(['html'])
{{-- Sanitize edilmiş Markdown çıktısı. $html bir App\Support\SafeHtml (Htmlable) olmalı; {{ }} kaçışsız basar. --}}
@if (! $html->isEmpty())
    <div {{ $attributes->class(['prose-mrp']) }}>{{ $html }}</div>
@endif
