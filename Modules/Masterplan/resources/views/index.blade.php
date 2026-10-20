@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@php($totals = ['available' => 0, 'booked' => 0, 'sold' => 0])
@foreach ($masterplans as $map)@php($c = $map->statusCounts())@php($totals = ['available' => $totals['available'] + $c['available'], 'booked' => $totals['booked'] + $c['booked'], 'sold' => $totals['sold'] + $c['sold']])@endforeach
@include('site::partials.page-hero', ['crumbs' => [[__('site::common.project_map'), null]], 'title' => __('masterplan::index.heading'), 'text' => __('masterplan::index.intro'), 'chip' => __('masterplan::index.eyebrow')])

<section class="container-x pt-6 pb-16 lg:pb-24">
  <ul class="flex flex-wrap gap-2" aria-label="{{ __('masterplan::show.plot_status') }}">
    <li class="inline-flex items-center rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-fg">{{ trans_choice('site::common.project_count', $masterplans->count()) }}</li>
    @foreach ([['available', 'bg-status-available', __('site::common.available')], ['booked', 'bg-status-booked', __('site::common.booked')], ['sold', 'bg-status-sold', __('site::common.sold')]] as [$k, $dot, $text])
      <li class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2 text-sm text-text"><span class="size-2 rounded-full {{ $dot }}"></span><span class="font-semibold text-fg">{{ $totals[$k] }}</span> {{ $text }}</li>
    @endforeach
  </ul>

  @if ($masterplans->count())
    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($masterplans as $map)
        @php($c = $map->statusCounts())
        @php($total = max(1, $map->plots->count()))
        @php($prices = $map->plots->pluck('price')->filter()->unique()->values())
        @php($thumb = $map->image_path ? $asset($map->image_path) : \Modules\Site\Site::placeholder('masterplan', 'masterplan', (int) $map->id))
        <a href="{{ $map->url() }}" class="card lift group flex flex-col overflow-hidden">
          <span class="relative block aspect-[16/10] overflow-hidden bg-fill">@if ($thumb)<img src="{{ $thumb }}" alt="" width="800" height="500" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" />@endif</span>
          <span class="flex flex-1 flex-col p-5">
            <span class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ $map->name }}</span>
            @if ($map->description)<span class="mt-1 flex items-center gap-1.5 text-sm text-muted-fg">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4 shrink-0 text-accent-text'])<span class="truncate">{{ $map->description }}</span></span>@endif
            <span class="mt-4 text-sm text-muted-fg">{{ __('home::index.available_of_total', ['available' => $c['available'], 'total' => $map->plots->count()]) }} · {{ $prices->count() === 1 ? \Modules\Site\Site::num($prices[0]) : ($prices->count() > 1 ? trans_choice('masterplan::index.price_tier_count', $prices->count()) : __('listing::card.price_on_request')) }}</span>
            <span class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-fill" aria-hidden="true"><span class="bg-status-available" style="width: {{ $c['available'] * 100 / $total }}%"></span><span class="bg-status-booked" style="width: {{ $c['booked'] * 100 / $total }}%"></span><span class="bg-status-sold" style="width: {{ $c['sold'] * 100 / $total }}%"></span></span>
            <span class="mt-2 flex gap-4 text-xs text-muted-fg"><span>{{ __('masterplan::index.n_booked', ['count' => $c['booked']]) }}</span><span>{{ __('masterplan::index.n_sold', ['count' => $c['sold']]) }}</span></span>
            <span class="link-arrow mt-4">{{ __('home::index.open_map') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</span>
          </span>
        </a>
      @endforeach
    </div>
  @else
    <p class="mt-8 rounded-[20px] border border-line bg-white p-12 text-center text-muted-fg">{{ __('masterplan::index.empty') }}</p>
  @endif
</section>
@endsection
