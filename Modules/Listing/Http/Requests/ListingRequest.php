<?php

namespace Modules\Listing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listing\Models\Location;
use Modules\Site\Support\YouTube;

/** The admin form; the same shape creates and updates. */
class ListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::exists('listing_types', 'slug')],
            'title' => ['required', 'string', 'max:200'],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'price' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $this->boolean('call_for_price'))],
            'call_for_price' => ['boolean'],
            'land_area' => ['nullable', 'string', 'max:255'],
            'built_area' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'baths' => ['nullable', 'integer', 'min:0'],
            'floor' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (trim((string) $value) !== '' && YouTube::id($value) === null) {
                    $fail('Enter a YouTube video link.');
                }
            }],
            'features' => ['array'],
            'features.*' => ['integer', Rule::exists('features', 'id')],
            'description' => ['nullable', 'string'],
            'published' => ['boolean'],
            'masterplan_id' => ['nullable', 'integer', 'exists:masterplans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Enter a price or tick Call for Price.',
            'type.exists' => 'Choose one of the listing types.',
            'location_id.required' => 'Choose a location.',
            'location_id.exists' => 'Choose one of the locations.',
        ];
    }

    /** The validated attributes, normalised for the model. */
    public function attributesForModel(): array
    {
        $data = $this->validated();
        $location = Location::query()->findOrFail($data['location_id']);

        return [
            'type' => $data['type'],
            'title' => trim($data['title']),
            'location' => $location->name,
            'location_id' => $location->id,
            'price' => trim((string) ($data['price'] ?? '')),
            'call_for_price' => (bool) ($data['call_for_price'] ?? false),
            'land_area' => trim((string) ($data['land_area'] ?? '')),
            'built_area' => trim((string) ($data['built_area'] ?? '')),
            'size' => trim((string) ($data['size'] ?? '')),
            'bedrooms' => (int) ($data['bedrooms'] ?? 0),
            'baths' => (int) ($data['baths'] ?? 0),
            'floor' => trim((string) ($data['floor'] ?? '')),
            'video_url' => trim((string) ($data['video_url'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'published' => (bool) ($data['published'] ?? false),
            'masterplan_id' => $data['masterplan_id'] ?? null,
        ];
    }

    /** The ids of the ticked features. */
    public function featureIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated()['features'] ?? [])));
    }
}
