@extends('site::layouts.app', ['title' => __('errors.not_found')])
@section('content')
<section class="container-x flex flex-col items-center py-24 text-center sm:py-32">
  <span class="pill-tag-light">404</span>
  <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('errors.not_found') }}</h1>
  <p class="mt-3 max-w-md text-muted-fg">{{ __('errors.not_found_text') }}</p>
  <div class="mt-8 flex flex-wrap justify-center gap-3"><a href="/" class="btn-dark">{{ __('errors.back_to_home') }}</a><a href="/projects" class="btn-outline">{{ __('errors.browse_projects') }}</a></div>
</section>
@endsection
