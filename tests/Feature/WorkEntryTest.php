<?php

use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Inertia\Testing\AssertableInertia as Assert;

test('pagination labels are available in Hungarian', function () {
    expect(__('pagination.previous'))->toBe('Előző')
        ->and(__('pagination.next'))->toBe('Következő');
});

test('users can manage their own work entries but not entries of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $entry = WorkEntry::factory()->for($user)->create(['work_date' => today()->subDay(), 'start_time' => '08:00', 'end_time' => '12:00']);
    $otherEntry = WorkEntry::factory()->for($other)->create(['work_date' => today()->subDay(), 'start_time' => '08:00', 'end_time' => '12:00']);

    $this->actingAs($user)->patch(route('work-entries.update', $entry), ['work_date' => today()->subDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '13:00'])
        ->assertSessionHasNoErrors();
    $this->actingAs($user)->patch(route('work-entries.update', $otherEntry), ['work_date' => today()->subDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '13:00'])
        ->assertForbidden();
    $this->actingAs($user)->delete(route('work-entries.destroy', $otherEntry))->assertForbidden();
});

test('overlapping invalid future and overnight intervals are rejected', function () {
    $user = User::factory()->create();
    $date = today()->subDay()->toDateString();
    WorkEntry::factory()->for($user)->create(['work_date' => $date, 'start_time' => '08:00', 'end_time' => '12:00']);

    $this->actingAs($user)->post(route('work-entries.store'), ['work_date' => $date, 'start_time' => '11:00', 'end_time' => '13:00'])->assertSessionHasErrors('start_time');
    $this->actingAs($user)->post(route('work-entries.store'), ['work_date' => $date, 'start_time' => '12:00', 'end_time' => '13:00'])->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('work-entries.store'), ['work_date' => $date, 'start_time' => '22:00', 'end_time' => '02:00'])->assertSessionHasErrors('end_time');
    $this->actingAs($user)->post(route('work-entries.store'), ['work_date' => today()->addDay()->toDateString(), 'start_time' => '08:00', 'end_time' => '09:00'])->assertSessionHasErrors('end_time');
});

