<?php

namespace App\Http\Controllers;

use App\Actions\SaveWorkEntry;
use App\Http\Requests\StoreWorkEntryRequest;
use App\Http\Requests\UpdateWorkEntryRequest;
use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkEntryController extends Controller
{
    public function store(StoreWorkEntryRequest $request, SaveWorkEntry $saveWorkEntry): RedirectResponse
    {
        Gate::authorize('create', WorkEntry::class);
        $data = $request->validated();
        $owner = $request->user()->isAdmin() && $request->filled('user_id')
            ? User::query()->findOrFail($request->integer('user_id'))
            : $request->user();
        $saveWorkEntry->create($owner, $data);

        return back()->with('success', 'A munkaidő-bejegyzés létrejött.');
    }

    public function update(UpdateWorkEntryRequest $request, WorkEntry $workEntry, SaveWorkEntry $saveWorkEntry): RedirectResponse
    {
        $data = $request->validated();
        $saveWorkEntry->update($workEntry, $data);

        return back()->with('success', 'A munkaidő-bejegyzés frissült.');
    }

    public function destroy(WorkEntry $workEntry): RedirectResponse
    {
        Gate::authorize('delete', $workEntry);
        $workEntry->delete();

        return back()->with('success', 'A munkaidő-bejegyzés törölve.');
    }
}
