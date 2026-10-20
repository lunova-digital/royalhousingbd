<?php

namespace Modules\Account\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberResetPassword extends Notification
{
    public function __construct(public string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/reset-password/'.$this->token.'?email='.urlencode($notifiable->email));

        return (new MailMessage)
            ->subject(__('account::auth.reset_mail_subject'))
            ->line(__('account::auth.reset_mail_intro'))
            ->action(__('account::auth.reset_mail_action'), $url)
            ->line(__('account::auth.reset_mail_outro', ['minutes' => config('auth.passwords.members.expire', 60)]));
    }
}
