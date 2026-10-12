@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($stats = \Modules\Site\Site::list('hero_stats'))
@php($whatsapp = $settings['whatsapp_number'] ?? '')
@php($phone = $settings['phone'] ?? '')
@php($hero = $featured[0] ?? $latest[0] ?? null)
@php($totals = ['available' => 0, 'booked' => 0, 'sold' => 0])
@foreach ($maps as $map)@php($c = $map->statusCounts())@php($totals = ['available' => $totals['available'] + $c['available'], 'booked' => $totals['booked'] + $c['booked'], 'sold' => $totals['sold'] + $c['sold']])@endforeach

{{-- FR-HOME-003: announcement ticker --}}
@if ($ticker->count())
<div class="border-b border-brand/10 bg-brand-50">
  <div class="ticker container-x overflow-hidden py-2 text-sm font-medium text-fg">
    <div class="ticker-track flex w-max gap-12 whitespace-nowrap">
      @for ($pass = 0; $pass < 2; $pass++)
        @foreach ($ticker as $item)
          @if ($item->link)<a href="{{ $item->link }}" class="inline-flex items-center gap-2 hover:text-brand"><span class="size-1.5 rounded-full bg-brand"></span>{{ $item->text }}</a>
          @else<span class="inline-flex items-center gap-2"><span class="size-1.5 rounded-full bg-brand"></span>{{ $item->text }}</span>@endif
        @endforeach
      @endfor
    </div>
  </div>
</div>
@endif

