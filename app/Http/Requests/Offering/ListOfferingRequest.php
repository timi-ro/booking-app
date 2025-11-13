<?php

namespace App\Http\Requests\Offering;

use Illuminate\Foundation\Http\FormRequest;

class ListOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['integer', ',min:1', 'max:10000'],
            'page_size' => ['integer', 'min:1', 'max:200'],
        ];
    }
}
