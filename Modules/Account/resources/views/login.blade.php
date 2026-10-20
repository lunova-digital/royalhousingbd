@extends('site::layouts.app')
@section('content')
<section class="container-x flex justify-center pt-8 pb-16 lg:pt-14 lg:pb-24">
  <div class="w-full max-w-[460px] rounded-[24px] border border-line bg-white p-6 sm:p-9">
    <h1 class="font-display text-[34px] leading-tight text-fg">{{ $mode === 'register' ? __('account::auth.register_title') : __('account::auth.login_title') }}</h1>
    @if (session('status'))<p class="mt-3 rounded-xl bg-fill px-3 py-2 text-sm text-fg" role="status">{{ session('status') }}</p>@endif
    <div class="mt-6">@include('account::partials.auth-fields', ['mode' => $mode, 'redirect' => $redirect, 'dialog' => false])</div>
    <p class="mt-6 text-center text-sm text-muted-fg">
      @if ($mode === 'register'){{ __('account::auth.have_account') }} <a href="/login?redirect={{ urlencode($redirect) }}" class="font-semibold text-fg underline-offset-4 hover:underline">{{ __('account::auth.login') }}</a>
      @else{{ __('account::auth.no_account') }} <a href="/register?redirect={{ urlencode($redirect) }}" class="font-semibold text-fg underline-offset-4 hover:underline">{{ __('account::auth.register') }}</a>@endif
    </p>
  </div>
</section>
@endsection
