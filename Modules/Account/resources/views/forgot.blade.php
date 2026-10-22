@extends('site::layouts.app')
@section('content')
<section class="container-x flex justify-center pt-8 pb-16 lg:pt-14 lg:pb-24">
  <div class="w-full max-w-[460px] rounded-[24px] border border-line bg-surface p-6 sm:p-9">
    @if ($step === 'identifier')
      <h1 class="font-display text-[40px] leading-[1.1] text-fg">{{ __('account::auth.forgot_title') }}</h1>
      <p class="mt-2 text-[15px] text-muted-fg">{{ __('account::auth.send_code_hint') }}</p>
      @if (session('status'))<p class="mt-4 rounded-xl bg-fill px-3 py-2 text-sm text-fg" role="status">{{ session('status') }}</p>@endif
      <form method="post" action="/forgot-password" class="mt-6 grid gap-4">
        @csrf
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.identifier') }}</span><input name="identifier" value="{{ old('identifier') }}" required autocomplete="username" placeholder="{{ __('account::auth.identifier_placeholder') }}" class="input h-12" />@error('identifier')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <button type="submit" class="btn-consult h-12 w-full">{{ __('account::auth.send_reset') }}</button>
      </form>
    @elseif ($step === 'code')
      <h1 class="font-display text-[40px] leading-[1.1] text-fg">{{ __('account::auth.code_title') }}</h1>
      <p class="mt-2 text-[15px] text-muted-fg">{{ __('account::auth.code_intro') }}</p>
      @if (session('status'))<p class="mt-4 rounded-xl bg-fill px-3 py-2 text-sm text-fg" role="status">{{ session('status') }}</p>@endif
      <form method="post" action="/forgot-password/code" class="mt-6 grid gap-4">
        @csrf
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.mobile_label') }}</span><input name="phone" value="{{ old('phone', $phone) }}" required inputmode="tel" autocomplete="tel" class="input h-12" /></label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.code_label') }}</span><input name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="input h-12 tracking-[0.3em]" />@error('code')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.new_password') }}</span><input name="password" type="password" required autocomplete="new-password" class="input h-12" />@error('password')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.confirm_password') }}</span><input name="password_confirmation" type="password" required autocomplete="new-password" class="input h-12" /></label>
        <button type="submit" class="btn-consult h-12 w-full">{{ __('account::auth.reset_password') }}</button>
      </form>
    @else
      <h1 class="font-display text-[40px] leading-[1.1] text-fg">{{ __('account::auth.reset_title') }}</h1>
      <form method="post" action="/reset-password" class="mt-6 grid gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />
        <input type="hidden" name="email" value="{{ old('email', $email) }}" />
        @error('email')<p class="rounded-xl bg-brand-50 px-3 py-2 text-sm text-brand-600" role="alert">{{ $message }}</p>@enderror
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.new_password') }}</span><input name="password" type="password" required autocomplete="new-password" class="input h-12" />@error('password')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.confirm_password') }}</span><input name="password_confirmation" type="password" required autocomplete="new-password" class="input h-12" /></label>
        <button type="submit" class="btn-consult h-12 w-full">{{ __('account::auth.reset_password') }}</button>
      </form>
    @endif
    <p class="mt-6 text-center text-sm"><a href="/login" class="font-semibold text-fg underline-offset-4 hover:underline">{{ __('account::auth.back_to_login') }}</a></p>
  </div>
</section>
@endsection
