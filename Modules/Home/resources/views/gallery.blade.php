@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@include('site::partials.page-hero', ['crumbs' => [[__('site::common.gallery'), null]], 'title' => __('site::common.gallery'), 'text' => __('home::gallery.description')])

<section class="container-x mt-6">
  <div class="scroll-row flex gap-2 overflow-x-auto pb-1" role="group" aria-label="{{ __('home::gallery.filter_photos') }}">
    @foreach (['all' => __('home::gallery.all_photos')] + $listingTypes->pluck('name', 'slug')->all() + ['masterplan' => __('home::gallery.masterplans')] as $key => $label)
      <button type="button" data-gallery-filter="{{ $key }}" class="gallery-filter shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $key === 'all' ? 'bg-ink-950 text-white' : 'bg-fill text-fg hover:bg-line' }}">{{ $label }}</button>
    @endforeach
  </div>

  @if (count($photos))
    <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
      @foreach ($photos as $photo)
        <figure data-gallery-item="{{ $photo['category'] }}" class="card group relative aspect-square overflow-hidden">
          <button type="button" data-lightbox-src="{{ $asset($photo['src']) }}" class="block size-full cursor-zoom-in bg-fill"><img src="{{ $asset($photo['thumb']) }}" alt="{{ $photo['caption'] }}" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" /></button>
          <figcaption class="pointer-events-none absolute inset-x-2 bottom-2"><a href="{{ $photo['href'] }}" class="pointer-events-auto flex items-center gap-1.5 truncate rounded-lg bg-white/90 px-2.5 py-1.5 text-xs font-semibold text-fg shadow-sm">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-3.5 shrink-0 text-brand'])<span class="truncate">{{ $photo['location'] }}</span></a></figcaption>
        </figure>
      @endforeach
    </div>
    <p data-gallery-empty hidden class="card mt-5 p-16 text-center text-muted-fg">{{ __('home::gallery.empty_category') }}</p>
  @else
    <p class="card mt-5 p-16 text-center text-muted-fg">{{ __('home::gallery.empty') }}</p>
  @endif
</section>
@endsection