test('work entry notes have a hard limit of 500 characters', function () {
    $user = User::factory()->create();
    $date = today()->subDay()->toDateString();

    $this->actingAs($user)
        ->post(route('work-entries.store'), [
            'work_date' => $date,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'note' => str_repeat('a', 500),
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('work-entries.store'), [
            'work_date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'note' => str_repeat('a', 501),
        ])
        ->assertSessionHasErrors([
            'note' => 'A megjegyzés legfeljebb 500 karakter hosszú lehet.',
        ]);

    expect(WorkEntry::query()->whereBelongsTo($user)->count())->toBe(1)
        ->and(WorkEntry::query()->whereBelongsTo($user)->value('note'))->toHaveLength(500);
});

test('daily totals and dashboard filters are calculated on the backend', function () {
    $user = User::factory()->create();
    $date = today()->subDay();
    WorkEntry::factory()->for($user)->create(['work_date' => $date, 'start_time' => '08:00', 'end_time' => '12:00']);
    WorkEntry::factory()->for($user)->create(['work_date' => $date, 'start_time' => '13:00', 'end_time' => '17:30']);

    $entries = WorkEntry::query()->get();
    expect(app(WorkTimeService::class)->kpis($entries))->toMatchArray(['total_minutes' => 510, 'workdays' => 1, 'average_minutes' => 510]);

    $this->actingAs($user)->get(route('dashboard', ['from' => $date->toDateString(), 'to' => $date->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->where('filters.from', $date->toDateString())->where('kpis.total_minutes', 510)->has('dailySummaries', 1));
});

test('the default worklog range starts with the current month and ends today', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('filters.from', now()->startOfMonth()->toDateString())
            ->where('filters.to', today()->toDateString()));
});

test('admin reports identify entry owners and admins can update their entries from the combined view', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Kovács Anna']);
    $date = today()->subDay()->toDateString();
    $olderDate = today()->subDays(2)->toDateString();
    WorkEntry::factory()->for($user)->create([
        'work_date' => $olderDate,
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);
    $entry = WorkEntry::factory()->for($user)->create([
        'work_date' => $date,
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['from' => $olderDate, 'to' => $date]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reports')
            ->where('selectedUser', null)
            ->where('entries.data.0.user_id', $user->id)
            ->where('entries.data.0.user_name', 'Kovács Anna')
            ->where('entries.data.0.work_date', $date)
            ->where('entries.data.1.work_date', $olderDate)
            ->has('calendarEntries', 2)
            ->where('calendarEntries.0.user_name', 'Kovács Anna')
            ->where('calendarEntries.0.work_date', $olderDate)
            ->where('calendarEntries.1.user_name', 'Kovács Anna')
            ->where('calendarEntries.1.work_date', $date));

    $this->actingAs($admin)
        ->patch(route('work-entries.update', $entry), [
            'user_id' => $user->id,
            'work_date' => $date,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ])
        ->assertSessionHasNoErrors();

    expect($entry->refresh())
        ->start_time->toBe('09:00')
        ->end_time->toBe('13:00');
});

test('combined admin report daily average is calculated per reporting user', function () {
    $admin = User::factory()->admin()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    User::factory()->create();
    $date = today()->subDay()->toDateString();

    WorkEntry::factory()->for($firstUser)->create([
        'work_date' => $date,
        'start_time' => '08:00',
        'end_time' => '12:00',
    ]);
    WorkEntry::factory()->for($secondUser)->create([
        'work_date' => $date,
        'start_time' => '08:00',
        'end_time' => '16:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['from' => $date, 'to' => $date]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedUser', null)
            ->where('kpis.total_minutes', 720)
            ->where('kpis.workdays', 1)
            ->where('kpis.average_minutes', 360)
            ->where('kpis.average_duration', '6:00'));

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['from' => $date, 'to' => $date, 'user_id' => $firstUser->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedUser.id', $firstUser->id)
            ->where('kpis.total_minutes', 240)
            ->where('kpis.average_minutes', 240)
            ->where('kpis.average_duration', '4:00'));
});

test('an administrator cannot spoof the owner to bypass overlap validation', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $date = today()->subDay()->toDateString();
    $entry = WorkEntry::factory()->for($owner)->create(['work_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00']);
    WorkEntry::factory()->for($owner)->create(['work_date' => $date, 'start_time' => '11:00', 'end_time' => '13:00']);

    $this->actingAs($admin)
        ->patch(route('work-entries.update', $entry), [
            'user_id' => $other->id,
            'work_date' => $date,
            'start_time' => '12:00',
            'end_time' => '14:00',
        ])
        ->assertSessionHasErrors('start_time');

    expect($entry->refresh())
        ->user_id->toBe($owner->id)
        ->start_time->toBe('08:00')
        ->end_time->toBe('10:00');
});

test('worklog filters reject malformed reversed and excessively long ranges', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard', ['from' => 'not-a-date', 'to' => today()->toDateString()]))
        ->assertSessionHasErrors('from');

    $this->actingAs($user)
        ->get(route('dashboard', ['from' => today()->toDateString(), 'to' => today()->subDay()->toDateString()]))
        ->assertSessionHasErrors('to');

    $this->actingAs($user)
        ->get(route('dashboard', ['from' => today()->subDays(367)->toDateString(), 'to' => today()->toDateString()]))
        ->assertSessionHasErrors('to');
});

test('administrator report details are paginated', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    foreach (range(1, 51) as $daysAgo) {
        WorkEntry::factory()->for($user)->create([
            'work_date' => today()->subDays($daysAgo),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.reports.index', [
            'from' => today()->subDays(51)->toDateString(),
            'to' => today()->subDay()->toDateString(),
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reports')
            ->where('entries.current_page', 1)
            ->where('entries.total', 51)
            ->where('entries.last_page', 2)
            ->has('entries.data', 50));
});
