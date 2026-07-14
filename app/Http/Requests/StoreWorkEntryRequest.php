<?php

namespace App\Http\Requests;

class StoreWorkEntryRequest extends WorkEntryRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}
