<?php

namespace Modules\Consultation\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Consultation\Availability;
use Modules\Listing\Models\Listing;
use Modules\Site\Support\WhatsApp;

/**
 * One booked free consultation: who, when (stored in UTC, shown in Bangladesh
 * time), how they want to talk and what about. The admin confirms it by
 * phone or WhatsApp and moves it through the statuses.
 */
class Consultation extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    /** Statuses that hold a slot. */
    public const ACTIVE = ['pending', 'confirmed'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            $c->reference ??= self::newReference();
            $c->status ??= 'pending';
        });
    }

    /** A short code the visitor can quote back ("RC-7K3MXQ"); no 0/O or 1/I. */
    public static function newReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = 'RC-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::query()->where('reference', $code)->exists());

        return $code;
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->active()->where('scheduled_at', '>=', now());
    }

    /** The slot in Bangladesh time. */
    public function startsAt(): CarbonImmutable
    {
        return $this->scheduled_at->setTimezone(Availability::TIMEZONE);
    }

    public function endsAt(): CarbonImmutable
    {
        return $this->startsAt()->addMinutes($this->duration_minutes);
    }

    public function url(): string
    {
        return '/consultation/'.$this->reference;
    }

    public function icsUrl(): string
    {
        return $this->url().'/calendar.ics';
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    /** A wa.me link to the visitor, when the number looks like a mobile number. */
    public function visitorWhatsapp(string $text = ''): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->phone);
        if (preg_match('/^0?1\d{9}$/', $digits)) {
            $digits = '880'.ltrim($digits, '0');
        }

        return strlen($digits) >= 10 ? WhatsApp::link($digits, $text) : null;
    }
}
