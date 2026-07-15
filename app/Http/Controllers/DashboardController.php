<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorklogFilterRequest;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(WorklogFilterRequest $request, WorkTimeService $workTime): Response
    {
        $range = $workTime->range($request);
        $query = WorkEntry::query()
            ->whereBelongsTo($request->user())
            ->withinWorkDates($range['from']->toDateString(), $range['to']->toDateString());
        $allEntries = (clone $query)->orderBy('work_date')->orderBy('start_time')->get();
        $entries = $query->orderByDesc('work_date')->orderBy('start_time')->paginate(15)->withQueryString();

        return Inertia::render('Dashboard', [
            'filters' => ['from' => $range['from']->toDateString(), 'to' => $range['to']->toDateString()],
            'dailySummaries' => $workTime->daily($allEntries),
            'kpis' => $workTime->kpis($allEntries),
            'calendarEntries' => $allEntries->map(fn (WorkEntry $entry): array => $workTime->serialize($entry)),
            'entries' => $entries->through(fn (WorkEntry $entry): array => $workTime->serialize($entry)),
        ]);
    }
}
