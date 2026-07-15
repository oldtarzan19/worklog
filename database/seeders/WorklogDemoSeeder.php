<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class WorklogDemoSeeder extends Seeder
{
    private const HISTORY_DAYS = 90;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('A demo adatok production környezetben nem tölthetők be.');
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@worklog.test'],
            [
                'name' => 'Worklog Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        foreach ($this->employees() as $employeeIndex => $employee) {
            $user = User::query()->updateOrCreate(
                ['email' => $employee['email']],
                [
                    'name' => $employee['name'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::User,
                    'is_active' => true,
                ],
            );

            $user->workEntries()
                ->withinWorkDates(
                    CarbonImmutable::today()->subDays(self::HISTORY_DAYS)->toDateString(),
                    CarbonImmutable::yesterday()->toDateString(),
                )
                ->delete();

            $this->seedWorkEntries($user, $employee, $employeeIndex);
        }
    }

    /**
     * @param  array{name: string, email: string, start: string, lunch_start: string, lunch_end: string, end: string}  $employee
     */
    private function seedWorkEntries(User $user, array $employee, int $employeeIndex): void
    {
        $notes = [
            'Irodai munkavégzés',
            'Távoli munkavégzés',
            'Ügyfél-egyeztetés és kapcsolattartás',
            'Dokumentáció és adminisztráció',
            'Csapatmegbeszélés és feladatvégzés',
        ];

        foreach (range(1, self::HISTORY_DAYS) as $daysAgo) {
            $workDate = CarbonImmutable::today()->subDays($daysAgo);

            if ($workDate->isWeekend() || ($daysAgo + $employeeIndex) % 11 === 0) {
                continue;
            }

            $note = $notes[($daysAgo + $employeeIndex) % count($notes)];

            $this->updateEntry($user, $workDate, $employee['start'], $employee['lunch_start'], $note);
            $this->updateEntry($user, $workDate, $employee['lunch_end'], $employee['end'], 'Délutáni munkavégzés');
        }
    }

    private function updateEntry(User $user, CarbonImmutable $workDate, string $startTime, string $endTime, string $note): void
    {
        $entry = WorkEntry::query()
            ->whereBelongsTo($user)
            ->onWorkDate($workDate->toDateString())
            ->where('start_time', $startTime)
            ->first();

        $attributes = [
            'user_id' => $user->id,
            'work_date' => $workDate->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'note' => $note,
        ];

        $entry ? $entry->update($attributes) : WorkEntry::query()->create($attributes);
    }

    /**
     * @return array<int, array{name: string, email: string, start: string, lunch_start: string, lunch_end: string, end: string}>
     */
    private function employees(): array
    {
        return [
            [
                'name' => 'Kovács Anna',
                'email' => 'anna.kovacs@worklog.test',
                'start' => '07:30',
                'lunch_start' => '11:45',
                'lunch_end' => '12:15',
                'end' => '15:45',
            ],
            [
                'name' => 'Nagy Balázs',
                'email' => 'balazs.nagy@worklog.test',
                'start' => '08:00',
                'lunch_start' => '12:00',
                'lunch_end' => '12:45',
                'end' => '16:45',
            ],
            [
                'name' => 'Tóth Csilla',
                'email' => 'csilla.toth@worklog.test',
                'start' => '08:30',
                'lunch_start' => '12:30',
                'lunch_end' => '13:00',
                'end' => '17:00',
            ],
            [
                'name' => 'Kiss Dávid',
                'email' => 'david.kiss@worklog.test',
                'start' => '09:00',
                'lunch_start' => '13:00',
                'lunch_end' => '13:30',
                'end' => '17:30',
            ],
        ];
    }
}
