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
            <a href="/map" class="btn-ghost h-12 px-6 text-base">{{ __('home::index.browse_project_map') }}</a>
          </div>
        </div>

        {{-- Spotlight card: the first featured listing --}}
        @if ($hero)
          @php($cover = $hero->cover())
          @php($avail = $hero->availability())
          <a href="{{ $hero->url() }}" class="group mx-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white p-2.5 text-fg shadow-2xl shadow-black/40">
            <div class="relative aspect-square overflow-hidden rounded-xl bg-fill">
              @if ($cover)<img src="{{ $asset($cover->thumb_path ?: $cover->path) }}" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />@endif
              <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold shadow-sm">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3.5 text-success']) {{ $avail ? trans_choice('site::common.plots_available', $avail['available']) : __('home::index.featured_type', ['type' => $hero->typeLabel()]) }}</span>
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
        <span class="pr-20"><span class="block font-display text-xl font-extrabold leading-tight">{{ __('home::index.legal_assurance') }}</span><span class="mt-1 block text-sm text-fg/70">{{ __('home::index.legal_assurance_text') }}</span></span>
        <span class="btn-light mt-8 h-10 w-fit px-4 text-sm">{{ __('home::index.view_listings') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-3.5'])</span>
      </a>
      <a href="/map" class="group relative flex flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-[#d7e6ff] to-[#edf3ff] p-6 text-fg">
        <span class="absolute top-6 right-6 inline-flex size-16 items-center justify-center rounded-2xl bg-info text-white shadow-lg shadow-info/30">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-7'])</span>
        <span class="pr-20"><span class="block font-display text-xl font-extrabold leading-tight">{{ __('home::index.interactive_map') }}</span><span class="mt-1 block text-sm text-fg/70">{{ __('home::index.interactive_map_text') }}</span></span>
        <span class="btn-light mt-8 h-10 w-fit px-4 text-sm">{{ __('home::index.explore_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-3.5'])</span>
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
      <span><span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('site::common.selling_fast') }} · {{ trans_choice('site::common.project_count', $maps->count()) }}</span><span class="block font-display text-2xl font-bold">{{ __('home::index.plots_now') }}</span></span>
    </div>
    <p class="max-w-sm text-sm text-white/70">{{ __('home::index.plots_now_text') }}</p>
    <div class="flex flex-wrap items-center gap-3 lg:ml-auto">
      <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('home::index.right_now') }}</span>
      @foreach ([['available', __('home::index.open')], ['booked', __('site::common.booked')], ['sold', __('site::common.sold')]] as [$key, $label])
        <span class="flex min-w-16 flex-col items-center rounded-xl bg-white/10 px-3 py-2"><span class="font-display text-xl font-extrabold leading-none">{{ $totals[$key] }}</span><span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</span></span>
      @endforeach
      <a href="/map" class="btn-light ml-1 h-11 px-5">{{ __('home::index.view_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
    </div>
  </div>
</section>
@endif

{{-- FR-HOME-002: featured listings --}}
@if (count($featured))
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('home::index.featured_projects'), 'title' => __('home::index.featured_title'), 'text' => __('home::index.featured_text'), 'action' => [__('site::common.view_all'), '/projects']])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach (array_slice($featured, 0, 8) as $listing)@include('listing::card', ['listing' => $listing])@endforeach
  </div>
</section>
@endif

{{-- Project maps as a tile grid --}}
@if ($maps->count())
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('home::index.buy_with_confidence'), 'title' => __('home::index.maps_title'), 'text' => __('home::index.maps_text'), 'action' => [__('home::index.browse_all_maps'), '/map']])
  <div class="card mt-6 p-3 sm:p-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      @foreach ($maps as $map)
        @php($c = $map->statusCounts())
        <a href="{{ $map->url() }}" class="tile flex min-h-24 flex-col items-center justify-center gap-1.5 p-4 text-center transition-colors hover:bg-brand-50">
          <span class="font-display text-sm font-bold text-fg">{{ $map->name }}</span>
          <span class="text-xs font-semibold text-success">{{ __('home::index.available_of_total', ['available' => $c['available'], 'total' => $map->plots->count()]) }}</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- Newest listings --}}
@if (count($latest))
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('home::index.just_listed'), 'title' => __('home::index.new_arrivals'), 'text' => __('home::index.new_arrivals_text'), 'action' => [__('site::common.view_all'), '/projects']])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @foreach ($latest as $listing)@include('listing::card', ['listing' => $listing])@endforeach
  </div>
</section>
@endif

{{-- Trust strip --}}
<section class="container-x mt-10 sm:mt-12">
  <div class="card grid grid-cols-1 divide-y divide-line sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
    @foreach ([['pin', __('site::common.prime_locations')], ['shield-check', __('site::common.legal_documents')], ['refresh', __('home::index.flexible_payment')], ['wallet', __('site::common.book_over_whatsapp')]] as [$icon, $label])
      <div class="flex items-center gap-4 px-6 py-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><span class="font-semibold">{{ $label }}</span></div>
    @endforeach
  </div>
</section>

