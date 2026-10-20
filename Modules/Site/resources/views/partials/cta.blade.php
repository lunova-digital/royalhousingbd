{{-- The two calls to action, used everywhere so the site has one WhatsApp button and one consult button.
     @include('site::partials.cta', ['kind' => 'wa' | 'consult', 'class' => '', 'dark' => false, 'ctaLabel' => null]) --}}
@if ($kind === 'wa')
  @if ($settings['whatsapp_number'] ?? '')
    <a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="btn-wa {{ $class ?? '' }}">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ $ctaLabel ?? __('site::layout.chat_whatsapp') }}</a>
  @endif
@elseif (\Modules\Consultation\Availability::enabled() && ! request()->routeIs('consultation.index'))
  <button type="button" data-consult-open class="btn-consult {{ ($dark ?? false) ? 'on-dark' : '' }} {{ $class ?? '' }}" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ $ctaLabel ?? __('site::layout.book_consultation') }}</button>
@endif
