<?php

use App\Exports\DetailsSheet;
use App\Exports\SummarySheet;
use App\Exports\WorklogExport;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

test('export sheets contain summary and detail rows with excel duration values', function () {
    $user = User::factory()->create(['name' => 'Export Elek']);
    WorkEntry::factory()->for($user)->create(['work_date' => today()->subDay(), 'start_time' => '08:00', 'end_time' => '16:30', 'note' => 'Teszt']);
    $entriesQuery = WorkEntry::query();
    $service = app(WorkTimeService::class);

    $summary = new SummarySheet($entriesQuery, $service);
    $details = new DetailsSheet($entriesQuery, $service);
    $detail = $details->query()->firstOrFail();

    expect($summary->title())->toBe('Összesítés')
        ->and($summary->array()[0])->toMatchArray(['Export Elek', 1, 510 / 1440, 510 / 1440])
        ->and($summary->columnFormats()['C'])->toBe('[h]:mm')
        ->and($details->title())->toBe('Részletek')
        ->and($details->map($detail)[4])->toBe(510 / 1440)
        ->and($details->columnFormats()['E'])->toBe('[h]:mm');
});

test('exported text is bound as plain text to prevent spreadsheet formulas', function () {
    $service = app(WorkTimeService::class);
    $details = new DetailsSheet(WorkEntry::query(), $service);
    $spreadsheet = new Spreadsheet;
    $cell = $spreadsheet->getActiveSheet()->getCell('A1');

    $details->bindValue($cell, '=HYPERLINK("https://example.com")');

    expect($cell->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($cell->getValue())->toBe('=HYPERLINK("https://example.com")');
});

test('a user export is restricted to their own records while admins may export all users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    WorkEntry::factory()->for($user)->create(['work_date' => today()->subDay()]);
    WorkEntry::factory()->for($other)->create(['work_date' => today()->subDay()]);
    $from = today()->subWeek()->toDateString();
    $to = today()->toDateString();
    $fileName = 'worklog-'.today()->subWeek()->format('Ymd').'-'.today()->format('Ymd').'.xlsx';

    Excel::fake();

    $this->actingAs($user)->get(route('export.own', ['from' => $from, 'to' => $to]))->assertOk();
    Excel::assertDownloaded($fileName, function (WorklogExport $export) use ($user): bool {
        /** @var DetailsSheet $details */
        $details = $export->sheets()[1];

        return $details->query()->get()->pluck('user_id')->unique()->values()->all() === [$user->id];
    });

    $this->actingAs($user)->get(route('admin.export'))->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.export', ['from' => $from, 'to' => $to]))->assertOk();
    Excel::assertDownloaded($fileName, function (WorklogExport $export) use ($other, $user): bool {
        /** @var DetailsSheet $details */
        $details = $export->sheets()[1];
        $userIds = $details->query()->get()->pluck('user_id')->unique()->sort()->values()->all();

        return $userIds === collect([$user->id, $other->id])->sort()->values()->all();
    });
});
