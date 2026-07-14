<?php

namespace App\Services;

use App\Models\WorkEntry;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkTimeService
{
    public function minutes(WorkEntry $entry): int
    {
        $start = CarbonImmutable::parse($entry->work_date->toDateString().' '.$entry->start_time);
        $end = CarbonImmutable::parse($entry->work_date->toDateString().' '.$entry->end_time);

        return (int) $start->diffInMinutes($end);
    }

    /** @return array{from: CarbonImmutable, to: CarbonImmutable} */
    public function range(Request $request): array
    {
        $from = $request->date('from')?->toImmutable() ?? now()->startOfMonth()->toImmutable();
        $to = $request->date('to')?->toImmutable() ?? now()->endOfMonth()->toImmutable();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from->startOfDay(), 'to' => $to->endOfDay()];
    }

    /** @param Collection<int, WorkEntry> $entries
     * @return array<int, array{date: string, minutes: int, duration: string}>
     */
    public function daily(Collection $entries): array
    {
        return $entries->groupBy(fn (WorkEntry $entry): string => $entry->work_date->toDateString())
            ->map(function (Collection $day, string $date): array {
                $minutes = $day->sum(fn (WorkEntry $entry): int => $this->minutes($entry));

                return ['date' => $date, 'minutes' => $minutes, 'duration' => $this->format($minutes)];
            })->values()->all();
    }

    /** @param Collection<int, WorkEntry> $entries
     * @return array{total_minutes: int, total_duration: string, workdays: int, average_minutes: int, average_duration: string}
     */
    public function kpis(Collection $entries): array
    {
        $total = $entries->sum(fn (WorkEntry $entry): int => $this->minutes($entry));
        $workdays = $entries->pluck('work_date')->map->toDateString()->unique()->count();
        $average = $workdays > 0 ? (int) round($total / $workdays) : 0;

        return [
            'total_minutes' => $total,
            'total_duration' => $this->format($total),
            'workdays' => $workdays,
            'average_minutes' => $average,
            'average_duration' => $this->format($average),
        ];
    }

    /** @return array<string, mixed> */
    public function serialize(WorkEntry $entry): array
    {
        $minutes = $this->minutes($entry);

        return [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'user_name' => $entry->relationLoaded('user') ? $entry->user?->name : null,
            'work_date' => $entry->work_date->toDateString(),
            'start_time' => substr($entry->start_time, 0, 5),
            'end_time' => substr($entry->end_time, 0, 5),
            'note' => $entry->note,
            'minutes' => $minutes,
            'duration' => $this->format($minutes),
        ];
    }

    public function format(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