{{-- FR-HOME-001: hero — dark panel + two pastel tiles --}}
<section class="container-x pt-4 sm:pt-6">
  <div class="grid gap-4 lg:grid-cols-[1fr_320px] lg:gap-5">
    <div class="dark-panel relative overflow-hidden">
      @if ($settings['hero_image'] ?? '')<img src="{{ $asset($settings['hero_image']) }}" alt="" class="absolute inset-0 size-full object-cover opacity-20" />@endif
      <div class="relative grid gap-8 p-6 sm:p-10 lg:grid-cols-[1.15fr_1fr] lg:items-center lg:p-12">
        <div>
          @if ($settings['hero_badge'] ?? '')<span class="pill-tag"><span class="size-1.5 rounded-full bg-brand"></span>{{ $settings['hero_badge'] }}</span>@endif
          <h1 class="mt-5 font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-balance sm:text-5xl xl:text-[52px]">{{ $settings['hero_title'] }}</h1>
          @if ($settings['hero_subtitle'] ?? '')<p class="mt-4 max-w-md text-base text-white/70 sm:text-lg">{{ $settings['hero_subtitle'] }}</p>@endif
          @if ($stats)
            <dl class="mt-7 grid grid-cols-3 gap-3 sm:flex sm:gap-0 sm:divide-x sm:divide-white/15">
              @foreach ($stats as $stat)
                <div class="sm:px-5 sm:first:pl-0"><dt class="font-display text-xl font-extrabold leading-none sm:text-[26px]">{{ $stat['value'] ?? '' }}</dt><dd class="mt-1.5 text-[10px] font-semibold uppercase tracking-wider text-white/55 sm:text-[11px]">{{ $stat['label'] ?? '' }}</dd></div>
              @endforeach
            </dl>
          @endif
          <div class="mt-8 flex flex-wrap gap-3">
            @if ($settings['hero_cta_text'] ?? '')<a href="{{ $settings['hero_cta_link'] ?: '/projects' }}" class="btn-light h-12 pr-2 pl-6 text-base">{{ $settings['hero_cta_text'] }} <span class="btn-arrow">@include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</span></a>@endif
            <a href="/map" class="btn-ghost h-12 px-6 text-base">{{ __('Browse project map') }}</a>
          </div>
        </div>

        {{-- Spotlight card: the first featured listing --}}
        @if ($hero)
          @php($cover = $hero->cover())
          @php($avail = $hero->availability())
          <a href="{{ $hero->url() }}" class="group mx-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white p-2.5 text-fg shadow-2xl shadow-black/40">
            <div class="relative aspect-square overflow-hidden rounded-xl bg-fill">
              @if ($cover)<img src="{{ $asset($cover->thumb_path ?: $cover->path) }}" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />@endif
              <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold shadow-sm">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3.5 text-success']) {{ $avail ? trans_choice(':count plot available|:count plots available', $avail['available']) : __('Featured :type', ['type' => $hero->typeLabel()]) }}</span>
            </div>
            <div class="mt-2.5 flex items-center justify-between gap-3 rounded-xl bg-ink-950 p-4 text-white">
              <span class="min-w-0"><span class="block truncate font-semibold">{{ $hero->title }}</span><span class="mt-1 block font-display text-xl font-extrabold">{{ $hero->priceLabel() }}</span></span>
              <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-brand">@include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-5'])</span>
            </div>
          </a>
        @endif
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 lg:gap-5">
      <a href="/projects" class="group relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-[#ffd6de] to-[#ffeef2] p-6 text-fg">
        <span class="absolute top-6 right-6 inline-flex size-16 items-center justify-center rounded-2xl bg-brand text-white shadow-lg shadow-brand/30">@include('site::partials.icon', ['name' => 'shield-check', 'class' => 'size-7'])</span>
        <span class="pr-20"><span class="block font-display text-xl font-extrabold leading-tight">{{ __('100% Legal Assurance') }}</span><span class="mt-1 block text-sm text-fg/70">{{ __('Verified documents on every plot') }}</span></span>
        <span class="btn-light mt-8 h-10 w-fit px-4 text-sm">{{ __('View listings') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-3.5'])</span>
      </a>
      <a href="/map" class="group relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-[#d7e6ff] to-[#edf3ff] p-6 text-fg">
        <span class="absolute top-6 right-6 inline-flex size-16 items-center justify-center rounded-2xl bg-info text-white shadow-lg shadow-info/30">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-7'])</span>
        <span class="pr-20"><span class="block font-display text-xl font-extrabold leading-tight">{{ __('Interactive Project Map') }}</span><span class="mt-1 block text-sm text-fg/70">{{ __('Live plot availability, tap any plot') }}</span></span>
        <span class="btn-light mt-8 h-10 w-fit px-4 text-sm">{{ __('Explore map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-3.5'])</span>
      </a>
    </div>
  </div>
</section>

{{-- Live availability band --}}
@if ($maps->count())
<section class="container-x mt-6 sm:mt-8">
  <div class="sale-band flex flex-col gap-6 p-5 sm:p-6 lg:flex-row lg:items-center lg:gap-10">
    <div class="flex items-center gap-4">
      <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand text-white">@include('site::partials.icon', ['name' => 'bolt', 'class' => 'size-6'])</span>
      <span><span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('Selling fast') }} · {{ trans_choice(':count project|:count projects', $maps->count()) }}</span><span class="block font-display text-2xl font-bold">{{ __('Plots available now') }}</span></span>
    </div>
    <p class="max-w-sm text-sm text-white/70">{{ __('Live status straight from our project maps. Tap a plot to see its size and price, then book over WhatsApp.') }}</p>
    <div class="flex flex-wrap items-center gap-3 lg:ml-auto">
      <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('Right now') }}</span>
      @foreach ([['available', __('Open')], ['booked', __('Booked')], ['sold', __('Sold')]] as [$key, $label])
        <span class="flex min-w-16 flex-col items-center rounded-xl bg-white/10 px-3 py-2"><span class="font-display text-xl font-extrabold leading-none">{{ $totals[$key] }}</span><span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</span></span>
      @endforeach
      <a href="/map" class="btn-light ml-1 h-11 px-5">{{ __('View map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
    </div>
  </div>
</section>
@endif

{{-- FR-HOME-002: featured listings --}}
@if (count($featured))
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('Featured projects'), 'title' => __('Explore our premium projects'), 'text' => __('Carefully planned housing with modern amenities, strategic locations and strong investment potential.'), 'action' => [__('View all'), '/projects']])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach (array_slice($featured, 0, 8) as $listing)@include('listing::card', ['listing' => $listing])@endforeach
  </div>
</section>
@endif

{{-- Project maps as a tile grid --}}
@if ($maps->count())
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('Buy with confidence'), 'title' => __('Projects · Interactive maps'), 'text' => __('Every project comes with a live masterplan so you can pick your plot and check its status before you visit.'), 'action' => [__('Browse all maps'), '/map']])
  <div class="card mt-6 p-3 sm:p-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      @foreach ($maps as $map)
        @php($c = $map->statusCounts())
        <a href="{{ $map->url() }}" class="tile flex min-h-24 flex-col items-center justify-center gap-1.5 p-4 text-center transition-colors hover:bg-brand-50">
          <span class="font-display text-sm font-bold text-fg">{{ $map->name }}</span>
          <span class="text-xs font-semibold text-success">{{ __(':available of :total available', ['available' => $c['available'], 'total' => $map->plots->count()]) }}</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- Newest listings --}}
