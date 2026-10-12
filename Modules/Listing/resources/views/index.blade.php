@extends('site::layouts.app')
@section('content')
@php($label = $typeName)
@php($base = ['q' => $filter['q'], 'location' => $filter['location'], 'features' => $filter['features']])
{{-- A link to this grid with some filters changed; features[] stay plain so the checkboxes read them back. --}}
@php($query = fn (array $extra, ?string $to = null) => rtrim(($to ?? $path).'?'.preg_replace('/%5B\d+%5D=/', '%5B%5D=', http_build_query(array_filter($extra + $base, fn ($v) => $v !== null && $v !== '' && $v !== []))), '?'))
@php($active = $filter['q'] !== '' || $filter['location'] !== '' || $filter['features'] !== [])
@php($featureNames = array_column(array_filter($features, fn ($f) => in_array($f->slug, $filter['features'], true)), 'name'))

<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => $label ? [[__('site::common.projects'), '/projects'], [__('site::common.type_listings', ['type' => $label]), null]] : [[__('site::common.projects'), null]]])

  {{-- Department-style dark panel --}}
  <div class="dark-panel mt-4 p-6 sm:p-10 lg:p-12">
    <span class="pill-tag">@include('site::partials.icon', ['name' => 'grid', 'class' => 'size-3.5 text-brand']) {{ $label ? __('listing::index.listing_type') : __('listing::index.catalogue') }}</span>
    <h1 class="mt-4 font-display text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[40px]">{{ $label ? __('site::common.type_listings', ['type' => $label]) : __('site::common.all_projects') }}</h1>
    <p class="mt-3 max-w-2xl text-base text-white/70 sm:text-lg">{{ $label ? __('listing::index.intro_type', ['type' => app()->getLocale() === 'en' ? strtolower($label) : $label, 'site' => $site_name]) : __('listing::index.intro_all', ['site' => $site_name]) }}</p>
    <div class="mt-6 flex flex-wrap gap-2">
      <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-2 text-xs font-bold text-white">@include('site::partials.icon', ['name' => 'grid', 'class' => 'size-3.5']) {{ trans_choice('listing::index.listing_count', $total) }}</span>
      @foreach ([['shield-check', __('site::common.legal_documents')], ['pin', __('site::common.prime_locations')], ['wallet', __('site::common.book_over_whatsapp')]] as [$icon, $text])
        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white/90">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-3.5']) {{ $text }}</span>
      @endforeach
    </div>
  </div>

  {{-- Browse by location (FR-LIST-002) --}}
  @if (count($locations))
    <h2 class="mt-8 flex items-baseline gap-2 font-display text-lg font-extrabold">{{ __('listing::index.browse_by_location') }} <span class="text-sm font-medium text-muted-fg">{{ trans_choice('listing::index.option_count', count($locations)) }}</span></h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      @foreach ($locations as $location)
        @php($on = $location->name === $filter['location'])
        <a href="{{ $query(['location' => $on ? '' : $location->name, 'page' => null]) }}" class="card flex items-center gap-3 p-3 text-sm font-semibold transition-colors hover:border-ink-950 {{ $on ? 'border-ink-950 ring-1 ring-ink-950' : '' }}">
          <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-fill font-display font-extrabold">{{ mb_strtoupper(mb_substr($location->name, 0, 1)) }}</span>
          <span class="min-w-0 flex-1 truncate">{{ $location->name }}<span class="block truncate text-xs font-medium text-muted-fg">{{ trans_choice('site::common.project_count', $location->listings_count) }}</span></span>
          @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4 shrink-0 text-faint-fg'])
        </a>
      @endforeach
    </div>
  @endif
</section>

