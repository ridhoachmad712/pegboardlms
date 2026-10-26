<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Urutan pengumpulan di Ruang Penilaian: individu urut nama mahasiswa,
 * kelompok urut nama kelompok. Dipakai untuk navigasi sebelumnya/berikutnya.
 */
class GradingQueue
{
    /** @return Collection<int, Submission> */
    public static function for(Assignment $assignment): Collection
    {
        $submissions = $assignment->submissions()
            ->with($assignment->isGroup() ? ['student', 'group.members', 'rubricScores'] : ['student', 'rubricScores'])
            ->get();

        return $submissions
            ->sortBy(fn (Submission $s) => mb_strtolower($assignment->isGroup() ? ($s->group?->name ?? '') : ($s->student?->name ?? '')), SORT_NATURAL)
            ->values();
    }

    public static function label(Submission $submission, Assignment $assignment): string
    {
        return $assignment->isGroup()
            ? ($submission->group?->name ?? 'Kelompok')
            : ($submission->student?->name ?? 'Mahasiswa');
    }

    /**
     * Pengumpulan berikutnya setelah $current. Bila $onlyUngraded, lewati yang sudah dinilai
     * (berputar ke awal daftar). Null bila tidak ada lagi.
     */
    public static function next(Collection $queue, Submission $current, bool $onlyUngraded = false): ?Submission
    {
        $index = $queue->search(fn (Submission $s) => $s->id === $current->id);

        if (! $onlyUngraded) {
            return $index === false ? $queue->first() : $queue->get($index + 1);
        }

        $rotated = $index === false ? $queue : $queue->slice($index + 1)->concat($queue->slice(0, $index));

        return $rotated->first(fn (Submission $s) => $s->id !== $current->id && ! $s->isGraded());
    }

    public static function previous(Collection $queue, Submission $current, bool $onlyUngraded = false): ?Submission
    {
        $index = $queue->search(fn (Submission $s) => $s->id === $current->id);
        if ($index === false) {
            return null;
        }

        return $queue->slice(0, $index)->reverse()->first(fn (Submission $s) => ! $onlyUngraded || ! $s->isGraded());
    }
}
