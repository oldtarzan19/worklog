<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveWorkEntry
{
    /** @param array{work_date: string, start_time: string, end_time: string, note?: string|null, user_id?: int|null} $data */
    public function create(User $owner, array $data): WorkEntry
    {
        return DB::transaction(function () use ($owner, $data): WorkEntry {
            $lockedOwner = User::query()->lockForUpdate()->findOrFail($owner->id);
            unset($data['user_id']);

            $this->ensureDoesNotOverlap($lockedOwner->id, $data);

            return $lockedOwner->workEntries()->create($data);
        }, attempts: 5);
    }

    /** @param array{work_date: string, start_time: string, end_time: string, note?: string|null, user_id?: int|null} $data */
    public function update(WorkEntry $workEntry, array $data): WorkEntry
    {
        return DB::transaction(function () use ($workEntry, $data): WorkEntry {
            User::query()->lockForUpdate()->findOrFail($workEntry->user_id);
            $lockedEntry = WorkEntry::query()->lockForUpdate()->findOrFail($workEntry->id);
            unset($data['user_id']);

            $this->ensureDoesNotOverlap($lockedEntry->user_id, $data, $lockedEntry->id);
            $lockedEntry->update($data);

            return $lockedEntry;
        }, attempts: 5);
    }

    /** @param array{work_date: string, start_time: string, end_time: string, note?: string|null} $data */
    private function ensureDoesNotOverlap(int $userId, array $data, ?int $ignoredEntryId = null): void
    {
        $overlaps = WorkEntry::query()
            ->where('user_id', $userId)
            ->onWorkDate($data['work_date'])
            ->where('start_time', '<', $data['end_time'].':00')
            ->where('end_time', '>', $data['start_time'].':00')
            ->when($ignoredEntryId, fn ($query) => $query->whereKeyNot($ignoredEntryId))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_time' => 'Ez az idősáv átfed egy már rögzített munkaidővel.',
            ]);
        }
    }
}
