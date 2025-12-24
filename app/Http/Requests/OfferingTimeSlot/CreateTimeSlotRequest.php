<?php

namespace App\Http\Requests\OfferingTimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class CreateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offering_day_id' => ['required', 'integer', 'exists:offering_days,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
