@extends('site::layouts.app')

{{-- FR-HOME-003: one announcement at a time, above the header; hidden when there are none --}}
@if ($ticker->count())
@section('ticker')
<div data-ticker class="relative bg-ink-950 text-white" role="region" aria-label="{{ __('site::layout.dismiss') }}">
  <div class="container-x relative flex h-9 items-center justify-center px-10 text-[13px] font-medium">
    <div class="relative h-full w-full">
      @foreach ($ticker as $i => $item)
        <div class="ticker-item" @if ($i === 0) data-on @endif data-ticker-item>
          @if ($item->link)<a href="{{ $item->link }}" class="truncate underline-offset-4 hover:underline">{{ $item->text }}</a>@else<span class="truncate">{{ $item->text }}</span>@endif
        </div>
      @endforeach
    </div>
    <button type="button" data-ticker-dismiss aria-label="{{ __('site::layout.dismiss') }}" class="absolute top-1/2 right-3 inline-flex size-7 -translate-y-1/2 items-center justify-center rounded-full text-white/70 hover:bg-white/10 hover:text-white sm:right-6">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-4'])</button>
  </div>
</div>
@endsection
@endif

@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($stats = \Modules\Site\Site::list('hero_stats'))
@php($heroImg = ($settings['hero_image'] ?? '') ? $asset($settings['hero_image']) : '/images/placeholders/hero.webp')
@php($totals = ['available' => 0, 'booked' => 0, 'sold' => 0])
@foreach ($maps as $map)@php($c = $map->statusCounts())@php($totals = ['available' => $totals['available'] + $c['available'], 'booked' => $totals['booked'] + $c['booked'], 'sold' => $totals['sold'] + $c['sold']])@endforeach
{{-- Featured first, then the newest, each listing once --}}
@php($all = collect($featured)->concat($latest)->unique('id')->values())
@php($counts = \Modules\Site\Site::typeCounts())
@php($cta = [($settings['hero_cta_text'] ?? '') ?: __('site::common.view_all'), ($settings['hero_cta_link'] ?? '') ?: '/projects'])

{{-- 2. Hero --}}
<section class="container-x pt-4 sm:pt-6">
  <div class="relative isolate flex min-h-[560px] flex-col justify-end overflow-hidden rounded-3xl bg-ink-950 text-white">
    <img src="{{ $heroImg }}" alt="" width="1600" height="900" fetchpriority="high" class="absolute inset-0 -z-20 size-full object-cover" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink-950/90 via-ink-950/60 to-ink-950/10"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink-950/70 via-transparent to-transparent"></div>
    <div class="on-dark px-5 pt-14 pb-6 sm:px-10 sm:pb-8 lg:px-14 lg:pt-16 lg:pb-10">
      @if ($settings['hero_badge'] ?? '')<p class="eyebrow !text-accent-soft">{{ $settings['hero_badge'] }}</p>@endif
      <h1 class="mt-3 max-w-2xl font-display text-[32px] leading-[1.1] font-bold tracking-[-0.02em] text-balance sm:text-[40px] lg:text-5xl">{{ $settings['hero_title'] }}</h1>
      @if ($settings['hero_subtitle'] ?? '')<p class="mt-4 max-w-xl text-base text-white/80 sm:text-lg">{{ $settings['hero_subtitle'] }}</p>@endif
      <div class="mt-7 flex flex-col gap-3 sm:flex-row">
        @include('site::partials.cta', ['kind' => 'wa'])
        @include('site::partials.cta', ['kind' => 'consult', 'dark' => true])
      </div>
      <div class="mt-8 max-w-2xl" aria-label="{{ __('home::index.hero_search_label') }}">
        @include('site::partials.search-form', ['pill' => true])
      </div>
      @if ($stats)
        <dl class="mt-7 grid grid-cols-3 divide-x divide-white/20 sm:flex">
          @foreach ($stats as $stat)
            <div class="px-3 first:pl-0 sm:px-8"><dt class="font-display text-xl leading-none font-bold tracking-[-0.02em] sm:text-[28px]">{{ $stat['value'] ?? '' }}</dt><dd class="mt-1.5 text-[13px] text-white/70">{{ $stat['label'] ?? '' }}</dd></div>
          @endforeach
        </dl>
      @endif
    </div>
  </div>
