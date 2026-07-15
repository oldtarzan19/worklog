<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorklogFilterRequest;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(WorklogFilterRequest $request, WorkTimeService $workTime): Response
    {
        $range = $workTime->range($request);
        $selectedUser = $request->filled('user_id') ? User::query()->findOrFail($request->integer('user_id')) : null;
        $query = WorkEntry::query()
            ->when($selectedUser, fn ($query) => $query->whereBelongsTo($selectedUser))
            ->withinWorkDates($range['from']->toDateString(), $range['to']->toDateString());
        $entries = (clone $query)
            ->select(['id', 'user_id', 'work_date', 'start_time', 'end_time', 'note'])
            ->with('user:id,name')
            ->orderByDesc('work_date')
            ->orderBy('start_time')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (WorkEntry $entry): array => $workTime->serialize($entry));
        $calendarEntries = (clone $query)
            ->select(['id', 'user_id', 'work_date', 'start_time', 'end_time', 'note'])
            ->with('user:id,name')
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (WorkEntry $entry): array => $workTime->serialize($entry));

        return Inertia::render('admin/Reports', [
            'filters' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'user_id' => $selectedUser?->id,
            ],
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'selectedUser' => $selectedUser?->only(['id', 'name', 'email']),
            'dailySummaries' => $workTime->dailyFromQuery($query),
            'kpis' => $workTime->kpisFromQuery($query, averagePerUser: $selectedUser === null),
            'entries' => $entries,
            'calendarEntries' => $calendarEntries,
            'userSummaries' => $selectedUser ? [] : $workTime->userSummariesFromQuery($query),
        ]);
    }
}
