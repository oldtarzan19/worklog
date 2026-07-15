<?php

namespace App\Services;

use App\Http\Requests\WorklogFilterRequest;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkTimeService
{
    public function minutes(WorkEntry $entry): int
    {
        $start = CarbonImmutable::parse($entry->work_date->toDateString().' '.$entry->start_time);
        $end = CarbonImmutable::parse($entry->work_date->toDateString().' '.$entry->end_time);

        return (int) $start->diffInMinutes($end);
    }

    /** @return array{from: CarbonImmutable, to: CarbonImmutable} */
    public function range(WorklogFilterRequest $request): array
    {
        $validated = $request->validated();
        $from = isset($validated['from'])
            ? CarbonImmutable::parse($validated['from'])
            : now()->startOfMonth()->toImmutable();
        $to = isset($validated['to'])
            ? CarbonImmutable::parse($validated['to'])
            : now()->endOfDay()->toImmutable();

        return ['from' => $from->startOfDay(), 'to' => $to->endOfDay()];
    }

    /** @return array<int, array{date: string, minutes: int, duration: string}> */
    public function dailyFromQuery(Builder $query): array
    {
        $dateSql = $this->workDateSql();

        return (clone $query)->toBase()
            ->selectRaw($dateSql.' as work_date')
            ->selectRaw('SUM('.$this->minutesSql().') as minutes')
            ->groupByRaw($dateSql)
            ->orderByRaw($dateSql)
            ->get()
            ->map(function (object $day): array {
                $minutes = (int) $day->minutes;

                return ['date' => (string) $day->work_date, 'minutes' => $minutes, 'duration' => $this->format($minutes)];
            })
            ->all();
    }

    /** @return array{total_minutes: int, total_duration: string, workdays: int, average_minutes: int, average_duration: string} */
    public function kpisFromQuery(Builder $query, bool $averagePerUser = false): array
    {
        $totals = (clone $query)->toBase()
            ->selectRaw('COALESCE(SUM('.$this->minutesSql().'), 0) as total_minutes')
            ->selectRaw('COUNT(DISTINCT '.$this->workDateSql().') as workdays')
            ->selectRaw('COUNT(DISTINCT '.(new WorkEntry)->qualifyColumn('user_id').') as users')
            ->first();
        $total = (int) $totals->total_minutes;
        $workdays = (int) $totals->workdays;
        $userCount = $averagePerUser ? max((int) $totals->users, 1) : 1;
        $average = $workdays > 0 ? (int) round($total / $workdays / $userCount) : 0;

        return [
            'total_minutes' => $total,
            'total_duration' => $this->format($total),
            'workdays' => $workdays,
            'average_minutes' => $average,
            'average_duration' => $this->format($average),
        ];
    }

    /** @return array<int, array{user_id: int, name: string, total_minutes: int, total_duration: string, workdays: int, average_minutes: int, average_duration: string}> */
    public function userSummariesFromQuery(Builder $query): array
    {
        $workEntriesTable = (new WorkEntry)->getTable();
        $usersTable = (new User)->getTable();

        return (clone $query)->toBase()
            ->join($usersTable, $usersTable.'.id', '=', $workEntriesTable.'.user_id')
            ->select([$workEntriesTable.'.user_id', $usersTable.'.name'])
            ->selectRaw('SUM('.$this->minutesSql().') as total_minutes')
            ->selectRaw('COUNT(DISTINCT '.$this->workDateSql().') as workdays')
            ->groupBy($workEntriesTable.'.user_id', $usersTable.'.name')
            ->orderBy($usersTable.'.name')
            ->get()
            ->map(function (object $summary): array {
                $total = (int) $summary->total_minutes;
                $workdays = (int) $summary->workdays;
                $average = $workdays > 0 ? (int) round($total / $workdays) : 0;

                return [
                    'user_id' => (int) $summary->user_id,
                    'name' => (string) $summary->name,
                    'total_minutes' => $total,
                    'total_duration' => $this->format($total),
                    'workdays' => $workdays,
                    'average_minutes' => $average,
                    'average_duration' => $this->format($average),
                ];
            })
            ->all();
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

    private function minutesSql(): string
    {
        $table = (new WorkEntry)->getTable();

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST((strftime('%s', DATE({$table}.work_date) || ' ' || {$table}.end_time) - strftime('%s', DATE({$table}.work_date) || ' ' || {$table}.start_time)) / 60 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(MINUTE, CONCAT({$table}.work_date, ' ', {$table}.start_time), CONCAT({$table}.work_date, ' ', {$table}.end_time))";
    }

    private function workDateSql(): string
    {
        $column = (new WorkEntry)->getTable().'.work_date';

        return DB::connection()->getDriverName() === 'sqlite' ? "DATE({$column})" : $column;
    }
}
