<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WorkEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkEntry extends Model
{
    /** @use HasFactory<WorkEntryFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'work_date', 'start_time', 'end_time', 'note'];

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOnWorkDate(Builder $query, string $date): Builder
    {
        if ($query->getConnection()->getDriverName() !== 'sqlite') {
            return $query->where('work_date', $date);
        }

        $from = CarbonImmutable::parse($date)->startOfDay();

        return $query
            ->where('work_date', '>=', $from->toDateTimeString())
            ->where('work_date', '<', $from->addDay()->toDateTimeString());
    }

    public function scopeWithinWorkDates(Builder $query, string $from, string $to): Builder
    {
        if ($query->getConnection()->getDriverName() !== 'sqlite') {
            return $query->whereBetween('work_date', [$from, $to]);
        }

        return $query
            ->where('work_date', '>=', CarbonImmutable::parse($from)->startOfDay()->toDateTimeString())
            ->where('work_date', '<', CarbonImmutable::parse($to)->addDay()->startOfDay()->toDateTimeString());
    }
}
