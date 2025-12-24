<?php

namespace App\Http\Requests\OfferingTimeSlot;

use Illuminate\Foundation\Http\FormRequest;

class ListTimeSlotsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'offering_day_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
