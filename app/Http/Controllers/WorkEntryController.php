<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkEntryRequest;
use App\Http\Requests\UpdateWorkEntryRequest;
use App\Models\WorkEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkEntryController extends Controller
{
    public function store(StoreWorkEntryRequest $request): RedirectResponse
    {
        Gate::authorize('create', WorkEntry::class);
        $data = $request->validated();
        $data['user_id'] = $request->user()->isAdmin() && $request->filled('user_id')
            ? $request->integer('user_id')
            : $request->user()->id;
        WorkEntry::query()->create($data);

        return back()->with('success', 'A munkaidő-bejegyzés létrejött.');
    }

    public function update(UpdateWorkEntryRequest $request, WorkEntry $workEntry): RedirectResponse
    {
        $data = $request->validated();
        unset($data['user_id']);
        $workEntry->update($data);

        return back()->with('success', 'A munkaidő-bejegyzés frissült.');
    }

    public function destroy(WorkEntry $workEntry): RedirectResponse
    {
        Gate::authorize('delete', $workEntry);
        $workEntry->delete();

        return back()->with('success', 'A munkaidő-bejegyzés törölve.');
    }
}
