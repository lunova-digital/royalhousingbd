{{-- The filter panels. One DOM: a left sidebar of accordions on desktop, a bottom sheet on phones (listing-filter.ts).
     Without JS it is a plain GET form with an Apply button. --}}
@php($money = fn (int $amount) => \Modules\Listing\Support\Price::format($amount))
@php($arrow = fn () => view('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-4 text-muted-fg transition-transform duration-200 group-open:rotate-180']))
<form id="lf" method="get" action="/projects" data-filter-form class="grid gap-0">
  {{-- Type: segmented pills --}}
  <details data-panel="type" open class="group border-b border-line py-4 lg:py-5">
    <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-fg">{{ __('listing::index.chip_type') }} {!! $arrow() !!}</summary>
    <div class="mt-3 flex flex-wrap gap-2" role="radiogroup" aria-label="{{ __('listing::index.chip_type') }}">
      @foreach (array_merge([[null, __('listing::index.all_types')]], $listingTypes->map(fn ($t) => [$t->slug, $t->name])->all()) as [$key, $name])
        <label class="cursor-pointer"><input type="radio" name="type" value="{{ $key }}" @checked(($filter['type'] ?? null) === $key) class="peer sr-only" /><span class="inline-flex h-10 items-center rounded-full border border-line bg-white px-4 text-sm font-medium text-text transition-colors duration-150 hover:border-ink-950 peer-checked:border-ink-950 peer-checked:bg-ink-950 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-ink-950 peer-focus-visible:ring-offset-2">{{ $name }}</span></label>
      @endforeach
    </div>
  </details>

  {{-- Budget --}}
  <details data-panel="budget" open class="group border-b border-line py-4 lg:py-5">
    <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-fg"><span class="inline-flex items-center gap-1.5">{{ __('listing::index.chip_budget') }}<span class="info-tip" tabindex="0" role="note" aria-label="{{ __('listing::index.budget_hint') }}">@include('site::partials.icon', ['name' => 'info', 'class' => 'size-4 text-muted-fg'])<span class="info-bubble" role="tooltip">{{ __('listing::index.budget_hint') }}</span></span></span> {!! $arrow() !!}</summary>
    <div class="mt-3 grid grid-cols-2 gap-3">
      @foreach (['budget_min' => __('listing::index.budget_min'), 'budget_max' => __('listing::index.budget_max')] as $name => $label)
        <label class="grid gap-1 text-xs font-medium text-muted-fg"><span>{{ $label }}</span>
          <span class="relative"><select name="{{ $name }}" class="input h-11 appearance-none pr-8 text-sm font-semibold text-fg">
            <option value="">{{ __('listing::index.budget_any') }}</option>
            @foreach ($budgetSteps as $step)<option value="{{ $step }}" @selected($filter[$name] === $step)>{{ $money($step) }}</option>@endforeach
          </select><span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-muted-fg">@include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5'])</span></span>
        </label>
      @endforeach
    </div>
  </details>

  {{-- Location: searchable checkboxes --}}
  @if (count($locations))
    <details data-panel="location" class="group border-b border-line py-4 lg:py-5" @if ($filter['location']) open @endif>
      <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-fg">{{ __('listing::index.chip_location') }} {!! $arrow() !!}</summary>
      <label class="relative mt-3 block"><span class="sr-only">{{ __('listing::index.location_search') }}</span><span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-muted-fg">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4'])</span><input type="search" data-location-search placeholder="{{ __('listing::index.location_search') }}" autocomplete="off" class="input h-11 pl-10" /></label>
      <ul class="mt-2 max-h-60 overflow-y-auto overscroll-contain" data-location-list>
        @foreach ($locations as $location)
          @php($raw = $location->getRawOriginal('name'))
          <li data-location-item data-search="{{ mb_strtolower($raw.' '.$location->name) }}"><label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-text"><input type="checkbox" name="locations[]" value="{{ $raw }}" @checked(in_array($raw, $filter['location'], true)) class="size-[18px] shrink-0 rounded border-faint-fg accent-ink-950" /><span class="flex-1" data-label>{{ $location->name }}</span><span class="text-xs tabular-nums text-muted-fg">{{ $location->listings_count }}</span></label></li>
        @endforeach
      </ul>
      <p data-location-empty hidden class="py-3 text-sm text-muted-fg">{{ __('listing::index.no_locations') }}</p>
    </details>
  @endif

  {{-- Features --}}
  @if (count($features))
    <details data-panel="features" class="group border-b border-line py-4 lg:py-5" @if ($filter['features']) open @endif>
      <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-fg">{{ __('listing::index.chip_features') }} {!! $arrow() !!}</summary>
      <p class="mt-1 text-xs text-muted-fg">{{ __('listing::index.features_hint') }}</p>
      <ul class="mt-2">
        @foreach ($features as $feature)
          <li><label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-text"><input type="checkbox" name="features[]" value="{{ $feature->slug }}" @checked(in_array($feature->slug, $filter['features'], true)) class="size-[18px] shrink-0 rounded border-faint-fg accent-ink-950" /><span class="flex-1" data-label>{{ $feature->name }}</span><span class="text-xs tabular-nums text-muted-fg">{{ $feature->listings_count }}</span></label></li>
        @endforeach
      </ul>
    </details>
  @endif

  {{-- Sort --}}
  <details data-panel="sort" class="group py-4 lg:py-5" @if ($filter['sort'] !== 'newest') open @endif>
    <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-fg">{{ __('listing::index.chip_sort') }} {!! $arrow() !!}</summary>
    <div class="mt-2" role="radiogroup" aria-label="{{ __('listing::index.chip_sort') }}">
      @foreach (['newest' => __('listing::index.sort_newest'), 'price_asc' => __('listing::index.sort_price_asc'), 'price_desc' => __('listing::index.sort_price_desc')] as $value => $label)
        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-text"><input type="radio" name="sort" value="{{ $value }}" @checked($filter['sort'] === $value) class="size-[18px] shrink-0 accent-ink-950" /><span data-label>{{ $label }}</span></label>
      @endforeach
    </div>
  </details>

  <noscript><button type="submit" class="btn-consult btn-sm mt-4 w-full">{{ __('listing::index.apply') }}</button></noscript>
</form>
