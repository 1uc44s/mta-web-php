<?php

declare(strict_types=1);

namespace App\Actions\Panel;

use App\Models\AcademyCourse;
use App\Models\AcademyTopic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Konu "Okudum" — docs/CMS.md §5.2: idempotent; yalnızca yayındaki konu (ve yayındaki ders) tamamlanabilir. */
final class CompleteAcademyTopic
{
    public function handle(User $user, AcademyTopic $topic): void
    {
        $coursePublished = AcademyCourse::query()->published()->whereKey($topic->academy_course_id)->exists();
        abort_unless($topic->isPublished() && $coursePublished, 404);

        DB::table('academy_progress')->insertOrIgnore([
            'user_id' => $user->id,
            'academy_topic_id' => $topic->id,
            'completed_at' => now(),
        ]);
    }

    /**
     * Ders bazında ilerleme: tamamlanan / yayındaki konu (CMS §5.2).
     *
     * @return list<array{course: AcademyCourse, done: int, total: int}>
     */
    public function progress(User $user): array
    {
        $completed = DB::table('academy_progress')->where('user_id', $user->id)->pluck('academy_topic_id')->map(fn ($id): int => (int) $id)->all();

        return AcademyCourse::query()->published()->orderBy('sort')
            ->with(['topics' => fn ($q) => $q->published()->select(['id', 'academy_course_id', 'slug', 'title', 'sort'])])
            ->get()
            ->map(fn (AcademyCourse $c): array => [
                'course' => $c,
                'done' => $c->topics->whereIn('id', $completed)->count(),
                'total' => $c->topics->count(),
            ])->all();
    }
}
