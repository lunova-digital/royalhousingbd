@extends('site::layouts.app')
@section('content')
@php($label = $typeName)
@php($heading = $label ? __('site::common.type_listings', ['type' => $label]) : __('site::common.all_projects'))
@php($labels = [
  'type' => __('listing::index.chip_type'), 'location' => __('listing::index.chip_location'), 'budget' => __('listing::index.chip_budget'), 'features' => __('listing::index.chip_features'), 'sort' => __('listing::index.chip_sort'),
  'between' => __('listing::index.budget_between', ['min' => ':min', 'max' => ':max']), 'from' => __('listing::index.budget_from', ['min' => ':min']), 'upTo' => __('listing::index.budget_up_to', ['max' => ':max']),
  'show' => __('listing::index.show_results'), 'selected' => __('listing::index.n_selected'), 'remove' => __('listing::index.remove_filter'), 'filters' => __('listing::index.filters_btn'),
  'search' => __('listing::index.search_label'), 'loading' => __('listing::index.loading'),
])

<section class="container-x pt-5 lg:pt-8">
  <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
    <div>
      @include('site::partials.breadcrumb', ['items' => $label ? [[__('site::common.projects'), '/projects'], [$heading, null]] : [[__('site::common.projects'), null]]])
      <h1 class="mt-2 font-display text-[28px] leading-tight font-bold tracking-[-0.02em] text-fg lg:text-[32px]">{{ $heading }}</h1>
    </div>
  </div>
</section>

<div data-filter-root data-labels="{{ json_encode($labels, JSON_UNESCAPED_UNICODE) }}" data-base="/projects">
  {{-- Sticky bar: search + filter chips (phones) --}}
  <div class="sticky top-16 z-30 mt-4 border-b border-line bg-page/95 backdrop-blur lg:top-[72px]" data-bar>
    <div class="container-x py-3">
      <div class="flex items-center gap-2">
        <label class="relative min-w-0 flex-1"><span class="sr-only">{{ __('listing::index.search_label') }}</span><span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-muted-fg">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-[18px]'])</span><input form="lf" type="search" name="q" value="{{ $filter['q'] }}" placeholder="{{ __('listing::index.search_placeholder') }}" autocomplete="off" enterkeyhint="search" class="input h-11 rounded-full pl-11" /></label>
        <button type="button" data-open-sheet="all" class="relative inline-flex h-11 shrink-0 items-center gap-2 rounded-full border border-line bg-white px-4 text-sm font-semibold text-fg transition-colors duration-150 hover:border-ink-950 lg:hidden">@include('site::partials.icon', ['name' => 'sliders', 'class' => 'size-[18px]']) <span>{{ __('listing::index.filters_btn') }}</span><span data-filter-count hidden class="inline-flex min-w-5 items-center justify-center rounded-full bg-ink-950 px-1.5 text-[11px] leading-5 text-white"></span></button>
      </div>
      <div class="scroll-row -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 sm:-mx-6 sm:px-6 lg:hidden" data-chips>
        @foreach (['type', 'location', 'budget', 'features', 'sort'] as $panel)
          @continue(($panel === 'location' && ! count($locations)) || ($panel === 'features' && ! count($features)))
          <button type="button" data-open-sheet="{{ $panel }}" data-chip="{{ $panel }}" class="filter-chip">{{ $labels[$panel] }} @include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5 opacity-70'])</button>
        @endforeach
      </div>
      <ul class="mt-3 flex flex-wrap gap-2 empty:hidden" data-active-chips aria-label="{{ __('listing::index.active_filters') }}"></ul>
    </div>
  </div>

  <section class="container-x pt-5 pb-16 lg:grid lg:grid-cols-[280px_minmax(0,1fr)] lg:gap-8 lg:pt-6">
    <div data-sheet-backdrop hidden class="fixed inset-0 z-[55] bg-ink-950/50 opacity-0 transition-opacity duration-200 lg:hidden"></div>
    <aside data-sheet class="filter-sheet" aria-label="{{ __('listing::index.filters') }}">
      <div class="sheet-grip lg:hidden" data-sheet-grip aria-hidden="true"><span></span></div>
      <div class="flex items-center justify-between px-5 pt-1 pb-2 lg:px-0 lg:pt-0">
        <h2 class="font-display text-lg font-bold tracking-[-0.02em] text-fg" data-sheet-title>{{ __('listing::index.filters') }}</h2>
        <button type="button" data-clear-all hidden class="hidden text-sm font-medium text-muted-fg hover:text-fg lg:inline">{{ __('listing::index.clear_all') }}</button>
        <button type="button" data-close-sheet aria-label="{{ __('listing::index.close_filters') }}" class="inline-flex size-10 items-center justify-center rounded-full text-muted-fg hover:bg-fill lg:hidden">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
      </div>
      <div class="sheet-body px-5 lg:px-0">@include('listing::partials.filter')</div>
      <div class="sheet-foot lg:hidden">
        <button type="button" data-clear-all class="btn-ghost-light">{{ __('listing::index.clear') }}</button>
        <button type="button" data-close-sheet data-show-button class="btn-consult flex-1">{{ trans_choice('listing::index.show_results', $total, ['count' => $total]) }}</button>
      </div>
    </aside>

    <div data-results role="region" aria-label="{{ __('listing::index.results_region') }}" aria-busy="false">
      @include('listing::partials.results')
    </div>
  </section>
</div>
@endsection

@push('scripts')
  {{ Modules::vite('listing', 'resources/js/public-filter.ts') }}
@endpush
