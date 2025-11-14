<?php

namespace App\Http\Requests\Media;

use App\Constants\MediaCollections;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,mp4,mov,avi', 'max:51200'], // max 50MB
            //TODO: rule in
            'entity' => ['required', 'string', 'in:offering'], // only offering for now
            'entity_id' => ['required', 'integer', 'exists:offerings,id'], // must exist
            'collection' => ['required', 'string', Rule::in(MediaCollections::allOfferingCollections())],
        ];
    }

    //TODO: overwrite the request validation response
}
