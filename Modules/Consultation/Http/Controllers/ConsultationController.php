<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Consultation\Availability;
use Modules\Consultation\Models\Consultation;
use Modules\Settings\Settings;

/** The admin's bookings list and availability, under /api/admin/consultations. */
class ConsultationController
{
    public const FILTERS = ['upcoming', 'pending', 'past', 'cancelled', 'all'];

    public function index(Request $request): JsonResponse
    {
        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'upcoming';
        $query = Consultation::query()->with('listing');
        match ($filter) {
            'upcoming' => $query->upcoming()->orderBy('scheduled_at'),
            'pending' => $query->where('status', 'pending')->orderBy('scheduled_at'),
            'past' => $query->where('scheduled_at', '<', now())->whereIn('status', ['confirmed', 'completed', 'pending'])->orderByDesc('scheduled_at'),
            'cancelled' => $query->where('status', 'cancelled')->orderByDesc('scheduled_at'),
            default => $query->orderByDesc('scheduled_at'),
        };
        $page = $query->paginate(max(1, min(100, (int) $request->query('per_page', 25))));

        return response()->json([
            'consultations' => collect($page->items())->map(fn (Consultation $c) => $this->row($c)),
            'total' => $page->total(),
            'counts' => self::counts(),
        ]);
    }

    public function update(Request $request, Consultation $consultation): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(Consultation::STATUSES)],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        $consultation->update($data);

        return response()->json(['consultation' => $this->row($consultation->load('listing')), 'counts' => self::counts()]);
    }

    public function destroy(Consultation $consultation): JsonResponse
    {
        $consultation->delete();

        return response()->json(['counts' => self::counts()]);
    }

    public function availability(): JsonResponse
    {
        return response()->json(['availability' => Availability::values(Settings::all()), 'modes' => Availability::MODES]);
    }

    public function saveAvailability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'consult_enabled' => ['required', Rule::in(['0', '1'])],
            'consult_days' => ['required', 'regex:/^[1-7](,[1-7])*$/'],
            'consult_start' => ['required', 'date_format:H:i'],
            'consult_end' => ['required', 'date_format:H:i', 'after:consult_start'],
            'consult_slot_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'consult_lead_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'consult_horizon_days' => ['required', 'integer', 'min:1', 'max:60'],
            'consult_modes' => ['required', 'regex:/^[a-z]+(,[a-z]+)*$/'],
            'consult_note' => ['nullable', 'string', 'max:300'],
        ]);
        $modes = array_values(array_intersect(Availability::MODES, explode(',', $data['consult_modes'])));
        if ($modes === []) {
            return response()->json(['message' => 'Offer at least one way to meet.', 'errors' => ['consult_modes' => ['Offer at least one way to meet.']]], 422);
        }
        $data['consult_modes'] = implode(',', $modes);
        Availability::save(array_map('strval', $data));

        return $this->availability();
    }

    /** The dashboard's numbers: new requests, and everything still ahead. */
    public static function counts(): array
    {
        return [
            'pending' => Consultation::query()->where('status', 'pending')->count(),
            'upcoming' => Consultation::query()->upcoming()->count(),
        ];
    }

    private function row(Consultation $c): array
    {
        return [
            'id' => $c->id,
            'reference' => $c->reference,
            'name' => $c->name,
            'phone' => $c->phone,
            'email' => $c->email,
            'whatsapp_url' => $c->visitorWhatsapp(),
            'mode' => $c->mode,
            'topic' => $c->topic,
            'message' => $c->message,
            'listing' => $c->listing ? ['id' => $c->listing->id, 'title' => $c->listing->title, 'url' => $c->listing->url()] : null,
            'scheduled_at' => $c->scheduled_at->toIso8601String(),
            'date' => $c->startsAt()->format('D j M Y'),
            'time' => $c->startsAt()->format('g:i A').' – '.$c->endsAt()->format('g:i A'),
            'is_past' => $c->scheduled_at->isPast(),
            'duration_minutes' => $c->duration_minutes,
            'status' => $c->status,
            'locale' => $c->locale,
            'source_url' => $c->source_url,
            'admin_notes' => $c->admin_notes,
            'created_at' => $c->created_at->setTimezone(Availability::TIMEZONE)->format('j M Y, g:i A'),
        ];
    }
}
