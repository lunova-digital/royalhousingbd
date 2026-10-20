{{-- @include('site::partials.brand', ['href' => '/']) — the logo mark (brand red) + the site name --}}
<a href="{{ $href }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ $site_name }}">
  @if ($settings['logo'] ?? '')
    <img src="{{ \Modules\Site\Site::asset($settings['logo']) }}" alt="" class="size-10 rounded-xl object-contain" width="40" height="40" />
  @else
    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-brand font-display text-lg font-bold text-white">{{ mb_substr($site_name, 0, 1) }}</span>
  @endif
  <span class="font-display text-[17px] leading-none font-bold tracking-[-0.02em] text-fg {{ ($hideName ?? false) ? 'hidden sm:block' : '' }}">{{ $site_name }}</span>
</a>