<section class="container-x mt-8 grid gap-6 lg:grid-cols-[250px_1fr]">
  {{-- Filters: type and location are links; search and features submit one GET form --}}
  <aside class="card h-fit p-5 lg:sticky lg:top-4">
    <div class="flex items-center justify-between gap-3">
      <h2 class="font-display text-lg font-bold">{{ __('listing::index.filters') }}</h2>
      @if ($active)<a href="{{ $path }}" class="text-xs font-medium text-muted-fg hover:text-fg">{{ __('listing::index.clear_all') }}</a>@endif
    </div>

    <form method="get" action="{{ $path }}" data-filter-form class="mt-4 border-t border-line pt-4">
      @if ($filter['location'])<input type="hidden" name="location" value="{{ $filter['location'] }}" />@endif
      <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">{{ __('site::common.search') }}</h3>
      <label class="relative mt-3 block"><span class="sr-only">{{ __('site::common.search') }}</span><span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint-fg">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4'])</span><input type="search" name="q" value="{{ $filter['q'] }}" placeholder="{{ __('listing::index.search_placeholder') }}" class="input h-10 pl-10" /></label>
      <button type="submit" class="btn-dark mt-2 h-10 w-full">{{ __('listing::index.apply') }}</button>

      @if (count($features))
        <div class="mt-5 border-t border-line pt-4">
          <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">{{ __('listing::index.features') }}</h3>
          <p class="mt-1 text-xs text-muted-fg">{{ __('listing::index.features_hint') }}</p>
          <ul class="mt-3 grid gap-2.5">
            @foreach ($features as $feature)
              @php($on = in_array($feature->slug, $filter['features'], true))
              <li>
                <label class="flex cursor-pointer items-center gap-2.5 text-sm {{ $on ? 'font-semibold text-fg' : 'text-ink-800 hover:text-fg' }}">
                  <input type="checkbox" name="features[]" value="{{ $feature->slug }}" @checked($on) data-autosubmit class="size-4 shrink-0 rounded border-faint-fg accent-ink-950" />
                  <span class="flex-1">{{ $feature->name }}</span>
                  <span class="text-xs text-faint-fg">{{ $feature->listings_count }}</span>
                </label>
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </form>

    <div class="mt-5 border-t border-line pt-4">
      <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">{{ __('site::common.type') }}</h3>
      <ul class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:gap-2.5">
        @foreach (array_merge([[null, __('listing::index.all_types'), '/projects']], $listingTypes->map(fn ($t) => [$t->slug, $t->name, $t->url()])->all()) as [$key, $name, $href])
          @php($on = $type === $key)
          <li><a href="{{ $query(['page' => null], $href) }}" class="flex items-center gap-2.5 text-sm {{ $on ? 'font-semibold text-fg' : 'text-ink-800 hover:text-fg' }}"><span class="inline-flex size-4 items-center justify-center rounded-full border {{ $on ? 'border-ink-950 bg-ink-950' : 'border-faint-fg' }}">@if ($on)<span class="size-1.5 rounded-full bg-white"></span>@endif</span>{{ $name }}</a></li>
        @endforeach
      </ul>
    </div>
    @if (count($locations))
      <div class="mt-5 border-t border-line pt-4">
        <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">{{ __('site::common.location') }}</h3>
        <ul class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:gap-2.5">
          @foreach ($locations as $location)
            @php($on = $location->name === $filter['location'])
            <li><a href="{{ $query(['location' => $on ? '' : $location->name, 'page' => null]) }}" class="flex items-center gap-2.5 text-sm {{ $on ? 'font-semibold text-fg' : 'text-ink-800 hover:text-fg' }}"><span class="inline-flex size-4 items-center justify-center rounded border {{ $on ? 'border-ink-950 bg-ink-950 text-white' : 'border-faint-fg' }}">@if ($on)@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3'])@endif</span><span class="flex-1">{{ $location->name }}</span><span class="text-xs tabular-nums text-faint-fg">{{ $location->listings_count }}</span></a></li>
          @endforeach
        </ul>
      </div>
    @endif
  </aside>

  <div>
    <div class="card flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3 text-sm">
      <p>{!! __('listing::index.showing', ['shown' => '<strong class="font-semibold">'.count($listings).'</strong>', 'total' => $total]) !!}</p>
      <p class="flex flex-wrap items-center gap-1.5 text-muted-fg">
        @if ($filter['q'])<span>{!! __('listing::index.for_query', ['q' => '<strong class="font-semibold text-fg">'.e($filter['q']).'</strong>']) !!}</span>@endif
        @if ($filter['location'])<span>{!! __('listing::index.in_location', ['location' => '<strong class="font-semibold text-fg">'.e($filter['location']).'</strong>']) !!}</span>@endif
        @foreach ($featureNames as $name)<span class="chip chip-gray">{{ $name }}</span>@endforeach
        @if (! $active){{ __('listing::index.sorted_by_newest') }} @endif
      </p>
    </div>
    @if (count($listings))
      <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($listings as $listing)
          @include('listing::card', ['listing' => $listing])
        @endforeach
      </div>
    @else
      <div class="card mt-4 flex flex-col items-center p-12 text-center">
        <span class="icon-box">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-5'])</span>
        <p class="mt-4 font-semibold">{{ __('listing::index.no_match') }}</p>
        <p class="mt-1 text-sm text-muted-fg">{{ __('listing::index.no_match_hint') }}</p>
        <a href="{{ $path }}" class="btn-outline mt-5 h-10">{{ __('listing::index.clear_filters') }}</a>
      </div>
    @endif

    @if ($pages > 1)
      <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('listing::index.pagination') }}">
        @for ($p = 1; $p <= $pages; $p++)
          <a href="{{ $query(['page' => $p > 1 ? $p : null]) }}"
             class="inline-flex size-10 items-center justify-center rounded-full border text-sm font-semibold {{ $p === $page ? 'border-ink-950 bg-ink-950 text-white' : 'border-line bg-white text-fg hover:bg-fill' }}"
             @if ($p === $page) aria-current="page" @endif>{{ $p }}</a>
        @endfor
      </nav>
    @endif
  </div>
</section>
@endsection
