<?php

namespace App\Http\Requests\Offering;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferingRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'], // max 2 MB
            'video' => ['required', 'mimes:mp4,mov,avi,flv,wmv', 'max:51200'], // max 50 MB
            'address_info' => ['required', 'string', 'max:5000'],
        ];
    }
}
