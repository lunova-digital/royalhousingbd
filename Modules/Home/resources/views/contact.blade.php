@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('Contact'), null]], 'title' => __('How can we help?'), 'text' => __('Questions about a plot, a flat or a site visit? Message us on WhatsApp or use the form; we usually reply within minutes.')])

<section class="container-x -mt-6">
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['pin', __('Visit us'), $settings['address'] ?? ''], ['phone', __('Call us'), $settings['phone'] ?? ''], ['mail', __('Email us'), $settings['email'] ?? ''], ['clock', __('Working hours'), $settings['working_hours'] ?? '']] as [$icon, $heading, $value])
      @if ($value)
        <div class="card p-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><h3 class="mt-4 font-semibold">{{ $heading }}</h3><p class="mt-1 text-sm text-muted-fg">{{ $value }}</p></div>
      @endif
    @endforeach
  </div>
</section>

<section class="container-x mt-8 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
  {{-- The message form composes a WhatsApp chat (no enquiries are stored, per SRS §9). The field labels travel with it so the message reads in the visitor's language. --}}
  <form data-whatsapp-form data-whatsapp-number="{{ $settings['whatsapp_number'] ?? '' }}" data-whatsapp-labels="{{ json_encode(['subject' => __('Subject'), 'name' => __('Name'), 'phone' => __('Phone'), 'email' => __('Email')], JSON_UNESCAPED_UNICODE) }}" class="card p-5 sm:p-8">
    <h2 class="font-display text-2xl font-bold">{{ __('Send us a message') }}</h2>
    <p class="mt-1 text-sm text-muted-fg">{{ __('Your message opens in WhatsApp so we can reply right away.') }}</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('Full name') }} *</span><input name="name" required class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('Email address') }}</span><input name="email" type="email" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('Phone number') }}</span><input name="phone" type="tel" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('Subject') }} *</span>
        <select name="subject" required class="input"><option value="">{{ __('Select a subject') }}</option>@foreach ([__('Property inquiry'), __('Schedule site visit'), __('Investment consultation'), __('Customer support'), __('Other')] as $subject)<option>{{ $subject }}</option>@endforeach</select>
      </label>
      <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>{{ __('Your message') }} *</span><textarea name="message" rows="6" required class="input h-auto py-3"></textarea></label>
    </div>
    <button type="submit" class="btn-primary mt-6 h-12 w-full rounded-xl text-base">@include('site::partials.icon', ['name' => 'send', 'class' => 'size-4']) {{ __('Send message') }}</button>
  </form>

  <div class="grid content-start gap-4">
    @if ($settings['whatsapp_number'] ?? '')
      <div class="dark-panel flex flex-wrap items-center gap-4 p-6">
        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-whatsapp text-white">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])</span>
        <span class="min-w-0 flex-1"><span class="block font-display text-lg font-bold">{{ __('Chat on WhatsApp') }}</span><span class="block text-sm text-white/65">{{ __('Fastest way to reach us') }}</span></span>
        <a href="{{ $whatsapp }}" target="_blank" rel="noreferrer" class="btn-light h-10 px-4">{{ __('Chat now') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      </div>
    @endif
    @if ($settings['map_embed_url'] ?? '')
      <div class="card overflow-hidden"><h3 class="px-5 pt-4 pb-3 font-semibold">{{ __('Main office') }}</h3><iframe src="{{ $settings['map_embed_url'] }}" class="aspect-[16/9] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ __('Location map') }}" allowfullscreen></iframe></div>
    @endif
    <div class="card p-5">
      <h3 class="font-semibold">{{ __('Why contact us?') }}</h3>
      <ul class="mt-3 grid gap-2.5 text-sm">
        @foreach ([__('Free property consultation'), __('Expert guidance on investments'), __('Personalised site visits'), __('Flexible payment options')] as $item)
          <li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']){{ $item }}</li>
        @endforeach
      </ul>
    </div>
  </div>
</section>
@endsection
