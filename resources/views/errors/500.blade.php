@extends('site::layouts.app', ['title' => __('errors.server_error')])
@section('content')
<section class="container-x flex flex-col items-center py-20 text-center sm:py-28">
  <span class="inline-flex size-16 items-center justify-center rounded-full bg-white text-accent-text ring-1 ring-line">@include('site::partials.icon', ['name' => 'info', 'class' => 'size-7'])</span>
  <p class="eyebrow mt-6">500</p>
  <h1 class="mt-2 font-display text-[38px] leading-[1.08] text-fg sm:text-[46px]">{{ __('errors.server_error') }}</h1>
  <p class="mt-3 max-w-md text-base text-muted-fg">{{ __('errors.server_error_text') }}</p>
  <div class="mt-8"><a href="/" class="btn-consult">{{ __('errors.back_to_home') }}</a></div>
  <p class="mt-10 text-sm text-muted-fg">{{ __('errors.need_help') }}</p>
  <div class="mt-3 flex flex-col gap-3 sm:flex-row">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'btn-sm'])@include('site::partials.cta', ['kind' => 'consult', 'class' => 'btn-sm'])</div>
</section>
@endsection