@if (count($latest))
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('Just listed'), 'title' => __('New arrivals'), 'text' => __('The latest plots, flats and properties added to our catalogue.'), 'action' => [__('View all'), '/projects']])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($latest as $listing)@include('listing::card', ['listing' => $listing])@endforeach
  </div>
</section>
@endif

{{-- Trust strip --}}
<section class="container-x mt-10 sm:mt-12">
  <div class="card grid grid-cols-1 divide-y divide-line sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
    @foreach ([['pin', __('Prime locations')], ['shield-check', __('100% legal documents')], ['refresh', __('Flexible payment plans')], ['wallet', __('Book over WhatsApp')]] as [$icon, $label])
      <div class="flex items-center gap-4 px-6 py-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><span class="font-semibold">{{ $label }}</span></div>
    @endforeach
  </div>
</section>

{{-- Investor CTA --}}
<section class="container-x mt-10 sm:mt-12">
  <div class="flex flex-col gap-6 rounded-3xl bg-fill p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <span class="pill-tag-light">@include('site::partials.icon', ['name' => 'star', 'class' => 'size-3.5']) {{ __('For investors') }}</span>
      <h2 class="mt-4 font-display text-2xl font-bold sm:text-[26px]">{{ __('Book a free site visit') }}</h2>
      <p class="mt-2 max-w-lg text-sm text-muted-fg sm:text-base">{{ __('Walk the project with our team, check the plot on the map and get the full document set before you commit.') }}</p>
    </div>
    <a href="/contact" class="btn-dark h-12 px-6 text-base">{{ __('Schedule a visit') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
  </div>
</section>

{{-- Dark contact band --}}
<section class="container-x mt-6">
  <div class="dark-panel flex flex-col gap-8 p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between lg:p-12">
    <div class="max-w-xl">
      <span class="pill-tag">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-3.5']) {{ __('Get in touch') }}</span>
      <h2 class="mt-4 font-display text-2xl font-bold sm:text-3xl">{{ __('Reach us on WhatsApp. We reply within minutes.') }}</h2>
      <p class="mt-3 text-sm text-white/65 sm:text-base">{{ __('Ask about any listing, request documents or book a site visit from your phone.') }}</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row">
      @if ($whatsapp)<a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-3 rounded-xl bg-white px-5 py-3 text-fg">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])<span><span class="block text-[10px] font-semibold uppercase tracking-wider text-muted-fg">{{ __('Chat on') }}</span><span class="block font-bold">{{ __('WhatsApp') }}</span></span></a>@endif
      @if ($phone)<a href="tel:{{ $phone }}" class="inline-flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-white">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-6'])<span><span class="block text-[10px] font-semibold uppercase tracking-wider text-white/50">{{ __('Call us') }}</span><span class="block font-bold">{{ $phone }}</span></span></a>@endif
    </div>
  </div>
</section>

{{-- FR-HOME-004: YouTube showcase --}}
@if ($videos->count())
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('Videos'), 'title' => __('See our projects in action'), 'text' => __('Walkthroughs and progress updates from the ground.')])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($videos as $video)
      @if ($id = $video->youtubeId())
        <figure>
          @include('site::partials.youtube', ['id' => $id])
          @if ($video->title)<figcaption class="mt-2 text-sm font-medium text-fg">{{ $video->title }}</figcaption>@endif
        </figure>
      @endif
    @endforeach
  </div>
</section>
@endif

