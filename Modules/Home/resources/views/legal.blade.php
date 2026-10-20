@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[$heading, null]], 'title' => $heading, 'text' => $page === 'privacy' ? __('home::legal.privacy_description') : __('home::legal.terms_description'), 'chip' => __('home::legal.eyebrow')])
<article class="container-x pt-8 pb-16 lg:pb-24">
  <div class="legal-prose mx-auto max-w-[720px] border-t border-line pt-8">
    @if (trim($body))
      {!! nl2br(e($body)) !!}
    @else
      <p class="text-muted-fg">{{ __('home::legal.empty') }}</p>
    @endif
  </div>
</article>
@endsection
