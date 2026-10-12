<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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

    /** An .ics the visitor can add to their phone's calendar. */
    public function ics(string $reference): Response
    {
        $c = Consultation::query()->where('reference', $reference)->first() ?? abort(404);
        $site = Site::settings()['site_name'];
        $stamp = fn ($at) => $at->utc()->format('Ymd\THis\Z');
        $escape = fn (string $s) => addcslashes($s, ',;\\');
        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//'.$escape($site).'//Consultation//EN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$c->reference.'@'.parse_url(Site::baseUrl(), PHP_URL_HOST),
            'DTSTAMP:'.$stamp($c->created_at),
            'DTSTART:'.$stamp($c->scheduled_at),
            'DTEND:'.$stamp($c->scheduled_at->addMinutes($c->duration_minutes)),
            'SUMMARY:'.$escape(__('consultation::page.ics_summary', ['site' => $site])),
            'DESCRIPTION:'.$escape(__('consultation::page.ics_description', ['ref' => $c->reference, 'mode' => __('consultation::widget.mode_'.$c->mode)])),
            'URL:'.Site::baseUrl().$c->url(),
            'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="consultation-'.strtolower($c->reference).'.ics"',
        ]);
    }

    /** The booking as the widget's success state and the confirmation page show it. */
    private function summary(Consultation $c): array
    {
        $settings = Site::settings();
        $when = $c->startsAt()->isoFormat('dddd, D MMMM').' · '.$c->startsAt()->isoFormat('h:mm A');

        return [
            'reference' => $c->reference,
            'url' => $c->url(),
            'ics_url' => $c->icsUrl(),
            'date' => $c->startsAt()->isoFormat('dddd, D MMMM YYYY'),
            'time' => $c->startsAt()->isoFormat('h:mm A').' – '.$c->endsAt()->isoFormat('h:mm A'),
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
