@extends('site::layouts.app')
@section('content')
@php($m = $masterplan)
<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => [[__('site::common.project_map'), '/map'], [$m->name, null]]])
  <div class="dark-panel mt-4 flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <span class="pill-tag">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-3.5 text-brand']) {{ __('site::common.interactive_map') }}</span>
      <h1 class="mt-3 font-display text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">{{ $m->name }}</h1>
      @if ($m->description)<p class="mt-2 flex items-center gap-2 text-sm text-white/70">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-4 text-brand']){{ $m->description }}</p>@endif
    </div>
    {{-- Legend (FR-MAP-005); each item also filters the map (FR-MAP-007) --}}
    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('masterplan::show.plot_status') }}">
      @php($legend = ['available' => __('masterplan::index.n_available', ['count' => $counts['available']]), 'booked' => __('masterplan::index.n_booked', ['count' => $counts['booked']]), 'sold' => __('masterplan::index.n_sold', ['count' => $counts['sold']])])
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
            <h2 class="font-display text-base font-bold sm:text-lg">{{ __('masterplan::show.project_layout') }}</h2>
            <div class="flex items-center gap-1 rounded-xl bg-fill p-1">
              <button type="button" data-plan-zoom="out" aria-label="{{ __('masterplan::show.zoom_out') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'zoom-out', 'class' => 'size-4'])</button>
              <span data-plan-zoom-level class="w-12 text-center text-xs font-semibold text-muted-fg">100%</span>
              <button type="button" data-plan-zoom="in" aria-label="{{ __('masterplan::show.zoom_in') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'zoom', 'class' => 'size-4'])</button>
              <span class="mx-1 h-5 w-px bg-line"></span>
              <button type="button" data-plan-zoom="reset" aria-label="{{ __('masterplan::show.reset_view') }}" class="inline-flex size-9 items-center justify-center rounded-lg text-fg transition-colors hover:bg-white">@include('site::partials.icon', ['name' => 'reset', 'class' => 'size-4'])</button>
            </div>
          </div>
          {{-- FR-MAP-002/003/006: the layout, tappable plots, zoom and pan --}}
          <div id="plan-map" class="max-h-[75vh] w-full min-w-0 overflow-hidden rounded-xl border border-line bg-fill" style="aspect-ratio: {{ max(1, $m->image_width) }} / {{ max(1, $m->image_height) }}" aria-label="{{ __('site::common.project_map') }}"></div>
          <p class="mt-3 text-center text-xs text-muted-fg">{{ __('masterplan::show.map_hint') }}</p>
        </div>
      </div>

      {{-- FR-MAP-004: the selected plots; FR-MAP-008: one WhatsApp message listing every selected plot --}}
      <div>
        <div data-plot-empty class="card hidden p-8 text-center lg:block">
          <span class="icon-box mx-auto size-14 rounded-2xl">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-6'])</span>
          <h3 class="mt-4 font-display text-lg font-bold">{{ __('masterplan::show.select_plot') }}</h3>
          <p class="mt-1 text-sm text-muted-fg">{{ __('masterplan::show.select_plot_hint') }}</p>
        </div>
        <div data-plot-details hidden class="card p-5 lg:sticky lg:top-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-display text-xl font-bold" data-plot-count></h3>
            <button type="button" data-plot-clear class="inline-flex h-9 items-center gap-1.5 rounded-full bg-fill px-3 text-xs font-semibold hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-3.5']) {{ __('masterplan::show.clear_selection') }}</button>
          </div>
          <ul data-plot-list class="mt-4 grid max-h-[45vh] gap-2.5 overflow-y-auto"></ul>
          <template data-plot-item>
            <li class="rounded-xl border border-line p-3.5 text-sm">
              <div class="flex items-center justify-between gap-3">
                <span class="font-display text-base font-bold">{{ __('site::common.plot') }} <span data-item-number></span></span>
                <span class="flex items-center gap-2">
                  <span data-item-status class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"></span>
                  <button type="button" data-item-remove class="inline-flex size-7 items-center justify-center rounded-full bg-fill hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-3.5'])</button>
                </span>
              </div>
              <dl class="mt-2.5 grid grid-cols-2 gap-x-3 gap-y-1.5">
                <div><dt class="text-xs text-muted-fg">{{ __('masterplan::show.plot_size') }}</dt><dd class="font-semibold" data-item-size></dd></div>
                <div><dt class="text-xs text-muted-fg">{{ __('site::common.price') }}</dt><dd class="font-display font-extrabold" data-item-price></dd></div>
                <div class="col-span-2"><dt class="text-xs text-muted-fg">{{ __('site::common.location') }}</dt><dd class="font-semibold" data-item-position></dd></div>
              </dl>
            </li>
          </template>
          <div class="mt-4 grid gap-2.5">
            @if ($settings['whatsapp_number'] ?? '')
              <a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-primary h-12 w-full rounded-xl text-base">{{ __('site::common.book_now') }}</a>
              <a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-green h-11 w-full rounded-xl">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('site::common.ask_on_whatsapp') }}</a>
            @endif
            @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="btn-outline h-11 w-full rounded-xl">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4']) {{ __('site::common.call_now') }}</a>@endif
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
