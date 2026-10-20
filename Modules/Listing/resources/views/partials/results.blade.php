{{-- The results block: re-rendered in place by listing-filter.ts (?fragment=1) and included by the full page.
     data-total feeds the sheet's "Show N results" button. --}}
@php($query = fn (array $extra) => rtrim($path.'?'.preg_replace('/%5B\d+%5D=/', '%5B%5D=', http_build_query(array_filter($extra + ['type' => $filter['type'] && $path === '/projects' ? $filter['type'] : null, 'q' => $filter['q'], 'locations' => $filter['location'], 'features' => $filter['features'], 'budget_min' => $filter['budget_min'] ?: null, 'budget_max' => $filter['budget_max'] ?: null, 'sort' => $filter['sort'] !== 'newest' ? $filter['sort'] : null], fn ($v) => $v !== null && $v !== '' && $v !== []))), '?'))
<div data-results-inner data-total="{{ $total }}" data-page="{{ $page }}">
  <h2 class="sr-only">{{ __('listing::index.results_region') }}</h2>
  <p class="text-sm text-muted-fg" aria-live="polite" data-count-line>{!! __('listing::index.showing', ['shown' => '<strong class="font-semibold text-fg">'.count($listings).'</strong>', 'total' => $total]) !!}</p>
  @if (count($listings))
    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-grid>
      @foreach ($listings as $i => $listing)
        <div class="card-in" style="--i: {{ min($i, 4) }}">@include('listing::card', ['listing' => $listing, 'eager' => $i === 0])</div>
      @endforeach
    </div>
  @else
    <div class="card card-in mt-4 flex flex-col items-center px-6 py-12 text-center sm:py-16">
      <span class="inline-flex size-14 items-center justify-center rounded-full bg-fill text-ink-950">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-6'])</span>
      <h2 class="mt-5 max-w-md font-display text-xl font-bold tracking-[-0.02em] text-fg">{{ __('listing::index.empty_title') }}</h2>
      <p class="mt-2 max-w-md text-[15px] text-muted-fg">{{ __('listing::index.empty_text') }}</p>
      <div class="mt-6 flex flex-col gap-3 sm:flex-row">@include('site::partials.cta', ['kind' => 'wa'])@include('site::partials.cta', ['kind' => 'consult'])</div>
      <a href="{{ $path === '/projects' ? '/projects' : $path }}" data-clear-filters class="link-arrow mt-5">{{ __('listing::index.clear_filters') }}</a>
    </div>
  @endif

  @if ($pages > 1)
    <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('listing::index.pagination') }}" data-pagination>
      @for ($p = 1; $p <= $pages; $p++)
        <a href="{{ $query(['page' => $p > 1 ? $p : null]) }}" data-page-link="{{ $p }}"
           class="inline-flex size-10 items-center justify-center rounded-full border text-sm font-semibold transition-colors duration-150 {{ $p === $page ? 'border-ink-950 bg-ink-950 text-white' : 'border-line bg-white text-fg hover:bg-fill' }}"
           @if ($p === $page) aria-current="page" @endif>{{ $p }}</a>
      @endfor
    </nav>
  @endif
</div>
