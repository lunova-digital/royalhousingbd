<?php

namespace Modules\Masterplan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Masterplan\Models\Masterplan;
use Modules\Masterplan\Models\Plot;

/** The builder's bulk save: every detailed plot of the map, in one request. */
class PlotsRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Masterplan $masterplan */
        $masterplan = $this->route('masterplan');

        return [
            'plots' => ['present', 'array'],
            'plots.*.svg_key' => ['required', 'string', 'max:100', 'distinct', Rule::in($masterplan->layout_keys ?? [])],
            'plots.*.number' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            'plots.*.size' => ['nullable', 'string', 'max:255'],
            'plots.*.price' => ['nullable', 'string', 'max:255'],
            'plots.*.position' => ['nullable', 'string', 'max:255'],
            'plots.*.status' => ['required', Rule::in(Plot::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'plots.*.svg_key.required' => 'Every plot must belong to a group in the layout.',
            'plots.*.svg_key.in' => 'A plot refers to a group that is not in the layout.',
            'plots.*.svg_key.distinct' => 'A layout group has details more than once.',
            'plots.*.number.required' => 'Every plot needs a number.',
            'plots.*.number.distinct' => 'Plot numbers must be unique on a map.',
            'plots.*.status.in' => 'A plot has an unknown status.',
        ];
    }
}
