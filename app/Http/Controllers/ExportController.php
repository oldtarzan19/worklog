<?php

namespace App\Http\Controllers;

use App\Exports\WorklogExport;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function own(Request $request, WorkTimeService $workTime): BinaryFileResponse
    {
        return $this->download($request, $workTime, $request->user());
    }

    public function admin(Request $request, WorkTimeService $workTime): BinaryFileResponse
    {
        $user = $request->filled('user_id') ? User::query()->findOrFail($request->integer('user_id')) : null;

        return $this->download($request, $workTime, $user);
    }

    private function download(Request $request, WorkTimeService $workTime, ?User $user): BinaryFileResponse
    {
        $range = $workTime->range($request);
        $entries = WorkEntry::query()->select(['id', 'user_id', 'work_date', 'start_time', 'end_time', 'note'])
            ->with('user:id,name')
            ->when($user, fn ($query) => $query->whereBelongsTo($user))
            ->whereDate('work_date', '>=', $range['from']->toDateString())
            ->whereDate('work_date', '<=', $range['to']->toDateString())
            ->orderBy('work_date')->orderBy('start_time')->get();

        return Excel::download(new WorklogExport($entries, $workTime), 'worklog-'.$range['from']->format('Ymd').'-'.$range['to']->format('Ymd').'.xlsx');
    }
}
