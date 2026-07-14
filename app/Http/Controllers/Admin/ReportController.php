<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(Request $request, WorkTimeService $workTime): Response
    {
        $range = $workTime->range($request);
        $selectedUser = $request->filled('user_id') ? User::query()->findOrFail($request->integer('user_id')) : null;
        $entries = WorkEntry::query()->select(['id', 'user_id', 'work_date', 'start_time', 'end_time', 'note'])
            ->with('user:id,name')
            ->when($selectedUser, fn ($query) => $query->whereBelongsTo($selectedUser))
            ->whereDate('work_date', '>=', $range['from']->toDateString())
            ->whereDate('work_date', '<=', $range['to']->toDateString())
            ->orderByDesc('work_date')->orderBy('start_time')->get();

        return Inertia::render('admin/Reports', [
            'filters' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'user_id' => $selectedUser?->id,
            ],
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'selectedUser' => $selectedUser?->only(['id', 'name', 'email']),
            'dailySummaries' => $workTime->daily($entries),
            'kpis' => $workTime->kpis($entries),
            'entries' => $entries->map(fn (WorkEntry $entry): array => $workTime->serialize($entry)),
            'userSummaries' => $selectedUser ? [] : $this->userSummaries($entries, $workTime),
        ]);
    }

    /** @param Collection<int, WorkEntry> $entries
     * @return array<int, array<string, mixed>>
     */
    private function userSummaries(Collection $entries, WorkTimeService $workTime): array
    {
        return $entries->groupBy('user_id')->map(function (Collection $userEntries) use ($workTime): array {
            return [
                'user_id' => $userEntries->first()->user_id,
                'name' => $userEntries->first()->user->name,
                ...$workTime->kpis($userEntries),
            ];
        })->values()->all();
    }
}
