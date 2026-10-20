@extends('site::layouts.app')
@section('content')
@php($m = $masterplan)
@php($statusDots = ['available' => 'bg-status-available', 'booked' => 'bg-status-booked', 'sold' => 'bg-status-sold'])
@php($statusNames = ['available' => __('site::common.available'), 'booked' => __('site::common.booked'), 'sold' => __('site::common.sold')])

@if ($m->image_path)
<div data-plan-app class="plan-app" data-labels="{{ json_encode($mapLabels, JSON_UNESCAPED_UNICODE) }}" data-endpoint="/map" data-title-suffix="{{ __('site::common.project_map') }}">
  {{-- Title, project switcher and the legend (which also filters the map: FR-MAP-005/007) --}}
  <div class="plan-top" data-plan-top>
    <div class="plan-top-inner">
      <div class="plan-titleblock min-w-0">
        <nav class="hidden text-sm text-muted-fg lg:block" aria-label="{{ __('site::layout.breadcrumb') }}"><a href="/" class="hover:text-fg">{{ __('site::common.home') }}</a> <span aria-hidden="true">›</span> <a href="/map" class="hover:text-fg">{{ __('site::common.project_map') }}</a></nav>
        <h1 class="plan-title truncate" data-plan-title>{{ $m->name }}</h1>
        <p class="hidden truncate text-sm text-muted-fg lg:block" data-plan-description>{{ $m->description }}</p>
      </div>

      @if (count($projects) > 1)
        <div class="plan-switch scroll-row" role="tablist" aria-label="{{ __('masterplan::show.project') }}" data-plan-switch>
          @foreach ($projects as $project)
            <button type="button" role="tab" data-plan-project="{{ $project['slug'] }}" aria-selected="{{ $project['slug'] === $m->slug ? 'true' : 'false' }}" class="plan-switch-item">{{ $project['name'] }}</button>
          @endforeach
        </div>
      @endif

      <div class="plan-legend" role="group" aria-label="{{ __('masterplan::show.plot_status') }}">
        @foreach ($statusDots as $k => $dot)
          <button type="button" data-plan-filter="{{ $k }}" aria-pressed="true" class="plan-chip"><span class="size-2.5 rounded-full {{ $dot }}"></span>{{ $statusNames[$k] }} <b class="num" data-count="{{ $k }}">{{ $counts[$k] }}</b></button>
        @endforeach
      </div>
    </div>
  </div>

  <div class="plan-body">
    <div class="plan-stage" data-stage>
      {{-- The map: pan / zoom layer → rotation layer → the inline SVG --}}
      <div class="plan-viewport" data-viewport tabindex="-1" role="application" aria-label="{{ __('site::common.project_map') }}">
        <div class="plan-pan" data-pan><div class="plan-rot" data-rot></div></div>
      </div>
      <div class="plan-skeleton" data-skeleton aria-live="polite"><span>{{ __('masterplan::show.loading') }}</span></div>
      <p class="plan-error" data-plan-error hidden></p>

      {{-- Find a plot --}}
      <form class="plan-find" data-find role="search" autocomplete="off">
        <button type="button" class="plan-ctl plan-find-toggle" data-find-toggle aria-label="{{ __('masterplan::show.find_plot') }}" aria-expanded="false">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-5'])</button>
        <input type="text" inputmode="text" enterkeyhint="go" data-find-input placeholder="{{ __('masterplan::show.find_placeholder') }}" aria-label="{{ __('masterplan::show.find_plot') }}" class="plan-find-input" />
        <button type="submit" class="plan-find-go">{{ __('masterplan::show.find_go') }}</button>
        <span class="plan-find-msg" data-find-msg role="status" hidden></span>
      </form>

      {{-- North arrow: turns with the map --}}
      <div class="plan-compass" data-compass title="{{ __('masterplan::show.compass_label') }}" aria-hidden="true">
        <svg viewBox="0 0 40 40" class="size-10" data-compass-arrow><circle cx="20" cy="20" r="19" fill="#fff" stroke="#E9E4DC"/><path d="M20 6 L25.5 22 L20 19 L14.5 22 Z" fill="#0F172A"/><path d="M20 34 L25.5 22 L20 19 L14.5 22 Z" fill="#CBD5E1"/></svg>
        <span>{{ __('masterplan::show.compass_n') }}</span>
      </div>

      {{-- Zoom, reset, full screen --}}
      <div class="plan-controls" role="group" aria-label="{{ __('masterplan::show.project_layout') }}">
        <button type="button" class="plan-ctl" data-plan-zoom="in" aria-label="{{ __('masterplan::show.zoom_in') }}">@include('site::partials.icon', ['name' => 'plus', 'class' => 'size-5'])</button>
        <button type="button" class="plan-ctl" data-plan-zoom="out" aria-label="{{ __('masterplan::show.zoom_out') }}">@include('site::partials.icon', ['name' => 'minus', 'class' => 'size-5'])</button>
        <button type="button" class="plan-ctl" data-plan-zoom="reset" aria-label="{{ __('masterplan::show.reset_view') }}">@include('site::partials.icon', ['name' => 'reset', 'class' => 'size-[18px]'])</button>
        <button type="button" class="plan-ctl" data-plan-fullscreen aria-pressed="false" aria-label="{{ __('masterplan::show.full_screen') }}">@include('site::partials.icon', ['name' => 'expand', 'class' => 'size-[18px]'])</button>
      </div>

      <p class="plan-coach" data-coach hidden>@include('site::partials.icon', ['name' => 'cursor', 'class' => 'size-4']) {{ __('masterplan::show.coach') }}</p>
    </div>

    {{-- Selected plots (FR-MAP-004): a three-snap bottom sheet on phones, a sticky side panel on desktop --}}
    <aside class="plan-panel" data-panel data-state="empty" aria-label="{{ __('masterplan::show.select_plot') }}">
      <button type="button" class="plan-grip" data-grip aria-label="{{ __('masterplan::show.sheet_handle') }}" aria-expanded="false"><span></span></button>

      <div class="plan-empty" data-empty>
        <span class="plan-hand" aria-hidden="true">@include('site::partials.icon', ['name' => 'cursor', 'class' => 'size-7'])</span>
        <h2 class="font-display text-[22px] leading-tight text-fg">{{ __('masterplan::show.select_plot') }}</h2>
        <p class="mt-2 text-[15px] text-muted-fg">{{ __('masterplan::show.select_plot_hint') }}</p>
      </div>

      <div data-details hidden class="plan-details">
        <div class="plan-summary" data-summary>
          <div class="min-w-0 flex-1">
            <h2 class="plan-summary-title" data-summary-title></h2>
            <p class="plan-summary-meta" data-summary-meta></p>
          </div>
          <span class="plan-badge" data-summary-status hidden></span>
          <button type="button" data-plot-clear class="plan-clear" aria-label="{{ __('masterplan::show.clear_selection') }}" title="{{ __('masterplan::show.clear_selection') }}">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-[18px]'])</button>
        </div>

        <div class="plan-cta">
          @if ($settings['whatsapp_number'] ?? '')<a data-plot-whatsapp href="#" target="_blank" rel="noreferrer" class="btn-wa btn-sm">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) <span data-wa-label>{{ __('site::layout.chat_whatsapp') }}</span></a>@endif
          @if (\Modules\Consultation\Availability::enabled())<button type="button" data-consult-open data-plot-consult data-consult-note="" class="btn-consult btn-sm consult-grad consult-glow" aria-haspopup="dialog">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) {{ __('masterplan::show.book_consultation_short') }}</button>@endif
        </div>

        <ul data-plot-list class="plan-list"></ul>
        <template data-plot-item>
          <li class="plan-row">
            <div class="flex items-center justify-between gap-3">
              <span class="plan-row-number"><span data-item-label></span></span>
              <span class="flex items-center gap-2"><span data-item-status class="plan-badge"></span><button type="button" data-item-remove class="plan-x">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-4'])</button></span>
            </div>
            <dl class="plan-row-grid">
              <div><dt>{{ __('masterplan::show.plot_size') }}</dt><dd data-item-size></dd></div>
              <div><dt>{{ __('site::common.price') }}</dt><dd data-item-price class="num"></dd></div>
              <div class="col-span-2"><dt>{{ __('site::common.location') }}</dt><dd data-item-position></dd></div>
            </dl>
          </li>
        </template>
        <p class="plan-ref">{{ __('masterplan::show.ref') }}: <span data-plot-ref></span></p>
        @if ($settings['phone'] ?? '')<a href="tel:{{ $settings['phone'] }}" class="link-arrow mt-3 justify-center">{{ __('site::common.call_now') }} @include('site::partials.icon', ['name' => 'phone', 'class' => 'size-4'])</a>@endif
      </div>
    </aside>
  </div>
</div>
<script id="plan-data" type="application/json">{!! str_replace('</', '<\/', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!}</script>
@push('scripts')
  {{ Modules::vite('masterplan', 'resources/js/plan.ts') }}
@endpush
@else
<section class="container-x pt-8 pb-16"><p class="rounded-[20px] border border-line bg-white p-12 text-center text-muted-fg">{{ __('masterplan::show.no_layout') }}</p></section>
@endif
@endsection
