<?php

namespace App\Http\Requests\Media;

use App\Media\Constants\MediaCollections;
use App\Media\Constants\MediaEntities;
use Illuminate\Foundation\Http\FormRequest;

class CreateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,mp4,mov,avi', 'max:51200'],
            'entity' => ['required', 'string', 'in:'.MediaEntities::allEntitiesList(',')],
            'entity_id' => ['required', 'integer'],
            'collection' => ['required', 'string', 'in:'.MediaCollections::allOfferingCollectionsList(',')],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a file',
            'file.file' => 'The uploaded item must be a valid file',
            'file.mimes' => 'File must be an image (jpeg, png, jpg, gif, svg, webp) or video (mp4, mov, avi)',
            'file.max' => 'File size must not exceed 50MB',

            'entity.required' => 'Entity type is required',
            'entity.in' => 'Invalid entity type selected',

            'entity_id.required' => 'Entity ID is required',
            'entity_id.integer' => 'Entity ID must be a valid number',
            'entity_id.exists' => 'The selected entity does not exist',

            'collection.required' => 'Collection name is required',
            'collection.in' => 'Invalid collection name selected',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'media file',
            'entity' => 'entity type',
            'entity_id' => 'entity identifier',
            'collection' => 'media collection',
        ];
    }
}
