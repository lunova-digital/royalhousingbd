<?php

namespace Modules\Account\Support\Sms;

/**
 * Sends a text message. The log driver (default) only writes the message to the log, so a mobile-only password reset
 * cannot reach a real phone until an SMS gateway is wired in: implement this interface for the chosen provider and set
 * SMS_DRIVER in .env (see AppServiceProvider / config/services.php).
 */
interface SmsSender
{
    /** @param  string  $to  +8801XXXXXXXXX */
    public function send(string $to, string $message): void;
}
