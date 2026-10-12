<?php

namespace Modules\Consultation;

use Carbon\CarbonImmutable;
use Modules\Settings\Settings;
use Modules\Site\Site;

/**
 * When the consultant takes free consultations, as the admin set it
 * (Leads → Availability). Everything is in Bangladesh time; the settings
 * store holds the raw values and this applies the defaults.
 */
final class Availability
{
    public const TIMEZONE = 'Asia/Dhaka';

    /** How a consultation can happen; the admin picks which to offer. */
    public const MODES = ['phone', 'whatsapp', 'office', 'video'];

    public const DEFAULTS = [
        'consult_enabled' => '1',
        'consult_days' => '6,7,1,2,3,4', // ISO weekdays: Saturday to Thursday
        'consult_start' => '10:00',
        'consult_end' => '18:00',
        'consult_slot_minutes' => '30',
        'consult_lead_hours' => '3',
        'consult_horizon_days' => '14',
        'consult_modes' => 'phone,whatsapp,office',
        'consult_note' => '',
    ];

    /**
     * @param  list<int>  $days  ISO weekdays (1 = Monday … 7 = Sunday)
     * @param  list<string>  $modes
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly array $days,
        public readonly string $start,
        public readonly string $end,
        public readonly int $slotMinutes,
        public readonly int $leadHours,
        public readonly int $horizonDays,
        public readonly array $modes,
        public readonly string $note,
    ) {}

    public static function current(): self
    {
        return self::fromSettings(Site::settings());
    }

    /** @param  array<string, string>  $settings */
    public static function fromSettings(array $settings): self
    {
        $get = fn (string $key) => trim((string) ($settings[$key] ?? '')) !== '' ? trim((string) $settings[$key]) : self::DEFAULTS[$key];
        $days = array_values(array_filter(array_map('intval', explode(',', $get('consult_days'))), fn (int $d) => $d >= 1 && $d <= 7));
        $modes = array_values(array_intersect(self::MODES, array_map('trim', explode(',', $get('consult_modes')))));

        return new self(
            enabled: $get('consult_enabled') === '1',
            days: array_values(array_unique($days)),
            start: self::time($get('consult_start'), self::DEFAULTS['consult_start']),
            end: self::time($get('consult_end'), self::DEFAULTS['consult_end']),
            slotMinutes: max(10, min(240, (int) $get('consult_slot_minutes'))),
            leadHours: max(0, min(168, (int) $get('consult_lead_hours'))),
            horizonDays: max(1, min(60, (int) $get('consult_horizon_days'))),
            modes: $modes ?: ['phone'],
            note: trim((string) ($settings['consult_note'] ?? '')),
        );
    }

    /** The settings as the admin form edits them (defaults applied). */
    public static function values(array $settings): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = trim((string) ($settings[$key] ?? '')) !== '' ? (string) $settings[$key] : $default;
        }

        return $values;
    }

    /** The on/off switch is read on every public page; the rest only by the scheduler. */
    public static function enabled(): bool
    {
        return (Site::settings()['consult_enabled'] ?? self::DEFAULTS['consult_enabled']) === '1';
    }

    public static function save(array $values): void
    {
        Settings::save(array_intersect_key($values, self::DEFAULTS));
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE);
    }

    /** The first slot a visitor may still book. */
    public function earliest(): CarbonImmutable
    {
        return self::now()->addHours($this->leadHours);
    }

    /** Every slot start on a day (Bangladesh time), bookable or not. */
    public function slotsOn(CarbonImmutable $day): array
    {
        if (! in_array($day->isoWeekday(), $this->days, true)) {
            return [];
        }
        $day = $day->setTimezone(self::TIMEZONE)->startOfDay();
        [$sh, $sm] = array_map('intval', explode(':', $this->start));
        [$eh, $em] = array_map('intval', explode(':', $this->end));
        $end = $day->setTime($eh, $em);
        $slots = [];
        for ($at = $day->setTime($sh, $sm); $at->addMinutes($this->slotMinutes) <= $end; $at = $at->addMinutes($this->slotMinutes)) {
            $slots[] = $at;
        }

        return $slots;
    }

    /** Is this instant a slot the site offers (a working day, on the grid, inside the horizon and lead time)? */
    public function offers(CarbonImmutable $at): bool
    {
        $at = $at->setTimezone(self::TIMEZONE);
        if ($at < $this->earliest() || $at >= self::now()->startOfDay()->addDays($this->horizonDays)) {
            return false;
        }
        foreach ($this->slotsOn($at) as $slot) {
            if ($slot->equalTo($at)) {
                return true;
            }
        }

        return false;
    }

    private static function time(string $value, string $default): string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $default;
    }
}
