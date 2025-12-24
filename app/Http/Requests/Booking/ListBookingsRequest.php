<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ListBookingsRequest extends FormRequest
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
            'offering_id' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:confirmed,cancelled,completed,no_show'],
            'payment_status' => ['sometimes', 'in:pending,paid,refunded'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'in:created_at,confirmed_at,total_price,status'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'page_size' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }

    /**
     * Get validated data with defaults for pagination.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Set defaults for pagination if not provided
        return array_merge([
            'page' => 1,
            'page_size' => 15,
        ], $validated);
    }
}
