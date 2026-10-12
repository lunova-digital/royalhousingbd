<?php

namespace Modules\Consultation\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Consultation\Models\Consultation;
use Modules\Site\Site;

/** Sent to the site's contact email when a visitor books a slot. */
class ConsultationBooked extends Mailable
{
    public function __construct(public Consultation $consultation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New consultation: '.$this->consultation->name.', '.$this->consultation->startsAt()->format('D j M, g:i A'),
            replyTo: $this->consultation->email ? [$this->consultation->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'consultation::mail.booked', with: [
            'c' => $this->consultation->load('listing'),
            'admin_url' => Site::baseUrl().'/admin/consultations',
        ]);
    }
}
