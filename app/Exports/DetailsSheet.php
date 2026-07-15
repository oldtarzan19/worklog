<?php

namespace App\Exports;

use App\Models\User;
use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailsSheet extends SafeValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Builder $entriesQuery, private readonly WorkTimeService $workTime) {}

    public function query(): Builder
    {
        $workEntriesTable = (new WorkEntry)->getTable();
        $usersTable = (new User)->getTable();

        return (clone $this->entriesQuery)
            ->join($usersTable, $usersTable.'.id', '=', $workEntriesTable.'.user_id')
            ->select([$workEntriesTable.'.*', $usersTable.'.name as user_name'])
            ->orderBy($workEntriesTable.'.work_date')
            ->orderBy($workEntriesTable.'.start_time');
    }

    /** @return array<int, mixed> */
    public function map($entry): array
    {
        return [
            $entry->user_name,
            $entry->work_date->toDateString(),
            substr($entry->start_time, 0, 5),
            substr($entry->end_time, 0, 5),
            $this->workTime->minutes($entry) / 1440,
            $entry->note,
        ];
    }

    public function headings(): array
    {
        return ['Felhasználó', 'Dátum', 'Kezdés', 'Befejezés', 'Időtartam', 'Megjegyzés'];
    }

    public function columnFormats(): array
    {
        return ['E' => '[h]:mm'];
    }

    public function title(): string
    {
        return 'Részletek';
    }
}
