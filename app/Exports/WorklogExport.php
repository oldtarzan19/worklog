<?php

namespace App\Exports;

use App\Services\WorkTimeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorklogExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Builder $query,
        private readonly WorkTimeService $workTime,
        private readonly CarbonImmutable $from,
        private readonly CarbonImmutable $to,
    ) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->query, $this->workTime, $this->from, $this->to),
            new DetailsSheet($this->query, $this->workTime),
        ];
    }
}
