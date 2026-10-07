@extends('site::layouts.app', ['title' => 'Something went wrong'])
@section('content')
<section class="container-x flex flex-col items-center py-24 text-center sm:py-32">
  <span class="pill-tag-light">500</span>
  <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Something went wrong</h1>
  <p class="mt-3 max-w-md text-muted-fg">Please try again in a moment.</p>
  <a href="/" class="btn-dark mt-8">Back to home</a>
</section>
@endsection
