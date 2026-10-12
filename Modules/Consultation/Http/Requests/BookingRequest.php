<?php

namespace Modules\Consultation\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Consultation\Availability;
use Modules\Consultation\Services\Scheduler;
use Modules\Site\Site;

/** A visitor booking a slot from the widget. */
class BookingRequest extends FormRequest
{
    public function rules(): array
    {
        $availability = Availability::current();

        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()-]{5,}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'mode' => ['required', Rule::in($availability->modes)],
            'topic' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:1000'],
            'listing_id' => ['nullable', 'integer', 'exists:listings,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('consultation::widget.error_phone'),
            'time.required' => __('consultation::widget.error_slot'),
            'date.required' => __('consultation::widget.error_slot'),
            'website.max' => __('consultation::widget.error_slot'),
        ];
    }

    /** The slot must be one the site offers right now. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->hasAny(['date', 'time'])) {
                return;
            }
            if (! Availability::current()->offers($this->at())) {
                $v->errors()->add('time', __('consultation::widget.error_slot'));
            }
        }];
    }

    public function at(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('time'), Availability::TIMEZONE);
    }

    /** What is stored; the slot itself goes through the scheduler. */
    public function booking(): array
    {
        $v = $this->validated();

        return [
            'name' => trim($v['name']),
            'phone' => trim($v['phone']),
            'email' => $v['email'] ?? null ?: null,
            'mode' => $v['mode'],
            'topic' => isset($v['topic']) ? trim($v['topic']) ?: null : null,
            'message' => isset($v['message']) ? trim($v['message']) ?: null : null,
            'listing_id' => $v['listing_id'] ?? null,
            'locale' => Site::locale(app()->getLocale()) ?? 'en',
            'source_url' => mb_substr((string) $this->headers->get('referer'), 0, 500) ?: null,
        ];
    }

    public function scheduler(): Scheduler
    {
        return new Scheduler;
    }
}
