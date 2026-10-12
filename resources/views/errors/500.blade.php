@extends('site::layouts.app', ['title' => __('Something went wrong')])
@section('content')
<section class="container-x flex flex-col items-center py-24 text-center sm:py-32">
  <span class="pill-tag-light">500</span>
  <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Something went wrong') }}</h1>
  <p class="mt-3 max-w-md text-muted-fg">{{ __('Please try again in a moment.') }}</p>
  <a href="/" class="btn-dark mt-8">{{ __('Back to home') }}</a>
</section>
@endsection
