@extends('site::layouts.app')
@section('content')
@php($s = $service)
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($hero = $s->image ? $asset($s->image) : '')
@php($gallery = array_values(array_filter((array) $s->gallery)))
@php($benefits = $s->benefitList())
@php($consultNote = __('service::service.consult_note', ['service' => $s->text('title')]))

<section class="container-x pt-5 lg:pt-8">
  @include('site::partials.breadcrumb', ['items' => [[__('service::service.other_services'), null], [$s->text('title'), null]]])
  <div class="relative isolate mt-4 flex min-h-[420px] flex-col justify-end overflow-hidden rounded-3xl bg-ink-950 text-white lg:min-h-[480px]">
    @if ($hero)<img src="{{ $hero }}" alt="" width="1600" height="900" fetchpriority="high" class="absolute inset-0 -z-20 size-full object-cover" />@endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-ink-950/90 via-ink-950/55 to-ink-950/10"></div>
    <div class="on-dark px-6 pt-16 pb-8 sm:px-10 lg:px-14 lg:pb-12">
      <p class="eyebrow !text-accent-soft">{{ __('service::service.other_services') }}</p>
      <h1 class="font-display mt-3 max-w-2xl text-[38px] leading-[1.08] sm:text-[48px] lg:text-[56px]">{{ $s->text('title') }}</h1>
      @if ($s->text('summary'))<p class="mt-4 max-w-xl text-base text-white/80 sm:text-lg">{{ $s->text('summary') }}</p>@endif
      <div class="mt-7 flex flex-col gap-3 sm:flex-row">
        @include('site::partials.cta', ['kind' => 'wa', 'waHref' => $whatsapp])
        @if (\Modules\Consultation\Availability::enabled())<button type="button" data-consult-open data-consult-note="{{ $consultNote }}" class="btn-consult on-dark" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ __('site::layout.book_consultation') }}</button>@endif
      </div>
    </div>
  </div>
</section>

<section class="container-x grid gap-12 pt-12 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:gap-16 lg:pt-20">
  <div>
    @if ($s->text('description'))
      <h2 class="section-title">{{ __('service::service.about', ['service' => $s->text('title')]) }}</h2>
      <div class="mt-5 grid max-w-2xl gap-4 text-[17px] leading-relaxed">
        @foreach (preg_split('/\n\s*\n/', trim($s->text('description'))) as $paragraph)<p>{!! nl2br(e($paragraph)) !!}</p>@endforeach
      </div>
    @endif
  </div>
  @if ($benefits)
    <div>
      <h2 class="section-title">{{ __('service::service.benefits') }}</h2>
      <ul class="mt-6 grid gap-4">
        @foreach ($benefits as $benefit)
          <li class="flex gap-3 rounded-2xl border border-line bg-white p-4"><span class="mt-0.5 text-accent-text">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-5'])</span><span class="font-medium text-fg">{{ $benefit }}</span></li>
        @endforeach
      </ul>
    </div>
  @endif
</section>

@if ($gallery)
<section class="container-x pt-12 lg:pt-20">
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" data-lightbox-group>
    @foreach ($gallery as $i => $img)
      <button type="button" data-lightbox-src="{{ $asset($img) }}" data-lightbox-caption="{{ $s->text('title') }}" class="group aspect-[4/3] cursor-zoom-in overflow-hidden rounded-2xl border border-line bg-fill" aria-label="{{ $s->text('title') }} {{ $i + 1 }}"><img src="{{ $asset($img) }}" alt="" width="800" height="600" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" /></button>
    @endforeach
  </div>
</section>
@endif

<section class="container-x pt-14 pb-16 lg:pt-20 lg:pb-24">
  <div class="on-dark flex flex-col gap-6 rounded-[28px] bg-ink-950 p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between">
    <div><h2 class="font-display text-[28px] leading-tight sm:text-[34px]">{{ __('service::service.cta_title', ['service' => $s->text('title')]) }}</h2><p class="mt-2 max-w-lg text-white/70">{{ __('service::service.cta_text') }}</p></div>
    <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
      @include('site::partials.cta', ['kind' => 'wa', 'waHref' => $whatsapp])
      @if (\Modules\Consultation\Availability::enabled())<button type="button" data-consult-open data-consult-note="{{ $consultNote }}" class="btn-consult on-dark" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ __('site::layout.book_consultation') }}</button>@endif
    </div>
  </div>
  @if ($others->count())
    <p class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted-fg">{{ __('service::service.other_services') }}:
      @foreach ($others as $other)<a href="{{ $other->url() }}" class="link-arrow">{{ $other->text('title') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endforeach
    </p>
  @endif
</section>
@endsection
