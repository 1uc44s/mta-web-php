{{-- Çerez tercihi (docs/SRS.md FR-PUB-020). Analitik yalnızca açık onayla; iki seçenek eşit ağırlıkta sunulur. --}}
<section data-cookie-banner aria-labelledby="cookie-title"
         class="fixed inset-x-4 bottom-4 z-40 mx-auto max-w-xl rounded-md border border-border-strong bg-surface-1 p-5 shadow-2 sm:left-4 sm:mx-0">
    <h2 id="cookie-title" class="font-medium text-text">{{ __('public.cookies.title') }}</h2>
    <p class="mt-1.5 text-sm text-text-muted">
        {{ __('public.cookies.text') }}
        <a href="{{ route('public.legal.cookies') }}" class="text-accent underline underline-offset-2">{{ __('public.cookies.policy') }}</a>
    </p>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach (['0' => 'reject', '1' => 'accept'] as $value => $label)
            <form method="POST" action="{{ route('public.cookie-consent') }}" data-cookie-form>
                @csrf
                <input type="hidden" name="analytics" value="{{ $value }}">
                <input type="hidden" name="return" value="{{ request()->getRequestUri() }}">
                <x-ui.button type="submit" size="sm" variant="secondary">{{ __('public.cookies.'.$label) }}</x-ui.button>
            </form>
        @endforeach
    </div>
</section>
