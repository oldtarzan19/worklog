<?php

namespace App\Models;

use Database\Factories\WorkEntryFactory;
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
}
