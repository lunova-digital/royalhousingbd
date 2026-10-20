@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@include('site::partials.page-hero', ['crumbs' => [[__('site::common.gallery'), null]], 'title' => __('site::common.gallery'), 'text' => __('home::gallery.description')])

<section class="container-x pt-6 pb-16 lg:pb-24">
  <div class="scroll-row flex gap-2 overflow-x-auto pb-1" role="group" aria-label="{{ __('home::gallery.filter_photos') }}">
    @foreach (['all' => __('home::gallery.all_photos')] + $listingTypes->pluck('name', 'slug')->all() + ['masterplan' => __('home::gallery.masterplans')] as $key => $label)
      <button type="button" data-gallery-filter="{{ $key }}" @if ($key === 'all') data-active @endif class="filter-chip">{{ $label }}</button>
    @endforeach
  </div>

  @if (count($photos))
    {{-- Masonry: CSS columns, with a rotating aspect ratio so the wall has rhythm --}}
    <div class="mt-6 columns-2 gap-3 sm:gap-4 lg:columns-4" data-lightbox-group>
      @foreach ($photos as $i => $photo)
        <figure data-gallery-item="{{ $photo['category'] }}" class="group relative mb-3 break-inside-avoid overflow-hidden rounded-2xl border border-line bg-fill sm:mb-4">
          <button type="button" data-lightbox-src="{{ $asset($photo['src']) }}" data-lightbox-caption="{{ $photo['caption'] }}" class="block w-full cursor-zoom-in {{ ['aspect-[4/5]', 'aspect-square', 'aspect-[4/3]', 'aspect-[3/4]'][$i % 4] }}" aria-label="{{ $photo['caption'] }}"><img src="{{ $asset($photo['thumb']) }}" alt="{{ $photo['caption'] }}" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" /></button>
          <figcaption class="pointer-events-none absolute inset-x-2 bottom-2"><a href="{{ $photo['href'] }}" class="pointer-events-auto flex items-center gap-1.5 truncate rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-fg">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-3.5 shrink-0 text-accent'])<span class="truncate">{{ $photo['location'] }}</span></a></figcaption>
        </figure>
      @endforeach
    </div>
    <p data-gallery-empty hidden class="mt-8 rounded-[20px] border border-line bg-white p-12 text-center text-muted-fg">{{ __('home::gallery.empty_category') }}</p>
  @else
    <p class="mt-8 rounded-[20px] border border-line bg-white p-12 text-center text-muted-fg">{{ __('home::gallery.empty') }}</p>
  @endif
</section>
@endsection
