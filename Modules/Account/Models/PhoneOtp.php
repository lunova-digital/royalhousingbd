<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;

/** A one-time code for a mobile-only account's password reset. */
class PhoneOtp extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }
}
