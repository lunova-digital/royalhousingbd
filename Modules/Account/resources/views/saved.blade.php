@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('account::saved.title'), null]], 'title' => __('account::saved.title'), 'text' => __('account::saved.intro')])

<section class="container-x pt-6 pb-16 lg:pb-24" data-saved-root>
  <div class="flex flex-wrap items-center justify-between gap-3" @if (! $total) hidden @endif>
    <div class="scroll-row flex gap-2 overflow-x-auto pb-1" role="tablist">
      <button type="button" role="tab" data-saved-tab="all" data-active class="filter-chip">{{ __('account::saved.all') }} <span data-count class="num ml-1 text-muted-fg">{{ $total }}</span></button>
      @foreach ($tabs as $tab)
        <button type="button" role="tab" data-saved-tab="{{ $tab['key'] }}" class="filter-chip">{{ $tab['label'] }} <span data-count class="num ml-1 text-muted-fg">{{ $tab['count'] }}</span></button>
      @endforeach
    </div>
    @if ($askAll)<a href="{{ $askAll }}" target="_blank" rel="noreferrer" data-ask-all class="btn-wa btn-sm">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5']) {{ __('account::saved.ask_all') }}</a>@endif
  </div>

  <div data-saved-grid class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" @if (! $total) hidden @endif>
    @foreach ($listings as $listing)
      <div data-saved-card="{{ $listing->type }}" class="relative">
        @include('listing::card', ['listing' => $listing])
      </div>
    @endforeach
  </div>

  <div data-saved-empty class="mt-8 flex flex-col items-center rounded-[20px] border border-line bg-white px-6 py-14 text-center" @if ($total) hidden @endif>
    <span class="inline-flex size-16 items-center justify-center rounded-full bg-fill text-accent-text">@include('site::partials.icon', ['name' => 'heart', 'class' => 'size-7'])</span>
    <h2 class="font-display mt-5 text-[32px] leading-[1.12] text-fg">{{ __('account::saved.empty_title') }}</h2>
    <p class="mt-2 max-w-md text-[15px] text-muted-fg">{{ __('account::saved.empty_text') }}</p>
    <div class="mt-6 flex flex-col gap-3 sm:flex-row"><a href="/projects" class="btn-consult">{{ __('account::saved.browse') }}</a>@include('site::partials.cta', ['kind' => 'wa'])</div>
    <div class="mt-3">@include('site::partials.cta', ['kind' => 'consult', 'class' => 'btn-sm'])</div>
  </div>
</section>
@endsection
