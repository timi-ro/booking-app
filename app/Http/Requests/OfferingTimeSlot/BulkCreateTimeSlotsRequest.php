<?php

namespace App\Http\Requests\OfferingTimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offering_day_id' => ['required', 'integer', 'exists:offering_days,id'],
            'time_slots' => ['required', 'array', 'min:1'],
            'time_slots.*.start_time' => ['required', 'date_format:H:i'],
            'time_slots.*.end_time' => ['required', 'date_format:H:i', 'after:time_slots.*.start_time'],
            'time_slots.*.capacity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'time_slots.*.price_override' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
