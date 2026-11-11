@props(['items' => []])
{{-- docs/DESIGN.md §6.1 x-ui.breadcrumb. $items: [['label' => …, 'href' => …|null], …] --}}
<nav aria-label="{{ __('public.breadcrumb') }}" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-text-subtle">
        <li><a href="{{ route('public.home') }}" class="hover:text-text">{{ __('public.home') }}</a></li>
        @foreach ($items as $item)
            <li aria-hidden="true" class="text-border-input">/</li>
            <li>
                @if (! empty($item['href']) && ! $loop->last)
                    <a href="{{ $item['href'] }}" class="hover:text-text">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="text-text-muted">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
