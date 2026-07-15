<?php

use App\Enums\UserRole;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\WorkEntry;
use Database\Seeders\WorklogDemoSeeder;
use Illuminate\Support\Facades\Hash;

test('demo seeder creates an administrator, four employees, and pending registrations', function () {
    $this->travelTo(now()->setDate(2026, 7, 14)->setTime(18, 0));

    $this->seed(WorklogDemoSeeder::class);

    $admin = User::query()->where('email', 'admin@worklog.test')->firstOrFail();
    $employees = User::query()->where('role', UserRole::User)->withCount('workEntries')->get();
    $inactiveEmployee = User::query()->where('email', 'david.kiss@worklog.test')->firstOrFail();
    $registrationRequests = RegistrationRequest::query()->get();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->is_active)->toBeTrue()
        ->and($employees)->toHaveCount(4)
        ->and($inactiveEmployee->is_active)->toBeFalse()
        ->and($employees->every(fn (User $user): bool => $user->work_entries_count >= 100))->toBeTrue();

    expect($registrationRequests)->toHaveCount(3)
        ->and($registrationRequests->pluck('email')->all())->toEqualCanonicalizing([
            'eszter.szabo@worklog.test',
            'gergo.horvath@worklog.test',
            'lilla.varga@worklog.test',
        ])
        ->and($registrationRequests->every(fn (RegistrationRequest $request): bool => Hash::check('password', $request->password)))->toBeTrue();

    expect($employees->flatMap->workEntries)
        ->each(fn ($entry) => $entry->work_date->toBeBetween(today()->subDays(90), today()->subDay()))
        ->and(WorkEntry::query()->whereDate('work_date', '<', today()->subDays(30))->exists())->toBeTrue();
});

test('demo seeder can be run repeatedly without duplicating work entries', function () {
    $this->seed(WorklogDemoSeeder::class);
    $entryCount = WorkEntry::query()->count();
    $userCount = User::query()->count();
    $registrationRequestCount = RegistrationRequest::query()->count();

    $this->seed(WorklogDemoSeeder::class);

    expect(WorkEntry::query()->count())->toBe($entryCount)
        ->and(User::query()->count())->toBe($userCount)
        ->and(RegistrationRequest::query()->count())->toBe($registrationRequestCount);
});
