<?php

use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Inertia\Testing\AssertableInertia as Assert;

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
            ->where('entries.0.user_id', $user->id)
            ->where('entries.0.user_name', 'Kovács Anna')
            ->where('entries.0.work_date', $date)
            ->where('entries.1.work_date', $olderDate));

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
