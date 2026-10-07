{{-- @include('site::partials.brand', ['href' => '/', 'sub' => 'Real Estate']) --}}
<a href="{{ $href }}" class="group flex shrink-0 items-center gap-2.5">
  @if ($settings['logo'] ?? '')
    <img src="{{ \Modules\Site\Site::asset($settings['logo']) }}" alt="{{ $site_name }}" class="size-10 rounded-xl object-contain lg:size-11" />
  @else
    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-ink-950 font-display text-lg font-extrabold text-white lg:size-11 lg:text-xl">{{ mb_substr($site_name, 0, 1) }}</span>
  @endif
  <span class="hidden sm:block">
    <span class="block font-display text-base font-extrabold leading-tight tracking-tight text-fg lg:text-lg">{{ $site_name }}</span>
    <span class="block text-[11px] font-medium uppercase tracking-wider text-muted-fg">{{ $sub }}</span>
  </span>
</a>
