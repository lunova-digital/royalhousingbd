{{-- Project card, styled like the reference's product card. @include('listing::card', ['listing' => $listing]) --}}
@php($cover = $listing->cover())
@php($avail = $listing->availability())
@php($book = ($settings['whatsapp_number'] ?? '') ? \Modules\Site\Site::whatsapp($settings, __('listing::card.whatsapp_message', ['title' => $listing->title, 'ref' => $listing->reference()])) : '')
<article class="card group flex flex-col overflow-hidden transition-shadow hover:shadow-lg hover:shadow-ink-950/5">
  <a href="{{ $listing->url() }}" class="relative block aspect-[4/3] overflow-hidden bg-fill">
    @if ($cover)
      <img src="{{ \Modules\Site\Site::asset($cover->thumb_path ?: $cover->path) }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" />
    @else
      <div class="flex size-full items-center justify-center text-sm text-faint-fg">@include('site::partials.icon', ['name' => 'image', 'class' => 'size-8'])</div>
    @endif
    <span class="badge-sale absolute top-3 left-3">{{ $avail ? trans_choice('site::common.plots_available', $avail['available']) : $listing->typeLabel() }}</span>
    <span class="absolute top-3 right-3 inline-flex size-9 items-center justify-center rounded-full bg-white/90 text-fg shadow-sm">@include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</span>
  </a>
  <div class="flex flex-1 flex-col p-4">
    <p class="flex items-center gap-1 text-xs font-medium text-info">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-3.5']) <span class="truncate">{{ $listing->location }}</span><span class="shrink-0">· {{ __('site::common.verified') }}</span></p>
    <h3 class="mt-1.5 line-clamp-2 text-[15px] font-semibold leading-snug"><a href="{{ $listing->url() }}" class="hover:text-brand">{{ $listing->title }}</a></h3>
    <p class="mt-2.5 flex flex-wrap items-baseline gap-x-2 gap-y-1"><span class="font-display text-lg font-extrabold leading-none">{{ $listing->priceLabel() }}</span>@if ($listing->sizeLabel())<span class="text-xs text-muted-fg">{{ $listing->sizeLabel() }}</span>@endif</p>
    <div class="mt-2.5 flex items-center justify-between gap-2">
      <span class="chip-green chip">{{ $listing->typeLabel() }}</span>
      @if ($listing->bedrooms)<span class="text-xs text-muted-fg">{{ __('listing::card.beds_baths', ['beds' => $listing->bedrooms, 'baths' => $listing->baths]) }}</span>
      @elseif ($avail)<span class="text-xs text-muted-fg">{{ __('listing::card.taken', ['count' => $avail['total'] - $avail['available']]) }}</span>@endif
    </div>
    @if ($avail)
      <div class="mt-3 flex items-center gap-2 text-xs"><span class="text-muted-fg">{{ __('site::common.selling_fast') }}</span><span class="h-1 flex-1 overflow-hidden rounded-full bg-fill"><span class="availability-bar block h-full rounded-full" style="width: {{ 100 - $avail['percent'] }}%"></span></span><span class="font-semibold text-brand">{{ $avail['total'] - $avail['available'] }}/{{ $avail['total'] }}</span></div>
    @endif
    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-3 text-xs">
      @if ($book)<a href="{{ $book }}" target="_blank" rel="noreferrer" class="flex items-center gap-1.5 font-semibold text-success hover:underline">@include('site::partials.icon', ['name' => 'wallet', 'class' => 'size-4']) {{ __('listing::card.book_on_whatsapp') }}</a>@else<span class="text-muted-fg">{{ __('site::common.ref', ['ref' => $listing->reference()]) }}</span>@endif
      <a href="{{ $listing->url() }}" class="text-muted-fg hover:text-fg">{{ __('listing::card.details') }}</a>
    </div>
  </div>
</article>
