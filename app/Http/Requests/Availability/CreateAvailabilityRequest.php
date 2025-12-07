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

            // Validate each day as a key with time slots
            'details.monday' => 'sometimes|array',
            'details.monday.*.start' => 'required|date_format:H:i',
            'details.monday.*.end' => 'required|date_format:H:i|after:details.monday.*.start',

            'details.tuesday' => 'sometimes|array',
            'details.tuesday.*.start' => 'required|date_format:H:i',
            'details.tuesday.*.end' => 'required|date_format:H:i|after:details.tuesday.*.start',

            'details.wednesday' => 'sometimes|array',
            'details.wednesday.*.start' => 'required|date_format:H:i',
            'details.wednesday.*.end' => 'required|date_format:H:i|after:details.wednesday.*.start',

            'details.thursday' => 'sometimes|array',
            'details.thursday.*.start' => 'required|date_format:H:i',
            'details.thursday.*.end' => 'required|date_format:H:i|after:details.thursday.*.start',

            'details.friday' => 'sometimes|array',
            'details.friday.*.start' => 'required|date_format:H:i',
            'details.friday.*.end' => 'required|date_format:H:i|after:details.friday.*.start',

            'details.saturday' => 'sometimes|array',
            'details.saturday.*.start' => 'required|date_format:H:i',
            'details.saturday.*.end' => 'required|date_format:H:i|after:details.saturday.*.start',

            'details.sunday' => 'sometimes|array',
            'details.sunday.*.start' => 'required|date_format:H:i',
            'details.sunday.*.end' => 'required|date_format:H:i|after:details.sunday.*.start',

            'details.date_range' => 'required|array',
            'details.date_range.start' => 'required|date',
            'details.date_range.end' => 'required|date|after_or_equal:details.date_range.start',

            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
        ];
    }
}
