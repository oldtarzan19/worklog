<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\WorkEntry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class WorkEntryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $date = (string) $this->input('work_date');
            $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$this->input('start_time'));
            $end = CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$this->input('end_time'));
            $startTime = $this->input('start_time').':00';
            $endTime = $this->input('end_time').':00';

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('end_time', 'A befejezésnek későbbinek kell lennie a kezdésnél, és nem nyúlhat át éjfélen.');
            }

            if ($end->isFuture()) {
                $validator->errors()->add('end_time', 'Jövőbeli munkaidő nem rögzíthető.');
            }

            $userId = $this->user()?->isAdmin() && $this->filled('user_id')
                ? $this->integer('user_id')
                : $this->user()?->getKey();

            $overlap = WorkEntry::query()
                ->where('user_id', $userId)
                ->whereDate('work_date', $date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->when($this->route('work_entry'), fn ($query, WorkEntry $entry) => $query->whereKeyNot($entry->getKey()))
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_time', 'Ez az idősáv átfed egy már rögzített munkaidővel.');
            }
        }];
    }
}
