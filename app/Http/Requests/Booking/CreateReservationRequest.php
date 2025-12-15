<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offering_time_slot_id' => ['required', 'integer', 'exists:offering_time_slots,id'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
