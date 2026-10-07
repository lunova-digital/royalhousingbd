@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[$heading, null]], 'title' => $heading, 'text' => $heading === 'Privacy Policy' ? 'What we collect, how we use it and your rights.' : 'The terms that apply when you use this website and book with us.', 'chip' => 'Legal'])
<section class="container-x py-10 sm:py-14">
  <div class="prose-light mx-auto max-w-3xl sm:text-lg">
    @if (trim($body))
      {!! nl2br(e($body)) !!}
    @else
      <p class="text-muted-fg">This page has not been written yet. Add the text under Settings → Legal in the admin panel.</p>
    @endif
  </div>
</section>
@endsection