{{-- Investor CTA --}}
<section class="container-x mt-10 sm:mt-12">
  <div class="flex flex-col gap-6 rounded-3xl bg-fill p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <span class="pill-tag-light">@include('site::partials.icon', ['name' => 'star', 'class' => 'size-3.5']) {{ __('home::index.for_investors') }}</span>
      <h2 class="mt-4 font-display text-2xl font-bold sm:text-[26px]">{{ __('home::index.free_site_visit') }}</h2>
      <p class="mt-2 max-w-lg text-sm text-muted-fg sm:text-base">{{ __('home::index.free_site_visit_text') }}</p>
    </div>
    <a href="/contact" class="btn-dark h-12 px-6 text-base">{{ __('site::common.schedule_a_visit') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
  </div>
</section>

{{-- Dark contact band --}}
<section class="container-x mt-6">
  <div class="dark-panel flex flex-col gap-8 p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between lg:p-12">
    <div class="max-w-xl">
      <span class="pill-tag">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-3.5']) {{ __('home::index.get_in_touch') }}</span>
      <h2 class="mt-4 font-display text-2xl font-bold sm:text-3xl">{{ __('home::index.get_in_touch_title') }}</h2>
      <p class="mt-3 text-sm text-white/65 sm:text-base">{{ __('home::index.get_in_touch_text') }}</p>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row">
      @if ($whatsapp)<a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-3 rounded-xl bg-white px-5 py-3 text-fg">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])<span><span class="block text-[10px] font-semibold uppercase tracking-wider text-muted-fg">{{ __('site::common.chat_on') }}</span><span class="block font-bold">{{ __('site::common.whatsapp') }}</span></span></a>@endif
      @if ($phone)<a href="tel:{{ $phone }}" class="inline-flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-white">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-6'])<span><span class="block text-[10px] font-semibold uppercase tracking-wider text-white/50">{{ __('site::common.call_us') }}</span><span class="block font-bold">{{ $phone }}</span></span></a>@endif
    </div>
  </div>
</section>

{{-- FR-HOME-004: YouTube showcase --}}
@if ($videos->count())
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['eyebrow' => __('home::index.videos'), 'title' => __('home::index.videos_title'), 'text' => __('home::index.videos_text')])
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
    <span class="pill-tag-light"><span class="size-1.5 rounded-full bg-brand"></span>{{ __('home::index.why_us') }}</span>
    <h2 class="mt-5 max-w-3xl font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('home::index.why_us_title') }}</h2>
    <p class="mt-4 max-w-3xl text-base leading-relaxed text-muted-fg sm:text-lg">{!! __('home::index.why_us_text', ['site' => e($site_name), 'cta' => '<strong class="font-semibold text-fg">'.__('home::index.why_us_cta').'</strong>']) !!}</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ([
        ['shield-check', __('home::index.value_legal'), __('home::index.value_legal_text')],
        ['award', __('home::index.value_quality'), __('home::index.value_quality_text')],
        ['clock', __('home::index.value_delivery'), __('home::index.value_delivery_text')],
        ['users', __('home::index.value_team'), __('home::index.value_team_text')],
        ['pin', __('site::common.prime_locations'), __('home::index.value_locations_text')],
        ['handshake', __('home::index.value_partnership'), __('home::index.value_partnership_text')],
      ] as [$icon, $heading, $text])
        <div class="flex gap-4 rounded-2xl border border-line p-5"><span class="icon-box">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><span><span class="block font-semibold">{{ $heading }}</span><span class="mt-1 block text-sm text-muted-fg">{{ $text }}</span></span></div>
      @endforeach
    </div>
    <div class="mt-8 flex flex-wrap gap-3">
      <a href="/projects" class="btn-dark">{{ __('home::index.browse_all_projects') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      <a href="/map" class="btn-outline">{{ __('site::common.project_map') }}</a>
      <a href="/gallery" class="btn-outline">{{ __('site::common.gallery') }}</a>
      <a href="/contact" class="btn-outline">{{ __('site::common.contact_us') }}</a>
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="container-x mt-12 sm:mt-16">
  @include('site::partials.section-heading', ['center' => true, 'title' => __('home::index.faq'), 'text' => __('home::index.faq_text')])
  <div class="mx-auto mt-8 grid max-w-3xl gap-3">
    @foreach ([
      [__('home::index.faq_book_q'), __('home::index.faq_book_a')],
      [__('home::index.faq_documents_q'), __('home::index.faq_documents_a')],
      [__('home::index.faq_visit_q'), __('home::index.faq_visit_a')],
      [__('home::index.faq_instalments_q'), __('home::index.faq_instalments_a')],
      [__('home::index.faq_colours_q'), __('home::index.faq_colours_a')],
      [__('home::index.faq_contact_q'), __('home::index.faq_contact_a')],
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
      <p class="text-sm text-faint-fg">{{ __('home::index.trusted_by') }}</p>
      <div class="mt-3 flex flex-wrap justify-center gap-2">
        @foreach ($badges as $badge)<span class="tile px-4 py-2 font-display text-sm font-bold text-fg">{{ $badge }}</span>@endforeach
      </div>
    </div>
  @endif
</section>
@endsection
