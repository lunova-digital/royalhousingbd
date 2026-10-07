<?php

namespace Modules\Masterplan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Masterplan\Models\Plot;

/** The builder's bulk save: every plot of the map, in one request. */
class PlotsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plots' => ['present', 'array'],
            'plots.*.id' => ['nullable', 'integer'],
            'plots.*.number' => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            'plots.*.size' => ['nullable', 'string', 'max:255'],
            'plots.*.price' => ['nullable', 'string', 'max:255'],
            'plots.*.position' => ['nullable', 'string', 'max:255'],
            'plots.*.status' => ['required', Rule::in(Plot::STATUSES)],
            'plots.*.polygon' => ['required', 'array', 'min:3'],
            'plots.*.polygon.*.x' => ['required', 'numeric'],
            'plots.*.polygon.*.y' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'plots.*.number.required' => 'Every plot needs a number.',
            'plots.*.number.distinct' => 'Plot numbers must be unique on a map.',
            'plots.*.status.in' => 'A plot has an unknown status.',
            'plots.*.polygon.min' => 'Every plot needs at least three points.',
            'plots.*.polygon.required' => 'Every plot needs at least three points.',
        ];
    }
}
