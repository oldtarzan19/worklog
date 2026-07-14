<?php

use App\Exports\DetailsSheet;
use App\Exports\SummarySheet;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;

test('export sheets contain summary and detail rows with excel duration values', function () {
    $user = User::factory()->create(['name' => 'Export Elek']);
    WorkEntry::factory()->for($user)->create(['work_date' => today()->subDay(), 'start_time' => '08:00', 'end_time' => '16:30', 'note' => 'Teszt']);
    $entries = WorkEntry::query()->with('user')->get();
    $service = app(WorkTimeService::class);

    $summary = new SummarySheet($entries, $service);
    $details = new DetailsSheet($entries, $service);

    expect($summary->title())->toBe('Összesítés')
        ->and($summary->array()[0])->toMatchArray(['Export Elek', 1, 510 / 1440, 510 / 1440])
        ->and($summary->columnFormats()['C'])->toBe('[h]:mm')
        ->and($details->title())->toBe('Részletek')
        ->and($details->array()[0][4])->toBe(510 / 1440)
        ->and($details->columnFormats()['E'])->toBe('[h]:mm');
});

test('a user export is restricted to their own records while admins may export all users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    WorkEntry::factory()->for($user)->create(['work_date' => today()->subDay()]);
    WorkEntry::factory()->for($other)->create(['work_date' => today()->subDay()]);

    $this->actingAs($user)->get(route('export.own', ['from' => today()->subWeek()->toDateString(), 'to' => today()->toDateString()]))->assertDownload();
    $this->actingAs($user)->get(route('admin.export'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.export', ['from' => today()->subWeek()->toDateString(), 'to' => today()->toDateString()]))->assertDownload();
});
