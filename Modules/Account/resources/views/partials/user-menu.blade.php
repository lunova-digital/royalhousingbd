{{-- Desktop header: the member's avatar with Saved · Profile · Log out. --}}
<div class="relative hidden lg:block" data-user-menu>
  <button type="button" data-user-toggle aria-haspopup="menu" aria-expanded="false" aria-label="{{ __('account::auth.menu_account') }}" class="flex size-10 items-center justify-center rounded-full ring-1 ring-line transition-shadow hover:ring-action">@include('account::partials.avatar', ['member' => $acct, 'size' => 38])</button>
  <div role="menu" data-user-panel hidden class="absolute top-[calc(100%+10px)] right-0 z-50 w-64 rounded-2xl border border-line bg-surface p-2 shadow-lift">
    <div class="px-3 pt-2 pb-3"><p class="truncate font-semibold text-fg">{{ $acct->displayName() }}</p><p class="truncate text-sm text-muted-fg">{{ $acct->email ?: \Modules\Account\Support\Identifier::local($acct->phone) }}</p></div>
    <a role="menuitem" href="/account/saved" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'heart', 'class' => 'size-[18px] text-muted-fg']) {{ __('account::auth.menu_saved') }}</a>
    <a role="menuitem" href="/account/profile" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'user', 'class' => 'size-[18px] text-muted-fg']) {{ __('account::auth.menu_profile') }}</a>
    <form method="post" action="/logout" class="mt-1 border-t border-line pt-1">@csrf<button role="menuitem" type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'log-out', 'class' => 'size-[18px] text-muted-fg']) {{ __('account::auth.menu_logout') }}</button></form>
  </div>
</div>
