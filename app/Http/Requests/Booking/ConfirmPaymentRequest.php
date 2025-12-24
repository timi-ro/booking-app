<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'string'],
            'payment_id' => ['required', 'string'], // Mock payment ID
        ];
    }
}
