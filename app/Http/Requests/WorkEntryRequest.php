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
    public const NOTE_MAX_LENGTH = 500;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX_LENGTH],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'note.max' => 'A megjegyzés legfeljebb 500 karakter hosszú lehet.',
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

            $routeEntry = $this->route('work_entry');
            $userId = $routeEntry instanceof WorkEntry
                ? $routeEntry->user_id
                : ($this->user()?->isAdmin() && $this->filled('user_id')
                    ? $this->integer('user_id')
                    : $this->user()?->getKey());

            $overlap = WorkEntry::query()
                ->where('user_id', $userId)
                ->onWorkDate($date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->when($routeEntry instanceof WorkEntry, fn ($query) => $query->whereKeyNot($routeEntry->getKey()))
                ->exists();

            if ($overlap) {
                $validator->errors()->add('start_time', 'Ez az idősáv átfed egy már rögzített munkaidővel.');
            }
        }];
    }
}
