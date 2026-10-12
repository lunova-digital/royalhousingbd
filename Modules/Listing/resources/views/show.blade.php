@extends('site::layouts.app')
@section('content')
@php($l = $listing)
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($avail = $l->availability())
@php($plan = $l->masterplan)
@php($planLive = $plan && $plan->published)
@php($videoId = \Modules\Site\Support\YouTube::id($l->video_url))
@php($whatsappOn = (bool) ($settings['whatsapp_number'] ?? ''))

<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => [[__(':type listings', ['type' => $l->typeLabel()]), '/'.$l->type], [$l->title, null]]])

  <div class="mt-4 grid gap-8 lg:grid-cols-[1.05fr_1fr] lg:gap-12">
    {{-- FR-LIST-003: image gallery --}}
    <div data-gallery>
      <div class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-line bg-fill sm:aspect-square lg:aspect-[4/3]">
        @if ($cover = $l->cover())
          <img data-gallery-main data-lightbox-src="{{ $asset($cover->path) }}" src="{{ $asset($cover->path) }}" alt="{{ $l->title }}" class="size-full cursor-zoom-in object-cover" />
          <button type="button" data-lightbox-src="{{ $asset($cover->path) }}" aria-label="{{ __('View full size') }}" class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-full bg-white text-fg shadow-md">@include('site::partials.icon', ['name' => 'external', 'class' => 'size-4'])</button>
        @else
          <div class="flex size-full items-center justify-center text-faint-fg">@include('site::partials.icon', ['name' => 'image', 'class' => 'size-10'])</div>
        @endif
        <div class="absolute top-4 left-4 flex flex-col items-start gap-2">
          <span class="badge-sale">{{ $avail ? trans_choice(':count plot available|:count plots available', $avail['available']) : $l->typeLabel() }}</span>
          <span class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-xs font-semibold text-success shadow-sm">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3.5']) {{ __('Verified') }}</span>
        </div>
      </div>
      @if ($l->images->count() > 1)
        <div class="scroll-row mt-3 flex gap-3 overflow-x-auto">
          @foreach ($l->images->take(8) as $i => $img)
            <button type="button" data-gallery-thumb="{{ $asset($img->path) }}" class="size-18 shrink-0 overflow-hidden rounded-xl border-2 bg-fill {{ $i === 0 ? 'border-ink-950' : 'border-transparent' }}" aria-label="{{ __('Photo :n', ['n' => $i + 1]) }}">
              <img src="{{ $asset($img->thumb_path ?: $img->path) }}" alt="" loading="lazy" class="size-full object-cover" />
            </button>
          @endforeach
        </div>
      @endif
    </div>

    <div>
      <div class="flex items-center justify-between gap-4 text-sm">
        <span class="flex items-center gap-1.5 font-medium text-brand">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4']) {{ $l->location }} · {{ __('Verified') }}</span>
        @if ($planLive)<a href="{{ $plan->url() }}" class="inline-flex shrink-0 items-center gap-1 font-semibold text-fg hover:text-brand">{{ __('View on map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endif
      </div>
      <h1 class="mt-2 font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ $l->title }}</h1>
      <p class="mt-2 text-sm font-medium text-warn">★ {{ $l->typeLabel() }} · {{ __('Ref :ref', ['ref' => $l->reference()]) }}</p>

      <div class="mt-5 flex flex-wrap items-center gap-3">
        <span class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $l->priceLabel() }}</span>
        @if ($l->sizeLabel())<span class="chip chip-red">{{ $l->sizeLabel() }}</span>@endif
      </div>
      <p class="mt-4 inline-flex items-center gap-2 rounded-xl border border-success/20 bg-success-50 px-3.5 py-2 text-xs font-semibold text-success sm:rounded-full sm:px-4 sm:text-sm">@include('site::partials.icon', ['name' => 'shield-check', 'class' => 'size-4 shrink-0']) {{ __('100% legal documents, verified before you pay') }}</p>

      <ul class="mt-5 grid gap-2.5 text-sm">
        <li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']) {{ __('Prime location: :location', ['location' => $l->location]) }}</li>
        @if ($l->bedrooms)<li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']) {{ trans_choice(':count bedroom|:count bedrooms', $l->bedrooms) }} · {{ trans_choice(':count bath|:count baths', $l->baths) }} @if ($l->floor)· {{ __(':floor floor', ['floor' => $l->floor]) }} @endif</li>@endif
        @if ($l->built_area && $l->land_area)<li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']) {{ __(':built built on :land', ['built' => $l->built_area, 'land' => $l->land_area]) }}</li>@endif
        @if ($l->features->count())<li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']) {{ $l->features->take(4)->pluck('name')->join(', ') }}@if ($l->features->count() > 4) {{ __('and :count more', ['count' => $l->features->count() - 4]) }} @endif</li>@endif
        <li class="flex items-center gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-4 text-success']) {{ __('Site visits by appointment, flexible payment plans') }}</li>
      </ul>

      @if ($avail)
        <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">{{ __('Availability') }}</p>
        <div class="mt-2 flex items-center gap-3">
          <span class="h-2 flex-1 overflow-hidden rounded-full bg-fill"><span class="availability-bar block h-full rounded-full" style="width: {{ 100 - $avail['percent'] }}%"></span></span>
          <span class="text-sm font-semibold">{{ __(':available of :total plots open', ['available' => $avail['available'], 'total' => $avail['total']]) }}</span>
        </div>
      @else
        <p class="mt-6 flex items-center gap-2 text-sm font-semibold text-success"><span class="size-2 rounded-full bg-success"></span> {{ __('Available, ready for a site visit') }}</p>
      @endif

      {{-- FR-LIST-004: WhatsApp with the pre-filled reference --}}
      <div class="mt-6 grid grid-cols-[1fr_1fr_auto] gap-3">
        @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="btn-outline h-13 rounded-xl text-base">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4']) {{ __('Call now') }}</a>@else<a href="/contact" class="btn-outline h-13 rounded-xl text-base">{{ __('Contact') }}</a>@endif
        @if ($whatsappOn)<a href="{{ $whatsapp }}" target="_blank" rel="noreferrer" class="btn-primary h-13 rounded-xl text-base">{{ __('Book now') }}</a>@else<a href="/contact" class="btn-primary h-13 rounded-xl text-base">{{ __('Book now') }}</a>@endif
        <a href="{{ $planLive ? $plan->url() : '/map' }}" class="btn-outline size-13 rounded-xl px-0" aria-label="{{ __('Project map') }}">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-5'])</a>
      </div>
      @if ($l->hasBrochure())
        <a href="{{ $asset($l->brochure_path) }}" download="{{ $l->brochureFilename() }}" class="btn-outline mt-3 h-12 w-full rounded-xl text-sm">@include('site::partials.icon', ['name' => 'download', 'class' => 'size-4']) {{ __('Download brochure') }} <span class="font-normal text-muted-fg">(PDF · {{ \Illuminate\Support\Number::fileSize((int) $l->brochure_size, 1) }})</span></a>
      @endif

      <div class="mt-5 grid grid-cols-3 gap-3">
        @foreach ([['pin', __('Prime location'), $l->location], ['file-check', __('Verified papers'), __('Full document set')], ['wallet', __('Easy payment'), __('Instalments available')]] as [$icon, $heading, $text])
          <div class="rounded-xl border border-line p-3 text-center sm:p-4">@include('site::partials.icon', ['name' => $icon, 'class' => 'mx-auto size-5 text-fg'])<p class="mt-2 text-xs font-bold sm:text-sm">{{ $heading }}</p><p class="mt-0.5 truncate text-[11px] text-muted-fg sm:text-xs">{{ $text }}</p></div>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- Tabs: description, amenities, project map, video --}}
