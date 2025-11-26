<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class CreateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Check if details exists and is an array
            'details' => 'required|array',

            // Validate nested keys inside details
            'details.days' => 'required|array|min:1',
            'details.days.*' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            'details.time_slots' => 'required|array|min:1',
            'details.time_slots.*.start' => 'required|date_format:H:i',
            'details.time_slots.*.end' => 'required|date_format:H:i',

            'details.date_range' => 'required|array',
            'details.date_range.start' => 'required|date',
            'details.date_range.end' => 'required|date|after_or_equal:details.date_range.start',

            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
        ];
    }
}
