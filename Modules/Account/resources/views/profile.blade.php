@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('account::profile.title'), null]], 'title' => __('account::profile.title'), 'text' => __('account::profile.intro')])

<section class="container-x grid gap-6 pt-6 pb-16 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:pb-24">
  <div class="grid content-start gap-6">
    @if (session('status'))<p class="rounded-xl bg-fill px-4 py-3 text-sm font-medium text-fg" role="status">{{ session('status') }}</p>@endif

    <div class="card p-6 sm:p-8">
      <h2 class="font-display text-[30px] leading-[1.12] text-fg">{{ __('account::profile.photo') }}</h2>
      <div class="mt-5 flex items-center gap-5">
        <span data-avatar-preview class="inline-flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-full ring-1 ring-line">@include('account::partials.avatar', ['member' => $member, 'size' => 96])</span>
        <div class="flex flex-wrap gap-2">
          <label class="btn-consult btn-sm cursor-pointer">@include('site::partials.icon', ['name' => 'camera', 'class' => 'size-[18px]']) {{ __('account::profile.change_photo') }}<input type="file" accept="image/*" data-avatar-input class="sr-only" /></label>
          <button type="button" data-avatar-remove class="btn-ghost-light !h-10" @if (! $member->avatar) hidden @endif>{{ __('account::profile.remove_photo') }}</button>
        </div>
      </div>
    </div>

    <form method="post" action="/account/profile" class="card grid gap-4 p-6 sm:p-8">
      @csrf @method('PUT')
      <h2 class="font-display text-[30px] leading-[1.12] text-fg">{{ __('account::profile.details') }}</h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.name') }}</span><input name="name" value="{{ old('name', $member->name) }}" autocomplete="name" class="input h-12" />@error('name')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.email') }}</span><input name="email" type="email" value="{{ old('email', $member->email) }}" autocomplete="email" class="input h-12" />@error('email')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.phone') }}</span><input name="phone" value="{{ old('phone', \Modules\Account\Support\Identifier::local($member->phone)) }}" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="input h-12" />@error('phone')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
        <label class="grid gap-1.5 text-sm font-medium text-fg sm:col-span-2"><span>{{ __('account::profile.address') }}</span><textarea name="address" rows="3" class="input h-auto py-3">{{ old('address', $member->address) }}</textarea></label>
      </div>
      <div><button type="submit" class="btn-consult">{{ __('account::profile.save') }}</button></div>
    </form>
  </div>

  <div class="grid content-start gap-6">
    <form method="post" action="/account/profile/password" class="card grid gap-4 p-6 sm:p-8">
      @csrf @method('PUT')
      <h2 class="font-display text-[30px] leading-[1.12] text-fg">{{ $member->hasPassword() ? __('account::profile.change_password') : __('account::profile.set_password') }}</h2>
      @unless ($member->hasPassword())<p class="text-[15px] text-muted-fg">{{ __('account::profile.set_password_text') }}</p>@endunless
      @if ($member->hasPassword())
        <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.current_password') }}</span><input name="current_password" type="password" autocomplete="current-password" class="input h-12" />@error('current_password')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
      @endif
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.new_password') }}</span><input name="password" type="password" autocomplete="new-password" class="input h-12" />@error('password')<span class="text-[13px] font-normal text-brand-600" role="alert">{{ $message }}</span>@enderror</label>
      <label class="grid gap-1.5 text-sm font-medium text-fg"><span>{{ __('account::profile.confirm_password') }}</span><input name="password_confirmation" type="password" autocomplete="new-password" class="input h-12" /></label>
      <div><button type="submit" class="btn-consult">{{ __('account::profile.update_password') }}</button></div>
    </form>
    <div class="card flex items-center justify-between gap-4 p-6">
      <p class="text-sm text-muted-fg">@if ($member->google_id){{ __('account::profile.google_linked') }}@endif</p>
      <form method="post" action="/logout">@csrf<button type="submit" class="btn-ghost-light !h-10">{{ __('account::profile.logout') }}</button></form>
    </div>
  </div>
</section>

{{-- Frame the photo: drag to position, slide to zoom; the server makes the 256px WebP --}}
<dialog data-crop-dialog class="auth-dialog" aria-label="{{ __('account::profile.crop_title') }}">
  <div class="p-6 sm:p-8">
    <h2 class="font-display text-[30px] leading-[1.12] text-fg">{{ __('account::profile.crop_title') }}</h2>
    <p class="mt-1 text-sm text-muted-fg">{{ __('account::profile.crop_hint') }}</p>
    <div data-crop-stage class="relative mx-auto mt-5 size-[280px] cursor-grab touch-none overflow-hidden rounded-full bg-fill ring-1 ring-line active:cursor-grabbing"><img data-crop-img alt="" class="absolute top-0 left-0 max-w-none origin-top-left select-none" draggable="false" /></div>
    <label class="mx-auto mt-5 flex max-w-[280px] items-center gap-3 text-sm text-muted-fg"><span>{{ __('account::profile.zoom') }}</span><input data-crop-zoom type="range" class="w-full accent-ink-950" /></label>
    <div class="mt-6 flex gap-3"><button type="button" data-crop-cancel class="btn-ghost-light flex-1">{{ __('account::profile.cancel') }}</button><button type="button" data-crop-save class="btn-consult flex-1">{{ __('account::profile.use_photo') }}</button></div>
  </div>
</dialog>
@endsection
