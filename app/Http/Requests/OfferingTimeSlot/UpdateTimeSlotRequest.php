<?php

namespace App\Http\Requests\OfferingTimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
