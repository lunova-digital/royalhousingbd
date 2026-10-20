@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('site::common.contact'), null]], 'title' => __('home::contact.heading'), 'text' => __('home::contact.intro')])

<section class="container-x grid gap-6 pt-8 pb-16 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-8 lg:pb-24">
  {{-- One card: how to reach us, the map, and the two ways to start --}}
  <div class="grid content-start gap-6">
    <div class="card p-6">
      <dl class="grid gap-5">
        @foreach ([['phone', __('site::layout.hotline'), $settings['phone'] ?? '', 'tel:'.($settings['phone'] ?? '')], ['mail', __('home::contact.email_us'), $settings['email'] ?? '', 'mailto:'.($settings['email'] ?? '')], ['pin', __('site::layout.visit_us'), $settings['address'] ?? '', null], ['clock', __('home::contact.working_hours'), $settings['working_hours'] ?? '', null]] as [$icon, $heading, $value, $href])
          @if ($value)
            <div class="flex gap-4"><span class="mt-0.5 text-accent-text">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-6'])</span><div class="min-w-0"><dt class="text-sm text-muted-fg">{{ $heading }}</dt><dd class="mt-0.5 font-medium text-fg">@if ($href)<a href="{{ $href }}" class="hover:underline">{{ $value }}</a>@else{{ $value }}@endif</dd></div></div>
          @endif
        @endforeach
      </dl>
      <div class="mt-6 flex flex-col gap-3 border-t border-line pt-6 sm:flex-row lg:flex-col xl:flex-row">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'w-full'])@include('site::partials.cta', ['kind' => 'consult', 'class' => 'w-full'])</div>
    </div>
    @if ($settings['map_embed_url'] ?? '')
      <div class="card overflow-hidden"><iframe src="{{ $settings['map_embed_url'] }}" class="aspect-[4/3] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ __('home::contact.location_map') }}" allowfullscreen></iframe></div>
    @endif
  </div>

  {{-- The message form composes a WhatsApp chat (no enquiries are stored, per SRS §9). The field labels travel with it so the message reads in the visitor's language. --}}
  <form data-whatsapp-form data-whatsapp-number="{{ $settings['whatsapp_number'] ?? '' }}" data-whatsapp-labels="{{ json_encode(['subject' => __('home::contact.subject'), 'name' => __('home::contact.name'), 'phone' => __('home::contact.phone'), 'email' => __('site::common.email')], JSON_UNESCAPED_UNICODE) }}" class="card h-fit p-6 sm:p-8">
    <h2 class="font-display text-2xl text-fg">{{ __('home::contact.send_message_title') }}</h2>
    <p class="mt-1 text-sm text-muted-fg">{{ __('home::contact.send_message_text') }}</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('home::contact.full_name') }} *</span><input name="name" required autocomplete="name" class="input h-12" /></label>
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('home::contact.email_address') }}</span><input name="email" type="email" autocomplete="email" class="input h-12" /></label>
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('home::contact.phone_number') }}</span><input name="phone" type="tel" autocomplete="tel" class="input h-12" /></label>
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('home::contact.subject') }} *</span>
        <select name="subject" required class="input h-12"><option value="">{{ __('home::contact.select_subject') }}</option>@foreach ([__('home::contact.subject_inquiry'), __('home::contact.subject_visit'), __('home::contact.subject_investment'), __('home::contact.subject_support'), __('home::contact.subject_other')] as $subject)<option>{{ $subject }}</option>@endforeach</select>
      </label>
      <label class="grid gap-1.5 text-sm font-medium text-fg sm:col-span-2"><span>{{ __('home::contact.your_message') }} *</span><textarea name="message" rows="6" required class="input h-auto py-3"></textarea></label>
    </div>
    <button type="submit" class="btn-wa mt-6 w-full">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ __('home::contact.send_message') }}</button>
  </form>
</section>
@endsection
