<?php

namespace Modules\Account\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Account\Notifications\MemberResetPassword;
use Modules\Listing\Models\Listing;
use Modules\Site\Site;

/** A public site account (see the members migration). Not the admin. */
class Member extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }

    public function savedListings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'saved_listings')->withTimestamps();
    }

    public function hasPassword(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    /** The name to show: the name, else the email's local part, else the mobile number. */
    public function displayName(): string
    {
        if (trim((string) $this->name) !== '') {
            return trim($this->name);
        }

        return $this->email ? strstr($this->email, '@', true) : (string) $this->phone;
    }

    public function initials(): string
    {
        $words = preg_split('/[\s._-]+/u', $this->displayName(), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters);
    }

    public function avatarUrl(): string
    {
        return $this->avatar ? Site::asset($this->avatar) : '';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new MemberResetPassword($token));
    }
}
