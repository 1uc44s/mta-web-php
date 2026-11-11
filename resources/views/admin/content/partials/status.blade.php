{{-- Yayın durumu rozeti (renk + metin; renk tek başına anlam taşımaz). --}}
@php $value = $status instanceof \BackedEnum ? $status->value : (string) $status; @endphp
<x-ui.badge :tone="match ($value) { 'published' => 'success', 'scheduled' => 'info', 'in_review' => 'amber', default => 'neutral' }">
    {{ __('content_admin.status.'.$value) }}
</x-ui.badge>
