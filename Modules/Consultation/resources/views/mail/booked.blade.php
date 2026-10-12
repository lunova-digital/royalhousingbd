A visitor booked a free consultation on {{ $c->startsAt()->format('l j F Y') }} at {{ $c->startsAt()->format('g:i A') }} (Bangladesh time).

Reference: {{ $c->reference }}
Name: {{ $c->name }}
Phone: {{ $c->phone }}
@if ($c->email)
Email: {{ $c->email }}
@endif
How: {{ __('consultation::widget.mode_'.$c->mode, [], 'en') }}
@if ($c->topic)
Interested in: {{ $c->topic }}
@endif
@if ($c->listing)
Listing: {{ $c->listing->title }} ({{ \Modules\Site\Site::baseUrl().$c->listing->url() }})
@endif
@if ($c->message)

Message:
{{ $c->message }}
@endif

Confirm or reschedule it from the admin: {{ $admin_url }}
