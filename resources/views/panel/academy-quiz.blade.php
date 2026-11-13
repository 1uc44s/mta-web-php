{{-- Panel: konu sınavı — docs/ROUTES.md §5.3, DATABASE §10.17. Doğru seçenekler yalnızca sınav geçildikten sonra gelir. --}}
<x-layouts.panel :title="$quiz->title">
    <section class="mx-auto max-w-3xl">
        <x-brand.eyebrow>{{ __('academy.quiz.eyebrow') }}</x-brand.eyebrow>
        <h1 class="mt-4 font-display text-h2 font-bold font-expanded text-text">{{ $quiz->title }}</h1>
        <p class="mt-2 text-text-muted">{{ $topic->title }}</p>

        <ul class="mt-4 flex flex-wrap gap-2 text-sm">
            <li><x-ui.badge>{{ __('academy.quiz.pass_rule', ['pass' => $quiz->pass_percent]) }}</x-ui.badge></li>
            @unless ($state['passed'])<li><x-ui.badge>{{ $state['remaining'] === null ? __('academy.quiz.attempts_unlimited') : __('academy.quiz.attempts_left', ['count' => $state['remaining']]) }}</x-ui.badge></li>@endunless
            @if ($state['last'])<li><x-ui.badge>{{ __('academy.quiz.last_score', ['score' => $state['last']->score_percent]) }}</x-ui.badge></li>@endif
        </ul>

        @php($canAttempt = $state['passed'] === null && $state['remaining'] !== 0 && $state['locked_until'] === null)

        @if ($state['passed'])
            <p role="status" class="mt-6 flex items-center gap-2 rounded-md border border-success/40 bg-surface-2 px-4 py-3 text-text">
                <x-lucide-circle-check class="size-5 shrink-0 text-success" aria-hidden="true" />{{ __('academy.quiz.passed_banner', ['score' => $state['passed']->score_percent]) }}
            </p>
        @elseif ($state['remaining'] === 0)
            <p role="status" class="mt-6 rounded-md border border-border bg-surface-2 px-4 py-3 text-text">{{ __('academy.quiz.no_attempts') }}</p>
        @elseif ($state['locked_until'])
            <p role="status" class="mt-6 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-text">{{ __('academy.quiz.locked', ['time' => $state['locked_until']->format('H:i')]) }}</p>
        @endif

        @error('answers')<p role="alert" class="mt-6 text-sm text-danger">{{ $message }}</p>@enderror

        <form method="POST" action="{{ route('panel.academy.quiz.submit', $topic->id) }}" class="mt-8 space-y-6">
            @csrf
            @foreach ($questions as $i => $question)
                <fieldset class="rounded-lg border border-border bg-surface-1 p-5" @disabled(! $canAttempt)>
                    <legend class="px-1 text-sm text-text-subtle">{{ __('academy.quiz.question', ['n' => $i + 1]) }}</legend>
                    <p class="font-medium text-text">{{ $question['body'] }}</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($question['options'] as $option)
                            @php($picked = (int) ($chosen[(string) $question['id']] ?? old('answers.'.$question['id'])) === $option['id'])
                            <label @class(['flex items-center gap-3 rounded-md border px-3 py-2 text-text',
                                           'border-success/60' => $option['correct'] === true,
                                           'border-border' => $option['correct'] !== true])>
                                <input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $option['id'] }}" @checked($picked) @required($canAttempt) class="size-4 accent-[var(--color-accent)]">
                                <span>{{ $option['body'] }}</span>
                                @if ($option['correct'] === true)<span class="ml-auto text-xs text-success">{{ __('academy.quiz.correct') }}</span>@endif
                            </label>
                        @endforeach
                    </div>
                    @error('answers.'.$question['id'])<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach

            <div class="flex flex-wrap gap-3">
                @if ($canAttempt)<x-ui.button type="submit" icon="send">{{ __('academy.quiz.submit') }}</x-ui.button>@endif
                @if ($topic->course)
                    <x-ui.button variant="secondary" :href="route('public.academy.topic', [$topic->course->slug, $topic->slug])">{{ __('academy.quiz.back_to_topic') }}</x-ui.button>
                @endif
            </div>
        </form>
    </section>
</x-layouts.panel>
