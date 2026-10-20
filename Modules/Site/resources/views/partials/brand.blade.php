{{-- @include('site::partials.brand', ['href' => '/']) — the logo: the full wordmark on desktop, the mark + name on phones.
     An uploaded logo (Settings → Brand) still wins over the bundled one. --}}
<a href="{{ $href }}" class="flex shrink-0 items-center">
  @if ($settings['logo'] ?? '')
    <img src="{{ \Modules\Site\Site::asset($settings['logo']) }}" alt="" aria-hidden="true" class="size-10 rounded-xl object-contain" width="40" height="40" />
    <span class="ml-2.5 num text-[17px] leading-none text-fg {{ ($hideName ?? false) ? 'hidden sm:block' : '' }}">{{ $site_name }}</span>
  @else
    <span class="hidden sm:block">{!! \Modules\Site\Site::logo('logo-full', 'h-9 w-auto lg:h-10') !!}</span>
    <span class="flex items-center gap-2 sm:hidden">{!! \Modules\Site\Site::logo('logo-mark', 'size-9') !!}<span class="num text-[17px] leading-none text-fg">{{ \Illuminate\Support\Str::beforeLast($site_name, ' ') ?: $site_name }}</span></span>
  @endif
</a>
