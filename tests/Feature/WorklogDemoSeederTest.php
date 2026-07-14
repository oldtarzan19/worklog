<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkEntry;
use Database\Seeders\WorklogDemoSeeder;

test('demo seeder creates an administrator and four employees with detailed entries from the last three months', function () {
    $this->travelTo(now()->setDate(2026, 7, 14)->setTime(18, 0));

    $this->seed(WorklogDemoSeeder::class);

    $admin = User::query()->where('email', 'admin@worklog.test')->firstOrFail();
    $employees = User::query()->where('role', UserRole::User)->withCount('workEntries')->get();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and($employees)->toHaveCount(4)
        ->and($employees->every(fn (User $user): bool => $user->work_entries_count >= 100))->toBeTrue();

    expect($employees->flatMap->workEntries)
        ->each(fn ($entry) => $entry->work_date->toBeBetween(today()->subDays(90), today()->subDay()))
        ->and(WorkEntry::query()->whereDate('work_date', '<', today()->subDays(30))->exists())->toBeTrue();
});

test('demo seeder can be run repeatedly without duplicating work entries', function () {
    $this->seed(WorklogDemoSeeder::class);
    $entryCount = WorkEntry::query()->count();

    $this->seed(WorklogDemoSeeder::class);

    expect(WorkEntry::query()->count())->toBe($entryCount);
});
