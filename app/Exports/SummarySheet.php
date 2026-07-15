<?php

namespace App\Exports;

use App\Services\WorkTimeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet extends SafeValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Builder $query,
        private readonly WorkTimeService $workTime,
        private readonly CarbonImmutable $from,
        private readonly CarbonImmutable $to,
    ) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return collect($this->workTime->userSummariesFromQuery($this->query))
            ->map(fn (array $summary): array => [
                $summary['name'],
                $summary['workdays'],
                $summary['total_minutes'] / 1440,
                $summary['average_minutes'] / 1440,
            ])
            ->all();
    }

    public function headings(): array
    {
        return [
            ['Lefedett időtartam', $this->from->toDateString().' – '.$this->to->toDateString()],
            [],
            ['Felhasználó', 'Munkanapok', 'Teljes idő', 'Napi átlag'],
        ];
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
