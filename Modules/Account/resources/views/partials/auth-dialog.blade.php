{{-- The auth sheet: a modal on desktop, a bottom sheet on phones. Opened by the Save button, the header and the menu. Guests only. --}}
<dialog id="auth-dialog" class="auth-dialog" aria-labelledby="auth-title" data-auth-dialog data-login-title="{{ __('account::auth.welcome_back') }}" data-register-title="{{ __('account::auth.register_title') }}" data-prompt-text="{{ __('account::auth.save_prompt_text') }}">
  <div class="relative p-6 pt-5 sm:p-8">
    <button type="button" data-dialog-close aria-label="{{ __('account::auth.close') }}" class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-full text-muted-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
    <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-line sm:hidden"></div>
    <h2 id="auth-title" class="font-display text-[28px] leading-tight text-fg" data-auth-title>{{ __('account::auth.welcome_back') }}</h2>
    <p class="mt-1.5 text-[15px] text-muted-fg" data-auth-prompt data-default="{{ __('account::auth.save_prompt_text') }}">{{ __('account::auth.save_prompt_text') }}</p>

    <div class="mt-5 grid grid-cols-2 gap-1 rounded-full bg-fill p-1" role="tablist">
      <button type="button" role="tab" data-auth-tab="login" aria-selected="true" class="auth-tab">{{ __('account::auth.tab_login') }}</button>
      <button type="button" role="tab" data-auth-tab="register" aria-selected="false" class="auth-tab">{{ __('account::auth.tab_register') }}</button>
    </div>

    <div class="mt-5" data-auth-panel="login">@include('account::partials.auth-fields', ['mode' => 'login', 'redirect' => '/', 'dialog' => true])</div>
    <div class="mt-5" data-auth-panel="register" hidden>@include('account::partials.auth-fields', ['mode' => 'register', 'redirect' => '/', 'dialog' => true])</div>
    <p class="mt-4 text-center text-xs text-muted-fg">{{ __('account::auth.privacy_note') }}</p>
  </div>
</dialog>
