@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[$heading, null]], 'title' => $heading, 'text' => $page === 'privacy' ? __('home::legal.privacy_description') : __('home::legal.terms_description'), 'chip' => __('home::legal.eyebrow')])
<section class="container-x py-10 sm:py-14">
  <div class="prose-light mx-auto max-w-3xl sm:text-lg">
    @if (trim($body))
      {!! nl2br(e($body)) !!}
    @else
      <p class="text-muted-fg">{{ __('home::legal.empty') }}</p>
    @endif
  </div>
</section>
@endsection
