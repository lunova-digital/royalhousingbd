{{-- The two calls to action, used everywhere so the site has one WhatsApp button and one consult button.
     @include('site::partials.cta', ['kind' => 'wa' | 'consult', 'class' => '', 'dark' => false, 'ctaLabel' => null]) --}}
@if ($kind === 'wa')
  @if ($settings['whatsapp_number'] ?? '')
    <a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="btn-wa {{ $class ?? '' }}">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ $ctaLabel ?? __('site::layout.chat_whatsapp') }}</a>
  @endif
@elseif (\Modules\Consultation\Availability::enabled() && ! request()->routeIs('consultation.index'))
  @php($fx = $fx ?? null)
  <button type="button" data-consult-open class="btn-consult {{ ($dark ?? false) ? 'on-dark' : '' }} {{ $fx === 'full' ? 'consult-grad consult-glow consult-shine consult-wiggle !h-auto flex-col !gap-0.5 py-3' : ($fx === 'glow' ? 'consult-glow' : '') }} {{ $class ?? '' }}" aria-haspopup="dialog">
    @if ($fx === 'full')
      <span class="relative text-[11px] font-semibold tracking-[0.12em] text-white/85 uppercase">{{ __('home::index.consult_meta', ['minutes' => \Modules\Consultation\Availability::current()->slotMinutes]) }}</span>
      <span class="relative inline-flex items-center gap-2">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ $ctaLabel ?? __('site::layout.book_consultation') }}</span>
    @else
      @include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ $ctaLabel ?? __('site::layout.book_consultation') }}
    @endif
  </button>
@endif
