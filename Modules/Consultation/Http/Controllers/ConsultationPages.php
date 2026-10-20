<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Modules\Consultation\Availability;
use Modules\Consultation\Http\Requests\BookingRequest;
use Modules\Consultation\Mail\ConsultationBooked;
use Modules\Consultation\Models\Consultation;
use Modules\Consultation\Services\Scheduler;
use Modules\Listing\Models\ListingType;
use Modules\Site\Site;

/** The public side: the booking page, the slot feed, the booking itself and its confirmation. */
class ConsultationPages
{
    public function index(): View
    {
        return view('consultation::index', Site::page([
            'title' => __('consultation::page.title'),
            'description' => __('consultation::page.description'),
            'nav' => 'contact',
        ], ['availability' => Availability::current()]));
    }

    /** What the widget renders: the next days and their slots, labelled in the visitor's language. */
    public function availability(): JsonResponse
    {
        $scheduler = new Scheduler;
        if (! $scheduler->availability()->enabled) {
            return response()->json(['days' => [], 'modes' => []]);
        }

        return response()->json([
            'timezone' => Availability::TIMEZONE,
            'slot_minutes' => $scheduler->availability()->slotMinutes,
            'modes' => $scheduler->availability()->modes,
            'days' => $scheduler->days(),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(BookingRequest $request): JsonResponse|RedirectResponse
    {
        abort_unless(Availability::enabled(), 404);

        $consultation = $request->scheduler()->book($request->at(), $request->booking());
        if (! $consultation) {
            return $request->expectsJson()
                ? response()->json(['message' => __('consultation::widget.error_taken'), 'errors' => ['time' => [__('consultation::widget.error_taken')]]], 422)
                : back()->withErrors(['time' => __('consultation::widget.error_taken')])->withInput();
        }

        $this->notify($consultation);

        if (! $request->expectsJson()) {
            return redirect($consultation->url());
        }

        return response()->json(['consultation' => $this->summary($consultation)], 201);
    }

    public function show(string $reference): View
    {
        $consultation = Consultation::query()->with('listing')->where('reference', $reference)->first() ?? abort(404);

        return view('consultation::show', Site::page([
            'title' => __('consultation::page.confirmation_title'),
            'nav' => 'contact',
        ], ['consultation' => $consultation, 'summary' => $this->summary($consultation)]));
    }

    /** The booking as the widget's success state and the confirmation page show it. */
    private function summary(Consultation $c): array
    {
        $settings = Site::settings();
        $when = Site::digits($c->startsAt()->isoFormat('dddd, D MMMM')).' · '.Availability::timeLabel($c->startsAt());

        return [
            'reference' => $c->reference,
            'url' => $c->url(),
            'date' => Site::digits($c->startsAt()->isoFormat('dddd, D MMMM YYYY')),
            'time' => Availability::timeLabel($c->startsAt()).' – '.Availability::timeLabel($c->endsAt()),
            'mode' => __('consultation::widget.mode_'.$c->mode),
            'status' => $c->status,
            'whatsapp_url' => ($settings['whatsapp_number'] ?? '') ? Site::whatsapp($settings, __('consultation::page.whatsapp_message', ['ref' => $c->reference, 'when' => $when])) : null,
        ];
    }

    /** Tell the consultant by email when one is set; a mail failure never loses the booking. */
    private function notify(Consultation $consultation): void
    {
        $to = Site::settings()['email'] ?? '';
        if ($to === '') {
            return;
        }
        try {
            Mail::to($to)->send(new ConsultationBooked($consultation));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Topics the widget offers: the listing types plus two general ones. */
    public static function topics(): array
    {
        $types = ListingType::registry()->values()->map(fn (ListingType $t) => $t->name)->all();

        return [...$types, __('consultation::widget.topic_investment'), __('consultation::widget.topic_other')];
    }
}
