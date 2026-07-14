<?php

namespace App\Exports;

use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailsSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @param Collection<int, WorkEntry> $entries */
    public function __construct(private readonly Collection $entries, private readonly WorkTimeService $workTime) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return $this->entries->map(fn (WorkEntry $entry): array => [
            $entry->user->name,
            $entry->work_date->toDateString(),
            substr($entry->start_time, 0, 5),
            substr($entry->end_time, 0, 5),
            $this->workTime->minutes($entry) / 1440,
            $entry->note,
        ])->all();
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
