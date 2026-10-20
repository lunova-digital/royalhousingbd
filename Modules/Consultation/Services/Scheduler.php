<?php

namespace Modules\Consultation\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Consultation\Availability;
use Modules\Consultation\Models\Consultation;
use Modules\Site\Site;

/** The slot calendar: what the widget shows, and booking one slot without double-booking it. */
class Scheduler
{
    public function __construct(private ?Availability $availability = null)
    {
        $this->availability ??= Availability::current();
    }

    public function availability(): Availability
    {
        return $this->availability;
    }

    /**
     * The next days the visitor can pick from, each with its slots. Slots that
     * are already past the lead time are left out; taken ones are flagged so
     * the grid still shows the shape of the day.
     *
     * @return list<array{date: string, weekday: string, day: string, month: string, label: string, open: int, slots: list<array{time: string, label: string, taken: bool}>}>
     */
    public function days(): array
    {
        $a = $this->availability;
        $earliest = $a->earliest();
        $first = Availability::now()->startOfDay();
        $taken = $this->takenBetween($first, $first->addDays($a->horizonDays));

        $days = [];
        for ($i = 0; $i < $a->horizonDays; $i++) {
            $day = $first->addDays($i);
            $slots = [];
            foreach ($a->slotsOn($day) as $slot) {
                if ($slot < $earliest) {
                    continue;
                }
                $slots[] = ['time' => $slot->format('H:i'), 'label' => Availability::timeLabel($slot), 'taken' => isset($taken[$slot->format('Y-m-d H:i')])];
            }
            if ($slots === []) {
                continue;
            }
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'weekday' => $day->isoFormat('ddd'),
                'day' => Site::digits($day->format('j')),
                'month' => $day->isoFormat('MMM'),
                'label' => Site::digits($day->isoFormat('dddd, D MMMM')),
                'open' => count(array_filter($slots, fn ($s) => ! $s['taken'])),
                'slots' => $slots,
            ];
        }

        return $days;
    }

    /** Book a slot the availability offers; null when it was taken in the meantime. */
    public function book(CarbonImmutable $at, array $attributes): ?Consultation
    {
        return DB::transaction(function () use ($at, $attributes) {
            if ($this->isTaken($at)) {
                return null;
            }

            return Consultation::query()->create($attributes + [
                'scheduled_at' => $at->utc(),
                'duration_minutes' => $this->availability->slotMinutes,
            ]);
        });
    }

    public function isTaken(CarbonImmutable $at): bool
    {
        return Consultation::query()->active()->where('scheduled_at', $at->utc())->exists();
    }

    /** @return array<string, true> keyed "Y-m-d H:i" in Bangladesh time */
    private function takenBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $taken = [];
        foreach (Consultation::query()->active()->whereBetween('scheduled_at', [$from->utc(), $to->utc()])->pluck('scheduled_at') as $at) {
            $taken[CarbonImmutable::parse($at)->setTimezone(Availability::TIMEZONE)->format('Y-m-d H:i')] = true;
        }

        return $taken;
    }
}
