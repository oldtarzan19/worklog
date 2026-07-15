<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WorklogFilterRequest extends FormRequest
{
    private const MAX_RANGE_DAYS = 366;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled(['from', 'to'])) {
                return;
            }

            $from = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->string('from'));
            $to = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->string('to'));

            if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
                $validator->errors()->add('to', 'A lekérdezhető időszak legfeljebb 366 nap lehet.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required_with' => 'A kezdő- és záródátumot együtt add meg.',
            'from.date_format' => 'A kezdődátum formátuma éééé-hh-nn legyen.',
            'to.required_with' => 'A kezdő- és záródátumot együtt add meg.',
            'to.date_format' => 'A záródátum formátuma éééé-hh-nn legyen.',
            'to.after_or_equal' => 'A záródátum nem lehet korábbi a kezdődátumnál.',
            'user_id.exists' => 'A kiválasztott felhasználó nem található.',
        ];
    }
}
