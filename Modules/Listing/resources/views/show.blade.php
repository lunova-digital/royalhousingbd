@extends('site::layouts.app')
@section('content')
@php($l = $listing)
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($avail = $l->availability())
@php($plan = $l->masterplan)
@php($planLive = $plan && $plan->published)
@php($videoId = \Modules\Site\Support\YouTube::id($l->video_url))
@php($onRequest = $l->call_for_price || trim((string) $l->price) === '')
@php($slides = $l->images->take(10)->map(fn ($img) => ['src' => $asset($img->path), 'thumb' => $asset($img->thumb_path ?: $img->path)])->all())
@php($fallback = $slides ? '' : \Modules\Site\Site::placeholder('listing', \Modules\Site\Site::typeKey((string) $l->type), (int) $l->id))
@php($facts = array_values(array_filter([
  $l->sizeLabel() ? ['ruler', __('listing::show.size'), $l->sizeLabel()] : null,
  $l->bedrooms ? ['bed', __('listing::show.bedrooms'), $l->bedrooms] : null,
  $l->baths ? ['bath', __('listing::show.baths'), $l->baths] : null,
  $l->floor ? ['layers', __('listing::show.floor'), $l->floor] : null,
  ($l->land_area && $l->land_area !== $l->sizeLabel()) ? ['map', __('listing::show.land_area'), $l->land_area] : null,
  ($l->built_area && $l->built_area !== $l->sizeLabel()) ? ['building', __('listing::show.built_area'), $l->built_area] : null,
])))

