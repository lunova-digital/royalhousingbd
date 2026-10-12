@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => [[__('Project map'), null]]])
  <div class="dark-panel mt-4 p-6 sm:p-10 lg:p-12">
    <span class="pill-tag">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-3.5 text-brand']) {{ __('Interactive experience') }}</span>
    <h1 class="mt-4 font-display text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[40px]">{{ __('Choose a project to explore') }}</h1>
    <p class="mt-3 max-w-2xl text-base text-white/70 sm:text-lg">{{ __('Open any project to see its live masterplan: tap a plot for its size, price and status, then book it over WhatsApp.') }}</p>
    <div class="mt-6 flex flex-wrap gap-2">
      <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-2 text-xs font-bold text-white">{{ trans_choice(':count project|:count projects', $masterplans->count()) }}</span>
      @foreach ([['available', 'bg-plot-available', __('Available')], ['booked', 'bg-plot-booked', __('Booked')], ['sold', 'bg-plot-sold', __('Sold')]] as [$k, $dot, $text])
        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white/90"><span class="size-2 rounded-full {{ $dot }}"></span>{{ $text }}</span>
      @endforeach
    </div>
  </div>
</section>

<section class="container-x mt-8">
  @if ($masterplans->count())
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($masterplans as $map)
        @php($c = $map->statusCounts())
        @php($prices = $map->plots->pluck('price')->filter()->unique()->values())
        <article class="card group flex flex-col overflow-hidden transition-shadow hover:shadow-lg hover:shadow-ink-950/5">
          <a href="{{ $map->url() }}" class="relative block aspect-[16/10] overflow-hidden bg-fill">
            @if ($map->image_path)<img src="{{ $asset($map->image_path) }}" alt="{{ $map->name }}" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />@endif
            <span class="badge-sale absolute top-3 left-3">{{ __(':count available', ['count' => $c['available']]) }}</span>
          </a>
          <div class="flex flex-1 flex-col p-4">
            <h2 class="font-display text-lg font-bold">{{ $map->name }}</h2>
            @if ($map->description)<p class="mt-1 flex items-center gap-1.5 text-sm text-muted-fg">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4 shrink-0 text-brand'])<span class="truncate">{{ $map->description }}</span></p>@endif
            <div class="mt-4 grid grid-cols-2 gap-3">
              <div class="tile p-3 text-center"><p class="font-display text-lg font-extrabold">{{ $map->plots->count() }}</p><p class="text-xs text-muted-fg">{{ __('Total plots') }}</p></div>
              <div class="tile p-3 text-center"><p class="truncate font-display text-lg font-extrabold text-brand">{{ $prices->count() === 1 ? $prices[0] : ($prices->count() > 1 ? trans_choice(':count price tier|:count price tiers', $prices->count()) : __('Call for Price')) }}</p><p class="text-xs text-muted-fg">{{ __('Price') }}</p></div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span class="chip chip-green"><span class="size-2 rounded-full bg-plot-available"></span>{{ __(':count available', ['count' => $c['available']]) }}</span>
              <span class="chip chip-amber"><span class="size-2 rounded-full bg-plot-booked"></span>{{ __(':count booked', ['count' => $c['booked']]) }}</span>
              <span class="chip chip-red"><span class="size-2 rounded-full bg-plot-sold"></span>{{ __(':count sold', ['count' => $c['sold']]) }}</span>
            </div>
            <a href="{{ $map->url() }}" class="btn-dark mt-4 w-full">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-4']) {{ __('View Map') }}</a>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <p class="card p-16 text-center text-muted-fg">{{ __('No project maps published yet.') }}</p>
  @endif
</section>
@endsection
