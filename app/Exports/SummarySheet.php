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

class SummarySheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithTitle
{
    /** @param Collection<int, WorkEntry> $entries */
    public function __construct(private readonly Collection $entries, private readonly WorkTimeService $workTime) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return $this->entries->groupBy('user_id')->map(function (Collection $entries): array {
            $kpis = $this->workTime->kpis($entries);

            return [
                $entries->first()->user->name,
                $kpis['workdays'],
                $kpis['total_minutes'] / 1440,
                $kpis['average_minutes'] / 1440,
            ];
        })->values()->all();
    }

    public function headings(): array
    {
        return ['Felhasználó', 'Munkanapok', 'Teljes idő', 'Napi átlag'];
    }

    public function columnFormats(): array
    {
        return ['C' => '[h]:mm', 'D' => '[h]:mm'];
    }

    public function title(): string
    {
        return 'Összesítés';
    }
}
