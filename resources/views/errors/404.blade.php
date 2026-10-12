@extends('site::layouts.app', ['title' => __('Page not found')])
@section('content')
<section class="container-x flex flex-col items-center py-24 text-center sm:py-32">
  <span class="pill-tag-light">404</span>
  <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Page not found') }}</h1>
  <p class="mt-3 max-w-md text-muted-fg">{{ __('The page you are looking for does not exist or was removed.') }}</p>
  <div class="mt-8 flex flex-wrap justify-center gap-3"><a href="/" class="btn-dark">{{ __('Back to home') }}</a><a href="/projects" class="btn-outline">{{ __('Browse projects') }}</a></div>
</section>
@endsection
