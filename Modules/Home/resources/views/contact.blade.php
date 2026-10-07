@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [['Contact', null]], 'title' => 'How can we help?', 'text' => 'Questions about a plot, a flat or a site visit? Message us on WhatsApp or use the form; we usually reply within minutes.'])

<section class="container-x -mt-6">
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['pin', 'Visit us', $settings['address'] ?? ''], ['phone', 'Call us', $settings['phone'] ?? ''], ['mail', 'Email us', $settings['email'] ?? ''], ['clock', 'Working hours', $settings['working_hours'] ?? '']] as [$icon, $heading, $value])
      @if ($value)
        <div class="card p-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><h3 class="mt-4 font-semibold">{{ $heading }}</h3><p class="mt-1 text-sm text-muted-fg">{{ $value }}</p></div>
      @endif
    @endforeach
  </div>
</section>

<section class="container-x mt-8 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
  {{-- The message form composes a WhatsApp chat (no enquiries are stored, per SRS §9). --}}
  <form data-whatsapp-form data-whatsapp-number="{{ $settings['whatsapp_number'] ?? '' }}" class="card p-5 sm:p-8">
    <h2 class="font-display text-2xl font-bold">Send us a message</h2>
    <p class="mt-1 text-sm text-muted-fg">Your message opens in WhatsApp so we can reply right away.</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <label class="grid gap-1.5 text-sm font-medium"><span>Full name *</span><input name="name" required class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>Email address</span><input name="email" type="email" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>Phone number</span><input name="phone" type="tel" class="input" /></label>
      <label class="grid gap-1.5 text-sm font-medium"><span>Subject *</span>
        <select name="subject" required class="input"><option value="">Select a subject</option><option>Property inquiry</option><option>Schedule site visit</option><option>Investment consultation</option><option>Customer support</option><option>Other</option></select>
      </label>
      <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>Your message *</span><textarea name="message" rows="6" required class="input h-auto py-3"></textarea></label>
    </div>
    <button type="submit" class="btn-primary mt-6 h-12 w-full rounded-xl text-base">@include('site::partials.icon', ['name' => 'send', 'class' => 'size-4']) Send message</button>
  </form>

  <div class="grid content-start gap-4">
    @if ($settings['whatsapp_number'] ?? '')
      <div class="dark-panel flex flex-wrap items-center gap-4 p-6">
        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-whatsapp text-white">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])</span>
        <span class="min-w-0 flex-1"><span class="block font-display text-lg font-bold">Chat on WhatsApp</span><span class="block text-sm text-white/65">Fastest way to reach us</span></span>
        <a href="{{ $whatsapp }}" target="_blank" rel="noreferrer" class="btn-light h-10 px-4">Chat now @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      </div>
    @endif
    @if ($settings['map_embed_url'] ?? '')
      <div class="card overflow-hidden"><h3 class="px-5 pt-4 pb-3 font-semibold">Main office</h3><iframe src="{{ $settings['map_embed_url'] }}" class="aspect-[16/9] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Location map" allowfullscreen></iframe></div>
    @endif
    <div class="card p-5">
      <h3 class="font-semibold">Why contact us?</h3>
      <ul class="mt-3 grid gap-2.5 text-sm">
        @foreach (['Free property consultation', 'Expert guidance on investments', 'Personalised site visits', 'Flexible payment options'] as $item)
          <li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']){{ $item }}</li>
        @endforeach
      </ul>
    </div>
  </div>
</section>
@endsection
