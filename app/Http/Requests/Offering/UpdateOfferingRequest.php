<?php

namespace App\Http\Requests\Offering;

use App\Exceptions\Offering\InvalidRequestException;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any null or empty values to allow partial updates
        $filtered = array_filter($this->all(), function ($value) {
            return $value !== null && $value !== '';
        });

        // Check if at least one field is present for update
        $updatableFields = ['title', 'description', 'price', 'address_info'];
        $hasUpdatableField = false;

        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $filtered)) {
                $hasUpdatableField = true;
                break;
            }
        }

        if (!$hasUpdatableField) {
            throw new InvalidRequestException();
        }

        $this->merge($filtered);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // max 2 MB
            'video' => 'sometimes|mimes:mp4,mov,avi,flv,wmv|max:51200', // max 50 MB
            'address_info' => 'sometimes|required|string|max:5000',
        ];
    }
}
