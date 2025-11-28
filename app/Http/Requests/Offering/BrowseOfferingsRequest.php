<?php

namespace App\Http\Requests\Offering;

use Illuminate\Foundation\Http\FormRequest;

class BrowseOfferingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1', 'max:10000'],
            'page_size' => ['integer', 'min:1', 'max:200'],
            'search' => ['string', 'max:255'],
            'min_price' => ['numeric', 'min:0'],
            'max_price' => ['numeric', 'min:0', 'gte:min_price'],
            'sort_by' => ['string', 'in:price,created_at,title'],
            'sort_direction' => ['string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price',
            'sort_by.in' => 'Sort by must be one of: price, created_at, title',
            'sort_direction.in' => 'Sort direction must be either asc or desc',
        ];
    }
}
