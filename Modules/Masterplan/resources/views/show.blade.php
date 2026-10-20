@extends('site::layouts.app')
@section('content')
@php($m = $masterplan)
<section class="container-x pt-5 lg:pt-8">
  @include('site::partials.breadcrumb', ['items' => [[__('site::common.project_map'), '/map'], [$m->name, null]]])
  <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <h1 class="font-display text-[28px] leading-tight text-fg lg:text-[34px]">{{ $m->name }}</h1>
      @if ($m->description)<p class="mt-1.5 flex items-center gap-1.5 text-sm text-muted-fg">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4 text-accent-text']){{ $m->description }}</p>@endif
    </div>
    {{-- Legend (FR-MAP-005); each chip also filters the map (FR-MAP-007) --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('masterplan::show.plot_status') }}">
      @php($legend = ['available' => __('masterplan::index.n_available', ['count' => $counts['available']]), 'booked' => __('masterplan::index.n_booked', ['count' => $counts['booked']]), 'sold' => __('masterplan::index.n_sold', ['count' => $counts['sold']])])
      @foreach (['available' => 'bg-status-available', 'booked' => 'bg-status-booked', 'sold' => 'bg-status-sold'] as $k => $dot)
        <button type="button" data-plan-filter="{{ $k }}" aria-pressed="true" class="plan-filter inline-flex h-10 items-center gap-2 rounded-full border border-line bg-white px-4 text-sm font-medium text-fg transition-opacity duration-150"><span class="size-2.5 rounded-full {{ $dot }}"></span>{{ $legend[$k] }}</button>
      @endforeach
    </div>
  </div>
</section>

<section class="container-x mt-6 pb-16 lg:pb-24">
  @if ($m->image_path)
    <div class="grid gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="card relative overflow-hidden p-2 sm:p-3">
          {{-- FR-MAP-002/003/006: the layout, tappable plots, zoom and pan --}}
          <div id="plan-map" class="h-[62svh] min-h-[360px] w-full min-w-0 overflow-hidden rounded-2xl border border-line bg-fill lg:h-[calc(100svh-15rem)] lg:max-h-[760px]" aria-label="{{ __('site::common.project_map') }}"></div>
          <div class="absolute top-5 right-5 z-[500] flex flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-lift sm:top-6 sm:right-6">
            <button type="button" data-plan-zoom="in" aria-label="{{ __('masterplan::show.zoom_in') }}" class="inline-flex size-11 items-center justify-center text-fg transition-colors hover:bg-fill">@include('site::partials.icon', ['name' => 'plus', 'class' => 'size-5'])</button>
            <span data-plan-zoom-level class="border-y border-line py-1 text-center text-[11px] font-semibold text-muted-fg">100%</span>
            <button type="button" data-plan-zoom="out" aria-label="{{ __('masterplan::show.zoom_out') }}" class="inline-flex size-11 items-center justify-center text-fg transition-colors hover:bg-fill">@include('site::partials.icon', ['name' => 'minus', 'class' => 'size-5'])</button>
            <button type="button" data-plan-zoom="reset" aria-label="{{ __('masterplan::show.reset_view') }}" class="inline-flex size-11 items-center justify-center border-t border-line text-fg transition-colors hover:bg-fill">@include('site::partials.icon', ['name' => 'reset', 'class' => 'size-4'])</button>
          </div>
        </div>
        <p class="mt-3 text-center text-xs text-muted-fg">{{ __('masterplan::show.map_hint') }}</p>
      </div>

      {{-- FR-MAP-004: the selected plots; FR-MAP-008: one WhatsApp message listing every selected plot --}}
      <div>
        <div data-plot-empty class="card hidden p-8 text-center lg:block">
          <span class="mx-auto inline-flex size-14 items-center justify-center rounded-full bg-fill text-ink-950">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-6'])</span>
          <h3 class="mt-4 text-lg font-semibold">{{ __('masterplan::show.select_plot') }}</h3>
          <p class="mt-1 text-sm text-muted-fg">{{ __('masterplan::show.select_plot_hint') }}</p>
        </div>
        <div data-plot-details hidden class="plot-sheet">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-xl text-fg font-semibold" data-plot-count></h3>
            <button type="button" data-plot-clear class="inline-flex h-10 items-center gap-1.5 rounded-full bg-fill px-4 text-sm font-semibold text-fg hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-3.5']) {{ __('masterplan::show.clear_selection') }}</button>
          </div>
          <ul data-plot-list class="mt-4 grid max-h-[32svh] gap-2.5 overflow-y-auto overscroll-contain lg:max-h-[45svh]"></ul>
          <template data-plot-item>
            <li class="rounded-xl border border-line p-3.5 text-sm">
              <div class="flex items-center justify-between gap-3">
                <span class="text-base font-semibold">{{ __('site::common.plot') }} <span data-item-number></span></span>
                <span class="flex items-center gap-2">
                  <span data-item-status class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"></span>
                  <button type="button" data-item-remove class="inline-flex size-9 items-center justify-center rounded-full bg-fill hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-3.5'])</button>
                </span>
              </div>
              <dl class="mt-2.5 grid grid-cols-2 gap-x-3 gap-y-1.5">
                <div><dt class="text-xs text-muted-fg">{{ __('masterplan::show.plot_size') }}</dt><dd class="font-semibold" data-item-size></dd></div>
                <div><dt class="text-xs text-muted-fg">{{ __('site::common.price') }}</dt><dd class="font-display" data-item-price></dd></div>
                <div class="col-span-2"><dt class="text-xs text-muted-fg">{{ __('site::common.location') }}</dt><dd class="font-semibold" data-item-position></dd></div>
              </dl>
            </li>
          </template>
          <div class="mt-4 grid gap-2.5">
            @if ($settings['whatsapp_number'] ?? '')<a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-wa w-full">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ __('site::common.book_now') }}</a>@endif
            @include('site::partials.cta', ['kind' => 'consult', 'class' => 'w-full'])
            @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="link-arrow justify-center">{{ __('site::common.call_now') }} @include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4'])</a>@endif
          </div>
          <p class="mt-3 text-center text-xs text-muted-fg">{{ __('masterplan::show.ref') }}: <span data-plot-ref></span></p>
        </div>
      </div>
    </div>
    <script id="plan-data" type="application/json">{!! str_replace('</', '<\/', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!}</script>
    @push('scripts')
      {{ Modules::vite('masterplan', 'resources/js/plan.ts') }}
    @endpush
  @else
    <p class="card p-16 text-center text-muted-fg">{{ __('masterplan::show.no_layout') }}</p>
  @endif
</section>
@endsection
