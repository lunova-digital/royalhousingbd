@extends('site::layouts.app')
@section('content')
@php($label = $type ? \Modules\Listing\Models\Listing::label($type) : null)
@php($query = fn (array $extra) => rtrim($path.'?'.http_build_query(array_filter(['q' => $filter['q'], 'location' => $filter['location']] + $extra, fn ($v) => $v !== null && $v !== '')), '?'))

<section class="container-x pt-5">
  @include('site::partials.breadcrumb', ['items' => $label ? [['Projects', '/projects'], [$label.' listings', null]] : [['Projects', null]]])

  {{-- Department-style dark panel --}}
  <div class="dark-panel mt-4 p-6 sm:p-10 lg:p-12">
    <span class="pill-tag">@include('site::partials.icon', ['name' => 'grid', 'class' => 'size-3.5 text-brand']) {{ $label ? 'Listing type' : 'Catalogue' }}</span>
    <h1 class="mt-4 font-display text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[40px]">{{ $label ? $label.' listings' : 'All projects' }}</h1>
    <p class="mt-3 max-w-2xl text-base text-white/70 sm:text-lg">Browse {{ $label ? strtolower($label).' listings' : 'every plot, flat and property' }} from {{ $site_name }}: verified documents, prime locations and site visits on request.</p>
    <div class="mt-6 flex flex-wrap gap-2">
      <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-2 text-xs font-bold text-white">@include('site::partials.icon', ['name' => 'grid', 'class' => 'size-3.5']) {{ $total }} {{ \Illuminate\Support\Str::plural('listing', $total) }}</span>
      @foreach ([['shield-check', '100% legal documents'], ['pin', 'Prime locations'], ['wallet', 'Book over WhatsApp']] as [$icon, $text])
        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white/90">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-3.5']) {{ $text }}</span>
      @endforeach
    </div>
  </div>

  {{-- Browse by location (FR-LIST-002) --}}
  @if (count($locations))
    <h2 class="mt-8 flex items-baseline gap-2 font-display text-lg font-extrabold">Browse by location <span class="text-sm font-medium text-muted-fg">{{ count($locations) }} {{ \Illuminate\Support\Str::plural('option', count($locations)) }}</span></h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
      @foreach ($locations as $location)
        @php($active = $location === $filter['location'])
        <a href="{{ $query(['location' => $active ? '' : $location, 'page' => null]) }}" class="card flex items-center gap-3 p-3 text-sm font-semibold transition-colors hover:border-ink-950 {{ $active ? 'border-ink-950 ring-1 ring-ink-950' : '' }}">
          <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-fill font-display font-extrabold">{{ mb_strtoupper(mb_substr($location, 0, 1)) }}</span>
          <span class="min-w-0 flex-1 truncate">{{ $location }}</span>
          @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4 shrink-0 text-faint-fg'])
        </a>
      @endforeach
    </div>
  @endif
</section>

<section class="container-x mt-8 grid gap-6 lg:grid-cols-[250px_1fr]">
  {{-- Filters --}}
  <aside class="card h-fit p-5 lg:sticky lg:top-4">
    <h2 class="font-display text-lg font-bold">Filters</h2>
    <div class="mt-4 border-t border-line pt-4">
      <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">Type</h3>
      <ul class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:gap-2.5">
        @foreach (array_merge([[null, 'All types', '/projects']], array_map(fn ($t) => [$t, \Modules\Listing\Models\Listing::label($t), '/'.$t], $types)) as [$key, $name, $href])
          @php($on = $type === $key)
          <li><a href="{{ $href.($filter['q'] || $filter['location'] ? '?'.http_build_query(array_filter(['q' => $filter['q'], 'location' => $filter['location']])) : '') }}" class="flex items-center gap-2.5 text-sm {{ $on ? 'font-semibold text-fg' : 'text-ink-800 hover:text-fg' }}"><span class="inline-flex size-4 items-center justify-center rounded-full border {{ $on ? 'border-ink-950 bg-ink-950' : 'border-faint-fg' }}">@if ($on)<span class="size-1.5 rounded-full bg-white"></span>@endif</span>{{ $name }}</a></li>
        @endforeach
      </ul>
    </div>
    @if (count($locations))
      <div class="mt-5 border-t border-line pt-4">
        <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">Location</h3>
        <ul class="mt-3 flex flex-wrap gap-2 lg:flex-col lg:gap-2.5">
          @foreach ($locations as $location)
            @php($on = $location === $filter['location'])
            <li><a href="{{ $query(['location' => $on ? '' : $location, 'page' => null]) }}" class="flex items-center gap-2.5 text-sm {{ $on ? 'font-semibold text-fg' : 'text-ink-800 hover:text-fg' }}"><span class="inline-flex size-4 items-center justify-center rounded border {{ $on ? 'border-ink-950 bg-ink-950 text-white' : 'border-faint-fg' }}">@if ($on)@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3'])@endif</span>{{ $location }}</a></li>
          @endforeach
        </ul>
      </div>
    @endif
    <form method="get" action="{{ $path }}" class="mt-5 border-t border-line pt-4">
      <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-muted-fg">Search</h3>
      @if ($filter['location'])<input type="hidden" name="location" value="{{ $filter['location'] }}" />@endif
      <label class="relative mt-3 block"><span class="sr-only">Search</span><span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint-fg">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4'])</span><input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Title or location…" class="input h-10 pl-10" /></label>
      <button type="submit" class="btn-dark mt-2 h-10 w-full">Apply</button>
      @if ($filter['q'] || $filter['location'])<a href="{{ $path }}" class="mt-2 block text-center text-xs font-medium text-muted-fg hover:text-fg">Clear filters</a>@endif
    </form>
  </aside>

  <div>
    <div class="card flex items-center justify-between gap-4 px-5 py-3 text-sm">
      <p>Showing <strong class="font-semibold">{{ count($listings) }}</strong> of {{ $total }}</p>
      <p class="text-muted-fg">@if ($filter['location'])in <strong class="font-semibold text-fg">{{ $filter['location'] }}</strong>@elseif ($filter['q'])for “<strong class="font-semibold text-fg">{{ $filter['q'] }}</strong>”@else Sorted by newest @endif</p>
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
        <p class="mt-4 font-semibold">No projects match.</p>
        <p class="mt-1 text-sm text-muted-fg">Try a different search or location.</p>
        <a href="{{ $path }}" class="btn-outline mt-5 h-10">Clear filters</a>
      </div>
    @endif

    @if ($pages > 1)
      <nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
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
