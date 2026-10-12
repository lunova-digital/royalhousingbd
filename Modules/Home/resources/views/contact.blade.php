@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('site::common.contact'), null]], 'title' => __('home::contact.heading'), 'text' => __('home::contact.intro')])

<section class="container-x -mt-6">
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['pin', __('site::layout.visit_us'), $settings['address'] ?? ''], ['phone', __('site::common.call_us'), $settings['phone'] ?? ''], ['mail', __('home::contact.email_us'), $settings['email'] ?? ''], ['clock', __('home::contact.working_hours'), $settings['working_hours'] ?? '']] as [$icon, $heading, $value])
      @if ($value)
        <div class="card p-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><h3 class="mt-4 font-semibold">{{ $heading }}</h3><p class="mt-1 text-sm text-muted-fg">{{ $value }}</p></div>
      @endif
    @endforeach
  </div>
</section>

<section class="container-x mt-8 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
  {{-- The message form composes a WhatsApp chat (no enquiries are stored, per SRS §9). The field labels travel with it so the message reads in the visitor's language. --}}
  <form data-whatsapp-form data-whatsapp-number="{{ $settings['whatsapp_number'] ?? '' }}" data-whatsapp-labels="{{ json_encode(['subject' => __('home::contact.subject'), 'name' => __('home::contact.name'), 'phone' => __('home::contact.phone'), 'email' => __('site::common.email')], JSON_UNESCAPED_UNICODE) }}" class="card p-5 sm:p-8">
    <h2 class="font-display text-2xl font-bold">{{ __('home::contact.send_message_title') }}</h2>
    <p class="mt-1 text-sm text-muted-fg">{{ __('home::contact.send_message_text') }}</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('home::contact.full_name') }} *</span><input name="name" required class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('home::contact.email_address') }}</span><input name="email" type="email" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('home::contact.phone_number') }}</span><input name="phone" type="tel" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>{{ __('home::contact.subject') }} *</span>
        <select name="subject" required class="input"><option value="">{{ __('home::contact.select_subject') }}</option>@foreach ([__('home::contact.subject_inquiry'), __('home::contact.subject_visit'), __('home::contact.subject_investment'), __('home::contact.subject_support'), __('home::contact.subject_other')] as $subject)<option>{{ $subject }}</option>@endforeach</select>
      </label>
      <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>{{ __('home::contact.your_message') }} *</span><textarea name="message" rows="6" required class="input h-auto py-3"></textarea></label>
    </div>
    <button type="submit" class="btn-primary mt-6 h-12 w-full rounded-xl text-base">@include('site::partials.icon', ['name' => 'send', 'class' => 'size-4']) {{ __('home::contact.send_message') }}</button>
  </form>

  <div class="grid content-start gap-4">
    @if ($settings['whatsapp_number'] ?? '')
      <div class="dark-panel flex flex-wrap items-center gap-4 p-6">
        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-whatsapp text-white">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])</span>
        <span class="min-w-0 flex-1"><span class="block font-display text-lg font-bold">{{ __('site::common.chat_on_whatsapp') }}</span><span class="block text-sm text-white/65">{{ __('home::contact.fastest_way') }}</span></span>
        <a href="{{ $whatsapp }}" target="_blank" rel="noreferrer" class="btn-light h-10 px-4">{{ __('home::contact.chat_now') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      </div>
    @endif
    @if ($settings['map_embed_url'] ?? '')
      <div class="card overflow-hidden"><h3 class="px-5 pt-4 pb-3 font-semibold">{{ __('home::contact.main_office') }}</h3><iframe src="{{ $settings['map_embed_url'] }}" class="aspect-[16/9] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ __('home::contact.location_map') }}" allowfullscreen></iframe></div>
    @endif
    <div class="card p-5">
      <h3 class="font-semibold">{{ __('home::contact.why_title') }}</h3>
      <ul class="mt-3 grid gap-2.5 text-sm">
        @foreach ([__('home::contact.why_consultation'), __('home::contact.why_guidance'), __('home::contact.why_visits'), __('home::contact.why_payment')] as $item)
          <li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']){{ $item }}</li>
        @endforeach
      </ul>
    </div>
  </div>
</section>
@endsection