@php($tabs = array_values(array_filter([
  ['description', __('Description'), (bool) $l->description],
  ['amenities', __('Amenities'), $l->features->isNotEmpty()],
  ['map', __('Project Map'), (bool) ($plan && $plan->image_path)],
  ['video', __('Video'), (bool) $videoId],
], fn ($t) => $t[2])))
<section class="container-x mt-10" data-tabs>
  <div class="scroll-row flex gap-1 overflow-x-auto border-b border-line" role="tablist">
    @foreach ($tabs as $i => [$key, $label])
      <button type="button" role="tab" data-tab="{{ $key }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition-colors aria-selected:border-ink-950 aria-selected:text-fg {{ $i === 0 ? 'border-ink-950 text-fg' : 'border-transparent text-muted-fg hover:text-fg' }}">{{ $label }}</button>
    @endforeach
    <span class="ml-auto hidden items-center pb-3 text-xs text-muted-fg sm:flex">{{ __('Type') }}: {{ $l->typeLabel() }} · {{ __('Ref :ref', ['ref' => $l->reference()]) }}</span>
  </div>

  @foreach ($tabs as $i => [$key, $label])
    <div data-tab-panel="{{ $key }}" role="tabpanel" class="pt-6" @if ($i !== 0) hidden @endif>
      @if ($key === 'description')
        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
          <div class="prose-light max-w-3xl">{!! nl2br(e($l->description)) !!}</div>
          <dl class="card h-fit divide-y divide-line text-sm">
            @foreach (array_filter([
              [__('Type'), $l->typeLabel()], [__('Location'), $l->location],
              [__('Land area'), $l->land_area], [__('Built area'), $l->built_area],
              [__('Size'), $l->size], [__('Bedrooms'), $l->bedrooms ?: ''],
              [__('Baths'), $l->baths ?: ''], [__('Floor'), $l->floor],
              [__('Price'), $l->priceLabel()], [__('Reference'), $l->reference()],
            ], fn ($r) => (string) $r[1] !== '') as [$dt, $dd])
              <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted-fg">{{ $dt }}</dt><dd class="text-right font-semibold">{{ $dd }}</dd></div>
            @endforeach
          </dl>
        </div>
      @elseif ($key === 'amenities')
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          @foreach ($l->features as $feature)
            <li class="card flex items-center gap-3 p-4 text-sm font-medium">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-5 shrink-0 text-success'])<a href="/projects?features%5B%5D={{ $feature->slug }}" class="hover:text-brand">{{ $feature->name }}</a></li>
          @endforeach
        </ul>
      @elseif ($key === 'map')
        <div class="card relative overflow-hidden">
          <button type="button" data-lightbox-src="{{ $asset($plan->image_path) }}" class="block w-full cursor-zoom-in bg-fill">
            <img src="{{ $asset($plan->image_path) }}" alt="{{ __(':name masterplan', ['name' => $plan->name]) }}" loading="lazy" class="mx-auto max-h-[70svh] object-contain" />
          </button>
          <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line p-4">
            <p class="text-sm text-muted-fg">{{ $planLive ? __('Tap the layout to open it full size, or explore the interactive version to check every plot.') : __('Tap the layout to open it full size.') }}</p>
            @if ($planLive)<a href="{{ $plan->url() }}" class="btn-dark h-10">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-4']) {{ __('Interactive map') }}</a>@endif
          </div>
        </div>
      @elseif ($key === 'video')
        <div class="max-w-4xl">@include('site::partials.youtube', ['id' => $videoId])</div>
      @endif
    </div>
  @endforeach