<section class="container-x pt-5 lg:pt-8">
  @include('site::partials.breadcrumb', ['items' => [[__('site::common.type_listings', ['type' => $l->typeLabel()]), '/'.$l->type], [$l->title, null]]])

  <div class="mt-4 grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-12">
    {{-- FR-LIST-003: swipeable gallery --}}
    <div data-gallery data-lightbox-group class="min-w-0">
      <div class="relative">
        <div data-slides class="scroll-row flex snap-x snap-mandatory overflow-x-auto rounded-[20px] border border-line bg-fill">
          @forelse ($slides as $i => $slide)
            <figure class="aspect-[4/3] w-full shrink-0 snap-center"><button type="button" data-lightbox-src="{{ $slide['src'] }}" data-lightbox-caption="{{ $l->title }}" class="block size-full cursor-zoom-in" aria-label="{{ __('listing::show.view_full_size') }}"><img src="{{ $slide['src'] }}" alt="{{ $l->title }}" width="800" height="600" @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif class="size-full object-cover" /></button></figure>
          @empty
            <figure class="aspect-[4/3] w-full shrink-0">@if ($fallback)<img src="{{ $fallback }}" alt="" width="800" height="600" class="size-full object-cover" />@else<span class="flex size-full items-center justify-center text-faint-fg">@include('site::partials.icon', ['name' => 'image', 'class' => 'size-10'])</span>@endif</figure>
          @endforelse
        </div>
        <span class="absolute top-4 left-4 rounded-full bg-white px-3 py-1 text-xs font-semibold text-fg">{{ $l->typeLabel() }}</span>
        @if (count($slides) > 1)
          <span data-gallery-counter class="absolute right-4 bottom-4 rounded-full bg-ink-950/75 px-3 py-1 text-xs font-medium text-white"></span>
          <button type="button" data-gallery-prev aria-label="{{ __('site::layout.prev') }}" class="absolute top-1/2 left-3 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-fg shadow-lift hover:bg-white sm:inline-flex">@include('site::partials.icon', ['name' => 'chevron-left', 'class' => 'size-5'])</button>
          <button type="button" data-gallery-next aria-label="{{ __('site::layout.next') }}" class="absolute top-1/2 right-3 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-fg shadow-lift hover:bg-white sm:inline-flex">@include('site::partials.icon', ['name' => 'chevron-right', 'class' => 'size-5'])</button>
        @endif
      </div>
      @if (count($slides) > 1)
        <div class="scroll-row mt-3 hidden gap-3 overflow-x-auto sm:flex">
          @foreach ($slides as $i => $slide)
            <button type="button" data-gallery-thumb class="size-[72px] shrink-0 overflow-hidden rounded-2xl border-2 bg-fill {{ $i === 0 ? 'border-ink-950' : 'border-transparent' }}" aria-label="{{ __('listing::show.photo_n', ['n' => $i + 1]) }}"><img src="{{ $slide['thumb'] }}" alt="" loading="lazy" class="size-full object-cover" /></button>
          @endforeach
        </div>
      @endif
    </div>

    <div class="min-w-0">
      <p class="flex items-center gap-1.5 text-sm text-muted-fg"><span>{{ $l->location }}</span>@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-accent']) <span>{{ __('site::common.verified') }}</span> · <span>{{ __('site::common.ref', ['ref' => $l->reference()]) }}</span></p>
      <h1 class="mt-2 font-display text-[28px] leading-tight font-bold tracking-[-0.02em] text-fg lg:text-[34px]">{{ $l->title }}</h1>

      {{-- Price block --}}
      <div class="mt-5 rounded-[20px] border border-line bg-white p-5">
        <p class="text-sm text-muted-fg">{{ __('site::common.price') }}</p>
        <p class="mt-1 font-display text-[32px] leading-none font-bold tracking-[-0.02em] text-fg">{{ $onRequest ? __('listing::show.price_on_request') : $l->price }}</p>
        @if ($onRequest)<p class="mt-2 text-sm text-muted-fg">{{ __('listing::show.price_on_request_text') }}</p>@endif
        @if ($avail)
          @php($taken = $avail['total'] - $avail['available'])
          <div class="mt-5 border-t border-line pt-4">
            <div class="flex items-center justify-between text-sm"><span class="text-muted-fg">{{ __('listing::show.availability') }}</span><span class="font-semibold text-fg">{{ __('listing::show.plots_open', ['available' => $avail['available'], 'total' => $avail['total']]) }}</span></div>
            <span class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-fill" aria-hidden="true"><span class="bg-status-available" style="width: {{ $avail['available'] * 100 / $avail['total'] }}%"></span><span class="bg-status-booked" style="width: {{ $taken * 100 / $avail['total'] }}%"></span></span>
          </div>
        @endif
      </div>

      {{-- Key facts --}}
      @if ($facts)
        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
          @foreach ($facts as [$icon, $label, $value])
            <div class="rounded-2xl border border-line bg-white p-4"><dt class="text-xs text-muted-fg"><span class="mb-2 block text-accent-text">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span>{{ $label }}</dt><dd class="mt-0.5 font-semibold text-fg">{{ $value }}</dd></div>
          @endforeach
        </dl>
      @endif

      {{-- The two actions (desktop; phones get the sticky bar below) --}}
      <div class="mt-5 hidden gap-3 sm:flex">
        <div class="flex-1">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'w-full', 'waHref' => $whatsapp, 'ctaLabel' => __('listing::show.ask_whatsapp')])</div>
        @if (\Modules\Consultation\Availability::enabled())<button type="button" data-consult-open data-consult-listing="{{ $l->id }}" class="btn-consult flex-1" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ __('site::layout.book_consultation') }}</button>@endif
      </div>
      <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2">
        @if ($planLive)<a href="{{ $plan->url() }}" class="link-arrow">{{ __('listing::show.view_on_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endif
        @if ($l->hasBrochure())<a href="{{ $asset($l->brochure_path) }}" download="{{ $l->brochureFilename() }}" class="link-arrow">{{ __('listing::show.download_brochure') }} <span class="font-normal text-muted-fg">(PDF · {{ \Illuminate\Support\Number::fileSize((int) $l->brochure_size, 1) }})</span> @include('site::partials.icon', ['name' => 'download', 'class' => 'size-4'])</a>@endif
        @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="link-arrow">{{ __('site::common.call_now') }} @include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4'])</a>@endif
      </div>
    </div>
  </div>
</section>

{{-- Details, documents, amenities, location: one scrolling page --}}
<section class="container-x grid gap-12 pt-14 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-16 lg:pt-20">
  <div class="min-w-0 space-y-14">
    @if ($l->description)
      <div><h2 class="section-title">{{ __('listing::show.description_heading') }}</h2><div class="mt-4 max-w-3xl text-[17px] leading-relaxed">{!! nl2br(e($l->description)) !!}</div></div>
    @endif

    @if ($l->features->isNotEmpty())
      <div>
        <h2 class="section-title">{{ __('listing::show.amenities') }}</h2>
        <ul class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
          @foreach ($l->features as $feature)
            <li><a href="/projects?features%5B%5D={{ $feature->slug }}" class="lift flex h-full items-center gap-3 rounded-2xl border border-line bg-white p-4 text-sm font-medium text-fg">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-5 shrink-0 text-accent-text']) {{ $feature->name }}</a></li>
          @endforeach
        </ul>
      </div>
    @endif

    @if ($plan && $plan->image_path)
      <div>
        <h2 class="section-title">{{ __('site::common.project_map_title') }}</h2>
        <div class="card mt-5 overflow-hidden">
          <button type="button" data-lightbox-src="{{ $asset($plan->image_path) }}" data-lightbox-caption="{{ __('listing::show.name_masterplan', ['name' => $plan->name]) }}" class="block w-full cursor-zoom-in bg-fill"><img src="{{ $asset($plan->image_path) }}" alt="{{ __('listing::show.name_masterplan', ['name' => $plan->name]) }}" width="{{ max(1, $plan->image_width) }}" height="{{ max(1, $plan->image_height) }}" loading="lazy" class="mx-auto max-h-[70svh] object-contain" /></button>
          <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line p-4">
            <p class="text-sm text-muted-fg">{{ $planLive ? __('listing::show.layout_hint_interactive') : __('listing::show.layout_hint') }}</p>
            @if ($planLive)<a href="{{ $plan->url() }}" class="link-arrow">{{ __('site::common.interactive_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endif
          </div>
        </div>
      </div>
    @endif

    @if ($videoId)
      <div><h2 class="section-title">{{ __('listing::show.video') }}</h2><div class="mt-5 max-w-3xl">@include('site::partials.youtube', ['id' => $videoId])</div></div>
    @endif
  </div>

  {{-- Documents & verification --}}
  <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
    <div class="card p-6">
      <h2 class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('listing::show.documents_title') }}</h2>
      <ul class="mt-4 space-y-4 text-[15px]">
        @foreach ([['shield-check', __('listing::show.verified_papers'), __('listing::show.legal_documents_note')], ['file-check', __('listing::show.full_document_set'), __('listing::show.documents_text')], ['wallet', __('listing::show.easy_payment'), __('listing::show.instalments_available')]] as [$icon, $heading, $text])
          <li class="flex gap-3"><span class="mt-0.5 text-accent-text">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</span><span><span class="block font-semibold text-fg">{{ $heading }}</span><span class="block text-muted-fg">{{ $text }}</span></span></li>
        @endforeach
      </ul>
    </div>
    <dl class="card divide-y divide-line text-sm">
      @foreach (array_filter([[__('site::common.type'), $l->typeLabel()], [__('site::common.location'), $l->location], [__('listing::show.reference'), $l->reference()]], fn ($r) => (string) $r[1] !== '') as [$dt, $dd])
        <div class="flex justify-between gap-4 px-6 py-3"><dt class="text-muted-fg">{{ $dt }}</dt><dd class="text-right font-semibold text-fg">{{ $dd }}</dd></div>
      @endforeach
    </dl>
  </aside>
</section>

@if (count($related))
<section class="container-x pt-14 pb-10 lg:pt-20">
  @include('site::partials.section-heading', ['title' => __('listing::show.more_type_listings', ['type' => app()->getLocale() === 'en' ? strtolower($l->typeLabel()) : $l->typeLabel()]), 'text' => __('listing::show.also_like'), 'action' => [__('site::common.view_all'), '/'.$l->type]])
  <div class="snap-row mt-6">
    @foreach ($related as $item)<div>@include('listing::card', ['listing' => $item])</div>@endforeach
  </div>
</section>
@endif

{{-- Phones: the two actions stay in reach, just above the tab bar --}}
<div class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom,0px))] z-30 flex gap-3 border-t border-line bg-white/95 px-4 py-3 backdrop-blur sm:hidden">
  <div class="flex-1">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'btn-sm w-full', 'waHref' => $whatsapp, 'ctaLabel' => __('listing::show.enquire_short')])</div>
  @if (\Modules\Consultation\Availability::enabled())<button type="button" data-consult-open data-consult-listing="{{ $l->id }}" class="btn-consult btn-sm flex-1" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-4']) {{ __('listing::show.consult_short') }}</button>@endif
</div>
<div class="h-20 sm:hidden" aria-hidden="true"></div>
@endsection
