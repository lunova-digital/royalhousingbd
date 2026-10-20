<?php

namespace Modules\Account\Support\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS (log driver, not delivered)', ['to' => $to, 'message' => $message]);
    }
}
