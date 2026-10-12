@extends('site::layouts.app')
@section('content')
@php($m = $masterplan)
<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => [[__('Project map'), '/map'], [$m->name, null]]])
  <div class="dark-panel mt-4 flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <span class="pill-tag">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-3.5 text-brand']) {{ __('Interactive map') }}</span>
      <h1 class="mt-3 font-display text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">{{ $m->name }}</h1>
      @if ($m->description)<p class="mt-2 flex items-center gap-2 text-sm text-white/70">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4 text-brand']){{ $m->description }}</p>@endif
    </div>
    {{-- Legend (FR-MAP-005); each item also filters the map (FR-MAP-007) --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Plot status') }}">
      @php($legend = ['available' => __(':count available', ['count' => $counts['available']]), 'booked' => __(':count booked', ['count' => $counts['booked']]), 'sold' => __(':count sold', ['count' => $counts['sold']])])
      @foreach (['available' => 'bg-plot-available', 'booked' => 'bg-plot-booked', 'sold' => 'bg-plot-sold'] as $k => $dot)
        <button type="button" data-plan-filter="{{ $k }}" aria-pressed="true" class="plan-filter inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold transition-opacity"><span class="size-2.5 rounded-full {{ $dot }}"></span>{{ $legend[$k] }}</button>
      @endforeach
    </div>
  </div>
</section>

<section class="container-x mt-6">
  @if ($m->image_path)
    <div class="grid gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="card p-3 sm:p-5">
          <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-display text-base font-bold sm:text-lg">{{ __('Project layout') }}</h2>
            <div class="flex items-center gap-1 rounded-xl bg-fill p-1">
              <button type="button" data-plan-zoom="out" aria-label="{{ __('Zoom out') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'zoom-out', 'class' => 'size-4'])</button>
              <span data-plan-zoom-level class="w-12 text-center text-xs font-semibold text-muted-fg">100%</span>
              <button type="button" data-plan-zoom="in" aria-label="{{ __('Zoom in') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'zoom', 'class' => 'size-4'])</button>
              <span class="mx-1 h-5 w-px bg-line"></span>
              <button type="button" data-plan-zoom="reset" aria-label="{{ __('Reset view') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'reset', 'class' => 'size-4'])</button>
            </div>
          </div>
          {{-- FR-MAP-002/003/006: the layout, tappable plots, zoom and pan --}}
          <div id="plan-map" class="max-h-[75vh] w-full min-w-0 overflow-hidden rounded-xl border border-line bg-fill" style="aspect-ratio: {{ max(1, $m->image_width) }} / {{ max(1, $m->image_height) }}" aria-label="{{ __('Project map') }}"></div>
          <p class="mt-3 text-center text-xs text-muted-fg">{{ __('Tap any plot to view details. Drag to move around the map.') }}</p>
        </div>
      </div>

      {{-- FR-MAP-004: selected plot details; FR-MAP-008: WhatsApp with map + plot reference --}}
      <div>
        <div data-plot-empty class="card hidden p-8 text-center lg:block">
          <span class="icon-box mx-auto size-14 rounded-2xl">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-6'])</span>
          <h3 class="mt-4 font-display text-lg font-bold">{{ __('Select a plot') }}</h3>
          <p class="mt-1 text-sm text-muted-fg">{{ __('Tap any plot on the map to see its size, price and availability.') }}</p>
        </div>
        <div data-plot-details hidden class="card p-5 lg:sticky lg:top-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-display text-xl font-bold">{{ __('Plot') }} <span data-plot-number></span></h3>
            <button type="button" data-plot-close aria-label="{{ __('Close') }}" class="inline-flex size-9 items-center justify-center rounded-full bg-fill hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-4'])</button>
          </div>
          <div class="mt-4"><span data-plot-status class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold"></span></div>
          <dl class="mt-4 divide-y divide-line rounded-xl border border-line text-sm">
            <div class="flex items-center gap-3 p-3.5"><span class="icon-box size-10 rounded-lg">@include('site::partials.icon', ['name' => 'expand', 'class' => 'size-4'])</span><span><dt class="text-xs text-muted-fg">{{ __('Plot size') }}</dt><dd class="font-semibold" data-plot-size></dd></span></div>
            <div class="flex items-center gap-3 p-3.5"><span class="icon-box size-10 rounded-lg">@include('site::partials.icon', ['name' => 'tag', 'class' => 'size-4'])</span><span><dt class="text-xs text-muted-fg">{{ __('Price') }}</dt><dd class="font-display text-lg font-extrabold" data-plot-price></dd></span></div>
            <div class="flex items-center gap-3 p-3.5"><span class="icon-box size-10 rounded-lg">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4'])</span><span><dt class="text-xs text-muted-fg">{{ __('Location') }}</dt><dd class="font-semibold" data-plot-position>{{ $m->name }}</dd></span></div>
          </dl>
          <div class="mt-4 grid gap-2.5">
            @if ($settings['whatsapp_number'] ?? '')
              <a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-primary h-12 w-full rounded-xl text-base">{{ __('Book now') }}</a>
              <a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-green h-11 w-full rounded-xl">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('Ask on WhatsApp') }}</a>
            @endif
            @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="btn-outline h-11 w-full rounded-xl">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4']) {{ __('Call now') }}</a>@endif
          </div>
          <p class="mt-3 text-center text-xs text-muted-fg">{{ __('Ref') }}: <span data-plot-ref></span></p>
        </div>
      </div>
    </div>
    <script id="plan-data" type="application/json">{!! str_replace('</', '<\/', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!}</script>
    @push('scripts')
      {{ Modules::vite('masterplan', 'resources/js/plan.ts') }}
    @endpush
  @else
    <p class="card p-16 text-center text-muted-fg">{{ __('The layout for this project has not been uploaded yet.') }}</p>
  @endif
</section>
@endsection
