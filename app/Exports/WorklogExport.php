<?php

namespace App\Exports;

use App\Services\WorkTimeService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorklogExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Builder $query,
        private readonly WorkTimeService $workTime,
    ) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->query, $this->workTime),
            new DetailsSheet($this->query, $this->workTime),
        ];
    }
}