</section>

{{-- Enquiry CTA --}}
<section class="container-x mt-12">
  <div class="flex flex-col gap-6 rounded-3xl bg-fill p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <h2 class="font-display text-2xl font-bold sm:text-[26px]">{{ __('Need more information?') }}</h2>
      <p class="mt-2 max-w-lg text-sm text-muted-fg sm:text-base">{{ __('Ask for the document set, a price breakdown or a site visit. We reply on WhatsApp within minutes.') }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
      @if ($whatsappOn)<a href="{{ $whatsapp }}" target="_blank" rel="noreferrer" class="btn-dark h-12 px-6">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('Ask on WhatsApp') }}</a>@endif
      @if ($l->hasBrochure())<a href="{{ $asset($l->brochure_path) }}" download="{{ $l->brochureFilename() }}" class="btn-light h-12 px-6">@include('site::partials.icon', ['name' => 'download', 'class' => 'size-4']) {{ __('Download brochure') }}</a>@endif
      <a href="/contact" class="btn-light h-12 px-6">{{ __('Schedule a visit') }}</a>
    </div>
  </div>
</section>

@if (count($related))
<section class="container-x mt-12">
  @include('site::partials.section-heading', ['eyebrow' => __('You may also like'), 'title' => __('More :type listings', ['type' => app()->getLocale() === 'en' ? strtolower($l->typeLabel()) : $l->typeLabel()]), 'action' => [__('View all'), '/'.$l->type]])
  <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($related as $item)@include('listing::card', ['listing' => $item])@endforeach
  </div>
</section>
@endif
@endsection