</section>

{{-- 3. Category grid --}}
@php($typeTiles = collect($types)->take(5))
<section class="container-x section reveal">
  @include('site::partials.section-heading', ['title' => __('home::index.categories_title'), 'text' => __('home::index.categories_text')])
  <ul class="mt-8 grid grid-cols-3 gap-x-3 gap-y-6 lg:grid-cols-6 lg:gap-5">
    @foreach ($typeTiles as $t)
      @php($key = \Modules\Site\Site::typeKey($t['key']))
      <li><a href="{{ $t['href'] }}" class="group block text-center">
        <span class="lift relative block aspect-square overflow-hidden rounded-[20px] border border-line bg-fill">
          @if (file_exists(public_path("images/placeholders/category/{$key}.webp")))<img src="/images/placeholders/category/{{ $key }}.webp" alt="" width="600" height="600" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />@else<span class="flex size-full items-center justify-center text-muted-fg">@include('site::partials.icon', ['name' => 'building', 'class' => 'size-8'])</span>@endif
        </span>
        <span class="mt-3 block text-[15px] leading-tight font-semibold text-fg">{{ $t['label'] }}</span>
        <span class="mt-0.5 block text-[13px] text-muted-fg">{{ trans_choice('site::common.listing_count', $counts[$t['key']] ?? 0) }}</span>
      </a></li>
    @endforeach
    <li><a href="/map" class="group block text-center">
      <span class="lift relative block aspect-square overflow-hidden rounded-[20px] border border-line bg-fill">
        @if (file_exists(public_path('images/placeholders/category/map.webp')))<img src="/images/placeholders/category/map.webp" alt="" width="600" height="600" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />@endif
      </span>
      <span class="mt-3 block text-[15px] leading-tight font-semibold text-fg">{{ __('site::common.project_map') }}</span>
      <span class="mt-0.5 block text-[13px] text-muted-fg">{{ trans_choice('site::common.project_count', $maps->count()) }}</span>
    </a></li>
  </ul>
</section>

{{-- 4. Consultation band --}}
<section class="container-x reveal">
  <div class="on-dark grid gap-8 rounded-[28px] bg-ink-950 p-6 text-white sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center lg:gap-14 lg:p-14">
    <div>
      <p class="eyebrow !text-accent-soft">{{ __('home::index.band_eyebrow') }}</p>
      <h2 class="mt-3 max-w-xl font-display text-[28px] leading-tight font-bold tracking-[-0.02em] text-balance sm:text-[32px]">{{ __('home::index.band_title') }}</h2>
      <ol class="mt-8 grid gap-4 sm:grid-cols-3">
        @foreach (['step_budget', 'step_match', 'step_visit'] as $n => $step)
          <li class="flex items-start gap-3 text-[15px] text-white/85"><span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full border border-white/25 text-xs font-semibold text-accent-soft">{{ $n + 1 }}</span><span class="pt-0.5">{{ __('home::index.'.$step) }}</span></li>
        @endforeach
      </ol>
    </div>
    <div class="flex flex-col gap-3 lg:w-72">
      @include('site::partials.cta', ['kind' => 'wa'])
      @include('site::partials.cta', ['kind' => 'consult', 'dark' => true])
      <p class="text-center text-sm text-white/60">{{ __('site::layout.replies_fast') }}</p>
    </div>
  </div>
</section>

