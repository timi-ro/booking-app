<?php

namespace App\Http\Requests\OfferingDay;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferingDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
