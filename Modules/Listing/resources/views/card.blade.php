{{-- Project card. @include('listing::card', ['listing' => $listing]) — the whole card is one link; Enquire sits above it. --}}
@php($cover = $listing->cover())
@php($avail = $listing->availability())
@php($img = $cover ? \Modules\Site\Site::asset($cover->thumb_path ?: $cover->path) : \Modules\Site\Site::placeholder('listing', \Modules\Site\Site::typeKey((string) $listing->type), (int) $listing->id))
@php($enquire = ($settings['whatsapp_number'] ?? '') ? \Modules\Site\Site::whatsapp($settings, __('listing::card.whatsapp_message', ['title' => $listing->title, 'ref' => $listing->reference()])) : '')
@php($onRequest = $listing->call_for_price || trim((string) $listing->price) === '')
@php($meta = array_values(array_filter([$listing->sizeLabel(), $listing->bedrooms ? __('listing::card.bed', ['count' => $listing->bedrooms]) : '', $listing->bedrooms && $listing->baths ? __('listing::card.bath', ['count' => $listing->baths]) : ''])))
<article class="card group lift relative flex h-full flex-col overflow-hidden" data-type="{{ $listing->type }}">
  <div class="relative aspect-[4/3] overflow-hidden rounded-t-[20px] bg-fill">
    @if ($img)<img src="{{ $img }}" alt="" width="800" height="600" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" />@endif
    <span class="absolute top-3 left-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-fg">{{ $listing->typeLabel() }}</span>
  </div>
  <div class="flex flex-1 flex-col p-5">
    <p class="flex items-center gap-1.5 text-sm text-muted-fg"><span class="truncate">{{ $listing->location }}</span>@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3.5 shrink-0 text-accent']) <span class="sr-only">{{ __('listing::card.verified') }}</span></p>
    <h3 class="mt-1 truncate font-display text-[17px] font-bold tracking-[-0.02em] text-fg"><a href="{{ $listing->url() }}" class="after:absolute after:inset-0 after:content-[''] focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-ink-950">{{ $listing->title }}</a></h3>
    <p class="mt-3 font-display text-2xl leading-none font-bold tracking-[-0.02em] text-fg">{{ $onRequest ? __('listing::card.price_on_request') : $listing->price }}</p>
    @if ($meta)<p class="mt-2 text-sm text-muted-fg">{{ implode(' · ', $meta) }}</p>@endif
    @if ($avail)
      @php($taken = $avail['total'] - $avail['available'])
      <div class="mt-4"><span class="block h-1 overflow-hidden rounded-full bg-fill"><span class="availability-bar block h-full rounded-full" style="width: {{ 100 - $avail['percent'] }}%"></span></span><span class="mt-1.5 block text-xs text-muted-fg">{{ __('listing::card.booked_of', ['taken' => $taken, 'total' => $avail['total']]) }}</span></div>
    @endif
    <div class="mt-auto flex items-center justify-between gap-3 pt-5 text-sm">
      @if ($enquire)<a href="{{ $enquire }}" target="_blank" rel="noreferrer" class="relative z-10 inline-flex items-center gap-1.5 font-semibold text-wa-text hover:underline">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('listing::card.enquire') }}</a>@else<span class="text-muted-fg">{{ __('site::common.ref', ['ref' => $listing->reference()]) }}</span>@endif
      <span class="inline-flex items-center gap-1 font-semibold text-fg">{{ __('listing::card.details') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</span>
    </div>
  </div>
</article>
