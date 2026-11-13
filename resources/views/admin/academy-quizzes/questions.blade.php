{{-- ACP: sınav soruları — docs/ROUTES.md §6.8, DATABASE §10.17. Doğru seçenek yalnızca personele gösterilir. --}}
<x-layouts.admin :title="__('academy.admin.questions').' — '.$quiz->title">
    <x-admin.header :title="__('academy.admin.questions').' — '.$quiz->title" :description="$quiz->topic?->title"
                    :back="route('admin.academy-quizzes.edit', $quiz->id)" back-label="Sınav" />

    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        <x-admin.card :title="__('academy.admin.questions').' ('.$quiz->questions->count().')'">
            @forelse ($quiz->questions as $i => $question)
                <article class="border-b border-border py-4 first:pt-0 last:border-b-0 last:pb-0">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-medium text-text"><span class="font-mono text-text-subtle">{{ $i + 1 }}.</span> {{ $question->body }}</h3>
                        <form method="POST" action="{{ route('admin.academy-quizzes.questions.destroy', [$quiz->id, $question->id]) }}">@csrf
                            <x-ui.button type="submit" size="sm" variant="ghost" icon="trash-2">{{ __('academy.admin.delete') }}</x-ui.button>
                        </form>
                    </div>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($question->options as $option)
                            <li @class(['flex items-center gap-2', 'text-success' => $option->is_correct, 'text-text-muted' => ! $option->is_correct])>
                                @if ($option->is_correct)<x-lucide-check class="size-4" aria-hidden="true" /><span class="sr-only">{{ __('academy.admin.correct') }}:</span>@else<span class="inline-block size-4" aria-hidden="true"></span>@endif
                                {{ $option->body }}
                            </li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <p class="text-text-muted">{{ __('academy.admin.empty') }}</p>
            @endforelse
        </x-admin.card>

        <x-admin.card :title="__('academy.admin.add_question')">
            <form method="POST" action="{{ route('admin.academy-quizzes.questions.store', $quiz->id) }}" class="space-y-4">
                @csrf
                <x-ui.field name="body" :label="__('academy.admin.question')" required>
                    <x-ui.input name="body" multiline rows="3" maxlength="500" required />
                </x-ui.field>
                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-text">{{ __('academy.admin.options') }}</legend>
                    @for ($n = 0; $n < 6; $n++)
                        <div class="flex items-center gap-2">
                            <input type="radio" name="correct" value="{{ $n }}" id="correct-{{ $n }}" @checked((string) old('correct', '0') === (string) $n) class="size-4 accent-[var(--color-accent)]">
                            <label for="correct-{{ $n }}" class="sr-only">{{ __('academy.admin.correct') }} — {{ __('academy.admin.option', ['n' => $n + 1]) }}</label>
                            <input type="text" name="options[{{ $n }}]" value="{{ old('options.'.$n) }}" maxlength="255" aria-label="{{ __('academy.admin.option', ['n' => $n + 1]) }}"
                                   class="w-full rounded-sm border border-border-input bg-surface-2 px-3 py-2 text-sm text-text">
                        </div>
                    @endfor
                    @error('options')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                    @error('correct')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                    <p class="text-xs text-text-subtle">Soldaki seçim doğru seçeneği belirler. Boş satırlar yok sayılır.</p>
                </fieldset>
                <x-ui.button type="submit" size="sm" icon="plus">{{ __('academy.admin.add_question') }}</x-ui.button>
            </form>
        </x-admin.card>
    </div>
</x-layouts.admin>
