<?php

namespace App\Exports;

use App\Models\WorkEntry;
use App\Services\WorkTimeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorklogExport implements WithMultipleSheets
{
    /** @param Collection<int, WorkEntry> $entries */
    public function __construct(
        private readonly Collection $entries,
        private readonly WorkTimeService $workTime,
    ) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        return [
            new SummarySheet($this->entries, $this->workTime),
            new DetailsSheet($this->entries, $this->workTime),
        ];
    }
}