{{-- Why choose us --}}
<section class="container-x mt-12 sm:mt-16">
  <div class="card relative overflow-hidden p-6 sm:p-10 lg:p-12">
    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand via-brand/60 to-ink-950"></span>
    <span class="pill-tag-light"><span class="size-1.5 rounded-full bg-brand"></span>{{ __('Why choose us') }}</span>
    <h2 class="mt-5 max-w-3xl font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Buying land in Bangladesh, made simple') }}</h2>
    <p class="mt-4 max-w-3xl text-base leading-relaxed text-muted-fg sm:text-lg">{!! __(':site develops residential plots, flats and properties with verified ownership, planned infrastructure and clear pricing. Pick a plot on the live map, ask for the documents, and :cta.', ['site' => e($site_name), 'cta' => '<strong class="font-semibold text-fg">'.__('book your site visit over WhatsApp').'</strong>']) !!}</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ([
        ['shield-check', __('100% legal assurance'), __('Every plot and flat comes with verified documents and clear ownership.')],
        ['award', __('Premium quality'), __('Planned roads, drainage and utilities in every project.')],
        ['clock', __('Timely delivery'), __('Handover on the committed schedule, project after project.')],
        ['users', __('Expert team'), __('Experienced professionals guide you through every step.')],
        ['pin', __('Prime locations'), __('Strategic sites with strong connectivity and growth.')],
        ['handshake', __('Trusted partnership'), __('Long-term relationships with the families we serve.')],
      ] as [$icon, $heading, $text])
        <div class="flex gap-4 rounded-2xl border border-line p-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><span><span class="block font-semibold">{{ $heading }}</span><span class="mt-1 block text-sm text-muted-fg">{{ $text }}</span></span></div>
      @endforeach
    </div>
    <div class="mt-8 flex flex-wrap gap-3">
      <a href="/projects" class="btn-dark">{{ __('Browse all projects') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      <a href="/map" class="btn-outline">{{ __('Project map') }}</a>
      <a href="/gallery" class="btn-outline">{{ __('Gallery') }}</a>
      <a href="/contact" class="btn-outline">{{ __('Contact us') }}</a>
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['center' => true, 'title' => __('Frequently asked questions'), 'text' => __('Everything you need to know about buying a plot or flat with us.')])
  <div class="mx-auto mt-8 grid max-w-3xl gap-3">
    @foreach ([
      [__('How do I book a plot?'), __('Pick a plot on the interactive project map or open any listing and tap "Book now". The message opens in WhatsApp with the plot reference already filled in, and our team confirms availability right away.')],
      [__('Are the documents verified?'), __('Yes. Every project is sold with a verified mutation, deed and layout approval. We share the full document set before you pay any booking amount.')],
      [__('Can I visit the site before booking?'), __('Absolutely. Site visits run throughout the week by appointment; message us on WhatsApp or call the hotline to schedule one.')],
      [__('Do you offer instalment plans?'), __('Most plots and flats can be paid for in monthly instalments after a booking amount. The schedule depends on the project; ask us for the current plan.')],
      [__('What do the colours on the project map mean?'), __('Green plots are available, amber plots are booked and red plots are sold. The map updates as soon as our team changes a plot\'s status.')],
      [__('How do I get in touch?'), __('WhatsApp is the fastest way. You can also call the hotline, email us, or use the contact form, which opens a WhatsApp chat with your message.')],
    ] as [$q, $a])
      <details class="card group px-5 py-4 sm:px-6">
        <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-fg">{{ $q }}<span class="faq-icon inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-fill">@include('site::partials.icon', ['name' => 'plus', 'class' => 'size-4'])</span></summary>
        <p class="mt-3 text-sm leading-relaxed text-muted-fg sm:text-base">{{ $a }}</p>
      </details>
    @endforeach
  </div>
  @php($badges = array_values(array_filter(array_map('trim', explode(',', $settings['trust_badges'] ?? '')))))
  @if ($badges)
    <div class="mx-auto mt-10 max-w-3xl text-center">
      <p class="text-sm text-faint-fg">{{ __('Trusted by') }}</p>
      <div class="mt-3 flex flex-wrap justify-center gap-2">
        @foreach ($badges as $badge)<span class="tile px-4 py-2 font-display text-sm font-bold text-fg">{{ $badge }}</span>@endforeach
      </div>
    </div>
  @endif
</section>
@endsection
