{{-- One auth form: Google first, a divider, then the identifier + password form.
     @include('account::partials.auth-fields', ['mode' => 'login'|'register', 'redirect' => '/', 'dialog' => bool]) --}}
@php($google = \Modules\Account\Http\Controllers\SocialController::configured())
@php($redirect = $redirect ?? '/')
@if ($google)
  <a href="/auth/google?redirect={{ urlencode($redirect) }}" data-google-link class="mb-4 flex h-12 items-center justify-center gap-3 rounded-full border border-line bg-white text-[15px] font-semibold text-fg transition-colors duration-150 hover:bg-fill">
    <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true"><path fill="#4285F4" d="M22.5 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h5.9a5.05 5.05 0 0 1-2.19 3.31v2.75h3.55c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.55-2.75c-.98.66-2.24 1.06-3.73 1.06-2.87 0-5.3-1.94-6.17-4.55H2.15v2.84A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.83 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.15a11 11 0 0 0 0 9.88z"/><path fill="#EA4335" d="M12 5.35c1.62 0 3.07.56 4.21 1.65l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.15 7.06l3.68 2.84C6.7 7.29 9.13 5.35 12 5.35z"/></svg>
    {{ __('account::auth.continue_google') }}
  </a>
  <div class="mb-4 flex items-center gap-3 text-xs font-medium text-muted-fg"><span class="h-px flex-1 bg-line"></span>{{ __('account::auth.or') }}<span class="h-px flex-1 bg-line"></span></div>
@endif

<form method="post" action="{{ $mode === 'register' ? '/register' : '/login' }}" data-auth-form="{{ $mode }}" novalidate class="grid gap-4">
  @csrf
  <input type="hidden" name="redirect" value="{{ $redirect }}" />
  @if ($mode === 'register')
    <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::auth.name_optional') }}</span><input name="name" autocomplete="name" value="{{ old('name') }}" class="input h-12" /></label>
  @endif
  <label class="grid gap-1.5 text-sm font-medium text-fg" data-field="identifier"><span>{{ __('account::auth.identifier') }}</span>
    <input name="identifier" value="{{ old('identifier') }}" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required placeholder="{{ __('account::auth.identifier_placeholder') }}" class="input h-12" @if ($errors->has('identifier')) aria-invalid="true" @endif />
    <span data-error class="text-[13px] font-normal text-brand-600" role="alert" @if (! $errors->has('identifier')) hidden @endif>{{ $errors->first('identifier') }}</span>
  </label>
  <label class="grid gap-1.5 text-sm font-medium text-fg" data-field="password"><span>{{ __('account::auth.password') }}</span>
    <span class="relative block"><input name="password" type="password" autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}" required placeholder="{{ $mode === 'register' ? __('account::auth.password_new') : '' }}" class="input h-12 pr-12" />
      <button type="button" data-toggle-password aria-label="{{ __('account::auth.show_password') }}" data-show="{{ __('account::auth.show_password') }}" data-hide="{{ __('account::auth.hide_password') }}" class="absolute top-1/2 right-1.5 inline-flex size-9 -translate-y-1/2 items-center justify-center rounded-full text-muted-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'eye', 'class' => 'size-5'])</button></span>
    <span data-error class="text-[13px] font-normal text-brand-600" role="alert" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</span>
  </label>
  @if ($mode === 'login')
    <div class="flex items-center justify-between gap-3 text-sm">
      <label class="flex cursor-pointer items-center gap-2 text-text"><input type="checkbox" name="remember" value="1" checked class="size-[18px] rounded border-faint-fg accent-ink-950" /> {{ __('account::auth.remember') }}</label>
      <a href="/forgot-password" class="font-medium text-fg underline-offset-4 hover:underline">{{ __('account::auth.forgot_link') }}</a>
    </div>
  @endif
  <p data-form-error class="rounded-xl bg-brand-50 px-3 py-2 text-sm text-brand-600" role="alert" hidden></p>
  <button type="submit" class="btn-consult h-12 w-full" data-submit>{{ $mode === 'register' ? __('account::auth.register') : __('account::auth.login') }}</button>
</form>
