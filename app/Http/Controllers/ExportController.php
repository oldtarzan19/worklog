<?php

namespace App\Http\Controllers;

use App\Exports\WorklogExport;
use App\Http\Requests\WorklogFilterRequest;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function own(WorklogFilterRequest $request, WorkTimeService $workTime): BinaryFileResponse
    {
        return $this->download($request, $workTime, $request->user());
    }

    public function admin(WorklogFilterRequest $request, WorkTimeService $workTime): BinaryFileResponse
    {
        $user = $request->filled('user_id') ? User::query()->findOrFail($request->integer('user_id')) : null;

        return $this->download($request, $workTime, $user);
    }

    private function download(WorklogFilterRequest $request, WorkTimeService $workTime, ?User $user): BinaryFileResponse
    {
        $range = $workTime->range($request);
        $entries = WorkEntry::query()
            ->when($user, fn ($query) => $query->whereBelongsTo($user))
            ->withinWorkDates($range['from']->toDateString(), $range['to']->toDateString());

        return Excel::download(new WorklogExport($entries, $workTime), 'worklog-'.$range['from']->format('Ymd').'-'.$range['to']->format('Ymd').'.xlsx');
    }
}
