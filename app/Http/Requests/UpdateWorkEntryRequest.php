<?php

namespace App\Http\Requests;

class UpdateWorkEntryRequest extends WorkEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('work_entry')) ?? false;
    }
}
