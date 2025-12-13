<?php

namespace App\Http\Requests\OfferingDay;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferingDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