{{-- 5. Available now: featured + newest in one grid, filtered by type --}}
@if ($all->count())
@php($tabTypes = collect($types)->filter(fn ($t) => $all->contains('type', $t['key']))->values())
<section class="container-x section reveal" data-tabs-listings>
  @include('site::partials.section-heading', ['title' => __('home::index.available_title'), 'text' => __('home::index.available_text'), 'action' => $cta])
  <div class="scroll-row mt-6 flex gap-2 overflow-x-auto pb-1" role="tablist">
    <button type="button" role="tab" data-tab-chip="all" aria-selected="true" class="shrink-0 rounded-full border border-line bg-white px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:border-ink-950">{{ __('site::common.all') }}</button>
    @foreach ($tabTypes as $t)<button type="button" role="tab" data-tab-chip="{{ $t['key'] }}" aria-selected="false" class="shrink-0 rounded-full border border-line bg-white px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:border-ink-950">{{ $t['label'] }}</button>@endforeach
  </div>
  <div class="snap-row mt-6" data-tab-grid>
    @foreach ($all->take(12) as $listing)<div data-tab-card="{{ $listing->type }}">@include('listing::card', ['listing' => $listing])</div>@endforeach
  </div>
</section>
@endif

{{-- 6. Project maps & live availability --}}
@if ($maps->count())
<section class="container-x pb-14 reveal lg:pb-24">
  @include('site::partials.section-heading', ['title' => __('home::index.maps_title'), 'text' => __('home::index.maps_text'), 'action' => [__('home::index.browse_all_maps'), '/map']])
  <ul class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('home::index.chips_label') }}">
    @foreach ([['available', __('home::index.chip_open'), 'bg-status-available'], ['booked', __('site::common.booked'), 'bg-status-booked'], ['sold', __('site::common.sold'), 'bg-status-sold']] as [$key, $label, $dot])
      <li class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2 text-sm text-text"><span class="size-2 rounded-full {{ $dot }}"></span><span class="font-semibold text-fg">{{ $totals[$key] }}</span> {{ $label }}</li>
    @endforeach
  </ul>
  <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
    @foreach ($maps->take(4) as $map)
      @php($c = $map->statusCounts())
      @php($total = max(1, $map->plots->count()))
      @php($thumb = $map->image_path ? $asset($map->image_path) : \Modules\Site\Site::placeholder('masterplan', 'masterplan', (int) $map->id))
      <a href="{{ $map->url() }}" class="card lift group flex gap-4 p-3 sm:gap-5 sm:p-4">
        <span class="block size-24 shrink-0 overflow-hidden rounded-2xl bg-fill sm:size-32">@if ($thumb)<img src="{{ $thumb }}" alt="" width="800" height="600" loading="lazy" class="size-full object-cover" />@endif</span>
        <span class="flex min-w-0 flex-1 flex-col justify-center">
          <span class="truncate font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ $map->name }}</span>
          <span class="mt-0.5 text-sm text-muted-fg">{{ __('home::index.available_of_total', ['available' => $c['available'], 'total' => $map->plots->count()]) }}</span>
          <span class="mt-3 flex h-1.5 w-full overflow-hidden rounded-full bg-fill" aria-hidden="true">
            <span class="bg-status-available" style="width: {{ $c['available'] * 100 / $total }}%"></span><span class="bg-status-booked" style="width: {{ $c['booked'] * 100 / $total }}%"></span><span class="bg-status-sold" style="width: {{ $c['sold'] * 100 / $total }}%"></span>
          </span>
          <span class="link-arrow mt-3">{{ __('home::index.open_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</span>
        </span>
      </a>
    @endforeach
  </div>
</section>
@endif

{{-- 7. Why --}}
<section class="container-x pb-14 reveal lg:pb-24">
  <div class="border-t border-line pt-14 lg:pt-20">
    @include('site::partials.section-heading', ['title' => __('home::index.why_title', ['site' => $site_name]), 'text' => __('home::index.why_text')])
    <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ([['file-check', 'value_docs'], ['layers', 'value_infra'], ['clock', 'value_handover'], ['users', 'value_advisors']] as [$icon, $k])
        <div><span class="text-accent-text">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-7'])</span><h3 class="mt-4 font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('home::index.'.$k) }}</h3><p class="mt-1.5 text-[15px] text-muted-fg">{{ __('home::index.'.$k.'_text') }}</p></div>
      @endforeach
    </div>
  </div>
</section>

{{-- 8. Videos (FR-HOME-004): only when the admin has added some --}}
@php($videoIds = $videos->map(fn ($v) => ['id' => $v->youtubeId(), 'title' => $v->title])->filter(fn ($v) => $v['id'])->values())
@if ($videoIds->count())
<section class="container-x pb-14 reveal lg:pb-24">
  @include('site::partials.section-heading', ['title' => __('home::index.videos_title'), 'text' => __('home::index.videos_text')])
  <div class="snap-row mt-8">
    @foreach ($videoIds as $video)
      <button type="button" data-video="{{ $video['id'] }}" class="group block text-left" aria-label="{{ __('home::index.watch_video') }}: {{ $video['title'] }}">
        <span class="lift relative block aspect-video overflow-hidden rounded-[20px] border border-line bg-fill"><img src="https://i.ytimg.com/vi/{{ $video['id'] }}/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" /><span class="absolute inset-0 flex items-center justify-center"><span class="inline-flex size-14 items-center justify-center rounded-full bg-white text-ink-950 shadow-lift"><svg viewBox="0 0 24 24" class="ml-0.5 size-6" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span></span></span>
        @if ($video['title'])<span class="mt-3 block text-[15px] font-semibold text-fg">{{ $video['title'] }}</span>@endif
      </button>
    @endforeach
  </div>
  <dialog data-video-dialog class="video-dialog" aria-label="{{ __('home::index.watch_video') }}">
    <button type="button" data-dialog-close aria-label="{{ __('home::index.close_video') }}" class="mb-2 ml-auto flex size-9 items-center justify-center rounded-full text-white/80 hover:bg-white/10">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
    <div data-video-slot class="aspect-video w-full overflow-hidden rounded-2xl bg-black"></div>
  </dialog>
</section>
@endif

{{-- 9. FAQ --}}
<section id="faq" class="container-x pb-14 reveal lg:pb-24">
  <div class="grid gap-10 border-t border-line pt-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16 lg:pt-20">
    <div>
      <h2 class="section-title">{{ __('home::index.faq') }}</h2>
      <p class="mt-2 text-base text-muted-fg">{{ __('home::index.faq_text') }}</p>
      <div class="mt-8 rounded-[20px] border border-line bg-white p-5">
        <p class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('home::index.faq_still') }}</p>
        <p class="mt-1 text-[15px] text-muted-fg">{{ __('home::index.faq_still_text') }}</p>
        <div class="mt-4">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'btn-sm'])</div>
      </div>
      @php($badges = array_values(array_filter(array_map('trim', explode(',', $settings['trust_badges'] ?? '')))))
      @if ($badges)<p class="mt-6 text-sm text-muted-fg">{{ __('home::index.trusted_by') }}: <span class="font-medium text-fg">{{ implode(' · ', $badges) }}</span></p>@endif
    </div>
    <div class="divide-y divide-line border-y border-line">
      @foreach ([
        [__('home::index.faq_book_q'), __('home::index.faq_book_a')],
        [__('home::index.faq_documents_q'), __('home::index.faq_documents_a')],
        [__('home::index.faq_visit_q'), __('home::index.faq_visit_a')],
        [__('home::index.faq_instalments_q'), __('home::index.faq_instalments_a')],
        [__('home::index.faq_colours_q'), __('home::index.faq_colours_a')],
        [__('home::index.faq_contact_q'), __('home::index.faq_contact_a')],
      ] as [$q, $a])
        <details class="group">
          <summary class="flex cursor-pointer items-center justify-between gap-6 py-5 text-[17px] font-semibold text-fg">{{ $q }}<span class="shrink-0 text-muted-fg"><span class="faq-plus">@include('site::partials.icon', ['name' => 'plus', 'class' => 'size-5'])</span><span class="faq-minus">@include('site::partials.icon', ['name' => 'minus', 'class' => 'size-5'])</span></span></summary>
          <p class="pb-5 text-base leading-relaxed text-muted-fg">{{ $a }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>
@endsection
