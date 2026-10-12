<!doctype html>
<html lang="{{ app()->getLocale() }}" class="bg-page">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <title>{{ $title }}</title>
    @if ($description)<meta name="description" content="{{ $description }}" />@endif
    <link rel="canonical" href="{{ $canonical }}" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $site_name }}" />
    <meta property="og:title" content="{{ $title }}" />
    @if ($description)<meta property="og:description" content="{{ $description }}" />@endif
    <meta property="og:url" content="{{ $canonical }}" />
    @if ($og_image)<meta property="og:image" content="{{ $og_image }}" />@endif
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="theme-color" content="#0b0d12" />
    @if ($settings['logo'] ?? '')<link rel="icon" href="{{ \Modules\Site\Site::asset($settings['logo']) }}" />@endif
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:600,700,800&display=swap" rel="stylesheet" />
    @if (app()->getLocale() === 'bn')<link href="https://fonts.bunny.net/css?family=noto-sans-bengali:400,500,600,700&display=swap" rel="stylesheet" />@endif
    @vite('resources/css/app.css')
    {{ Modules::vite('site', 'resources/js/site.ts') }}
    @stack('head')
  </head>
  <body class="min-h-svh bg-page font-body text-base text-fg antialiased pb-16 lg:pb-0">
    @php($path = '/'.ltrim(request()->path(), '/'))
    @php($whatsapp = $settings['whatsapp_number'] ?? '')
    @php($phone = $settings['phone'] ?? '')
    @php($locale = app()->getLocale())

    {{-- Utility strip --}}
    <div class="bg-ink-950 text-xs text-white/80">
      <div class="container-x flex h-9 items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
          @if ($settings['address'] ?? '')
            <span class="flex min-w-0 items-center gap-1.5">@include('site::partials.icon', ['name' => 'pin', 'class' => 'size-3.5 shrink-0'])<span class="truncate">{{ __('site::layout.visit_us') }} <strong class="font-semibold text-white">{{ \Illuminate\Support\Str::limit($settings['address'], 40) }}</strong></span></span>
          @endif
          <span class="hidden h-3.5 w-px bg-white/15 sm:block"></span>
          <a href="/map" class="hidden hover:text-white sm:block">{{ __('site::common.project_map') }}</a>
          <a href="/gallery" class="hidden hover:text-white sm:block">{{ __('site::common.gallery') }}</a>
          <a href="/contact" class="hidden hover:text-white sm:block">{{ __('site::layout.help_contact') }}</a>
        </div>
        <div class="flex shrink-0 items-center gap-4">
          @if ($phone)<a href="tel:{{ $phone }}" class="flex items-center gap-1.5 hover:text-white">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-3.5'])<span class="hidden sm:inline">{{ $phone }}</span><span class="sm:hidden">{{ __('site::layout.call') }}</span></a>@endif
          @if ($settings['email'] ?? '')<span class="hidden h-3.5 w-px bg-white/15 md:block"></span><a href="mailto:{{ $settings['email'] }}" class="hidden hover:text-white md:block">{{ $settings['email'] }}</a>@endif
          {{-- Language toggle: `?lang=` is remembered by the SetLocale middleware --}}
          <span class="flex items-center overflow-hidden rounded-md border border-white/15 text-[11px] font-semibold leading-none" role="group" aria-label="{{ __('site::layout.language') }}">
            @foreach (\Modules\Site\Site::LOCALES as $code => $label)
              <a href="{{ \Modules\Site\Site::localeUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" rel="nofollow" class="px-2 py-1 transition-colors {{ $code === $locale ? 'bg-white text-fg' : 'text-white/70 hover:text-white' }}" @if ($code === $locale) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
          </span>
        </div>
      </div>
    </div>

    <header class="border-b border-line bg-white">
      {{-- Brand, search, actions --}}
      <div class="container-x">
        <div class="flex h-16 items-center gap-3 lg:h-20 lg:gap-6">
          @include('site::partials.brand', ['href' => '/', 'sub' => __('site::layout.real_estate')])
          <form action="/projects" method="get" class="hidden flex-1 items-center overflow-hidden rounded-full bg-fill pl-2 md:flex" role="search">
            <label class="relative shrink-0 border-r border-line">
              <span class="sr-only">{{ __('site::common.type') }}</span>
              <select name="type" class="h-11 appearance-none bg-transparent pr-8 pl-4 text-sm font-medium text-fg outline-none">
                <option value="">{{ __('site::common.all') }}</option>
                @foreach ($types as $t)<option value="{{ $t['key'] }}">{{ $t['label'] }}</option>@endforeach
              </select>
              <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-muted-fg">@include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5'])</span>
            </label>
            <input type="search" name="q" placeholder="{{ __('site::layout.search_placeholder') }}" class="h-11 min-w-0 flex-1 bg-transparent px-4 text-sm text-fg outline-none placeholder:text-faint-fg" />
            <button type="submit" class="btn-dark m-1 h-10 px-5">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4']) {{ __('site::common.search') }}</button>
          </form>
          <div class="ml-auto flex items-center gap-1 sm:gap-2">
            @if ($phone)<a href="tel:{{ $phone }}" class="flex flex-col items-center gap-0.5 rounded-xl px-2 py-1.5 text-fg hover:bg-fill sm:px-3" aria-label="{{ __('site::common.call_us') }}">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-5'])<span class="text-[11px] text-muted-fg">{{ __('site::layout.call') }}</span></a>@endif
            @if ($whatsapp)<a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="flex flex-col items-center gap-0.5 rounded-xl px-2 py-1.5 text-fg hover:bg-fill sm:px-3" aria-label="WhatsApp">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5'])<span class="text-[11px] text-muted-fg">{{ __('site::common.whatsapp') }}</span></a>@endif
            <a href="/map" class="flex flex-col items-center gap-0.5 rounded-xl px-2 py-1.5 text-fg hover:bg-fill sm:px-3" aria-label="{{ __('site::common.project_map') }}">@include('site::partials.icon', ['name' => 'map', 'class' => 'size-5'])<span class="text-[11px] text-muted-fg">{{ __('site::layout.map') }}</span></a>
          </div>
        </div>
        <form action="/projects" method="get" class="mb-3 flex items-center overflow-hidden rounded-full bg-fill md:hidden" role="search">
          <span class="pl-4 text-muted-fg">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
          <input type="search" name="q" placeholder="{{ __('site::layout.search_placeholder') }}" class="h-11 min-w-0 flex-1 bg-transparent px-3 text-sm text-fg outline-none placeholder:text-faint-fg" />
          <button type="submit" class="btn-dark m-1 h-9 px-4">{{ __('site::common.search') }}</button>
        </form>
      </div>

      {{-- Category row --}}
      <div class="border-t border-line">
        <div class="container-x flex h-14 items-center gap-2">
          <a href="/projects" class="btn-dark h-10 shrink-0 rounded-xl px-4">@include('site::partials.icon', ['name' => 'menu', 'class' => 'size-4']) {{ __('site::layout.all_projects_menu') }} @include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5 opacity-70'])</a>
          <nav class="scroll-row flex min-w-0 flex-1 items-center gap-1 overflow-x-auto" aria-label="{{ __('site::layout.main_nav') }}">
            @foreach ($nav as $item)
              @if ($item['key'] === 'map')
                @foreach ($types as $t)<a href="{{ $t['href'] }}" class="pill-nav {{ $path === $t['href'] ? 'pill-nav-active' : '' }}">{{ $t['label'] }}</a>@endforeach
              @endif
              <a href="{{ $item['href'] }}" class="pill-nav {{ $nav_active === $item['key'] && ! in_array($path, array_column($types, 'href'), true) ? 'pill-nav-active' : '' }}">{{ __($item['label']) }}</a>
            @endforeach
          </nav>
          <span class="hidden shrink-0 items-center gap-1.5 text-sm font-semibold text-success xl:flex">@include('site::partials.icon', ['name' => 'shield-check', 'class' => 'size-4']) {{ __('site::layout.verified_documents') }}</span>
        </div>
      </div>
    </header>

    <main>
      @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-16 bg-ink-900 text-white">
      <div class="container-x py-12 lg:py-16">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_1fr]">
          <div>
            <a href="/" class="inline-flex items-center gap-3">
              @if ($settings['logo'] ?? '')
                <img src="{{ \Modules\Site\Site::asset($settings['logo']) }}" alt="{{ $site_name }}" class="size-11 rounded-xl bg-white object-contain p-1" />
              @else
                <span class="inline-flex size-11 items-center justify-center rounded-xl bg-white font-display text-xl font-extrabold text-brand">{{ mb_substr($site_name, 0, 1) }}</span>
              @endif
              <span class="font-display text-lg font-bold">{{ $site_name }}</span>
            </a>
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/65">{{ $settings['about_text'] ?: __('site::layout.footer_blurb') }}</p>
            <h3 class="mt-8 text-xs font-bold uppercase tracking-[0.14em] text-white">{{ __('site::common.contact_us') }}</h3>
            <ul class="mt-3 space-y-2 text-sm text-white/65">
              @if ($phone)<li>{{ __('site::common.hotline') }}: <a href="tel:{{ $phone }}" class="hover:text-white">{{ $phone }}</a></li>@endif
              @if ($settings['email'] ?? '')<li>{{ __('site::common.email') }}: <a href="mailto:{{ $settings['email'] }}" class="hover:text-white">{{ $settings['email'] }}</a></li>@endif
              @if ($settings['address'] ?? '')<li>{{ __('site::common.office') }}: {{ $settings['address'] }}</li>@endif
            </ul>
            @if ($whatsapp || $phone)
              <h3 class="mt-8 text-xs font-bold uppercase tracking-[0.14em] text-white">{{ __('site::layout.talk_to_us') }}</h3>
              <div class="mt-3 flex flex-wrap gap-2">
                @if ($whatsapp)<a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2.5 rounded-xl bg-white px-3.5 py-2 text-fg">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-5'])<span><span class="block text-[10px] uppercase tracking-wider text-muted-fg">{{ __('site::common.chat_on') }}</span><span class="block text-sm font-bold">{{ __('site::common.whatsapp') }}</span></span></a>@endif
                @if ($phone)<a href="tel:{{ $phone }}" class="inline-flex items-center gap-2.5 rounded-xl border border-white/15 bg-white/5 px-3.5 py-2 text-white">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-5'])<span><span class="block text-[10px] uppercase tracking-wider text-white/50">{{ __('site::common.call_us') }}</span><span class="block text-sm font-bold">{{ __('site::common.hotline') }}</span></span></a>@endif
              </div>
            @endif
          </div>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.14em]">{{ __('site::common.projects') }}</h3>
            <ul class="mt-4 space-y-2.5">
              <li><a href="/projects" class="link-muted">{{ __('site::common.all_projects') }}</a></li>
              @foreach ($types as $t)<li><a href="{{ $t['href'] }}" class="link-muted">{{ __('site::common.type_listings', ['type' => $t['label']]) }}</a></li>@endforeach
              <li><a href="/map" class="link-muted">{{ __('site::layout.interactive_project_map') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.14em]">{{ __('site::layout.company') }}</h3>
            <ul class="mt-4 space-y-2.5">
              <li><a href="/about" class="link-muted">{{ __('site::layout.about_us') }}</a></li>
              <li><a href="/about#team" class="link-muted">{{ __('site::common.our_team') }}</a></li>
              <li><a href="/gallery" class="link-muted">{{ __('site::common.gallery') }}</a></li>
              <li><a href="/contact" class="link-muted">{{ __('site::common.contact_us') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.14em]">{{ __('site::layout.support') }}</h3>
            <ul class="mt-4 space-y-2.5">
              <li><a href="/consultation" class="link-muted">{{ __('site::layout.free_consultation') }}</a></li>
              <li><a href="/contact" class="link-muted">{{ __('site::layout.book_site_visit') }}</a></li>
              @if ($whatsapp)<li><a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="link-muted">{{ __('site::layout.whatsapp_enquiry') }}</a></li>@endif
              <li><a href="/privacy" class="link-muted">{{ __('site::layout.privacy_policy') }}</a></li>
              <li><a href="/terms" class="link-muted">{{ __('site::layout.terms_of_service') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.14em]">{{ __('site::layout.follow') }}</h3>
            <div class="mt-4 flex flex-wrap gap-2">
              @foreach (['facebook_url' => 'facebook', 'instagram_url' => 'instagram', 'youtube_url' => 'youtube', 'tiktok_url' => 'tiktok'] as $key => $icon)
                @if ($settings[$key] ?? '')
                  <a href="{{ $settings[$key] }}" target="_blank" rel="noreferrer" aria-label="{{ ucfirst($icon) }}" class="flex size-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/80 transition-colors hover:bg-white hover:text-fg">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-5'])</a>
                @endif
              @endforeach
            </div>
          </div>
        </div>
        <div class="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-white/55 md:flex-row md:items-center md:justify-between">
          <p>© {{ date('Y') }} {{ $site_name }}. {{ __('site::layout.all_rights_reserved') }}</p>
          <p class="flex items-center gap-3"><span>{{ __('site::layout.site_visits') }}</span><span class="rounded-md bg-white px-2.5 py-1 text-xs font-bold text-fg">{{ __('site::layout.by_appointment') }}</span></p>
        </div>
      </div>
    </footer>

    {{-- Bottom tab bar on phones and tablets --}}
    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-line bg-white lg:hidden" aria-label="{{ __('site::layout.quick_navigation') }}" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
      <div class="flex h-16 items-stretch justify-around px-1">
        @foreach (array_values(array_filter($nav, fn ($i) => $i['key'] !== 'gallery')) as $item)
          @php($active = $nav_active === $item['key'])
          <a href="{{ $item['href'] }}" class="relative flex flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors active:scale-95 {{ $active ? 'font-semibold text-fg' : 'text-muted-fg' }}">
            @if ($active)<span class="absolute inset-x-3 top-0 h-0.5 rounded-b bg-ink-950"></span>@endif
            @include('site::partials.icon', ['name' => $item['icon'], 'class' => 'size-5'])
            <span class="max-w-[64px] truncate">{{ __($item['label']) }}</span>
          </a>
        @endforeach
      </div>
    </nav>

    {{-- Floating actions: the free consultation launcher (client's top priority) above the WhatsApp icon (FR-COMM-001) --}}
    <div class="fixed right-4 bottom-20 z-40 flex flex-col items-end gap-3 lg:right-6 lg:bottom-6">
      @includeIf('consultation::partials.launcher')
      @if ($whatsapp)
        <a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" aria-label="{{ __('site::common.chat_on_whatsapp') }}"
           class="inline-flex size-14 items-center justify-center rounded-full bg-whatsapp text-white shadow-lg shadow-black/20 transition-transform hover:scale-105">
          @include('site::partials.whatsapp-icon')
        </a>
      @endif
    </div>

    {{-- Lightbox for galleries --}}
    <div data-lightbox hidden class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4">
      <button type="button" data-lightbox-close aria-label="{{ __('site::common.close') }}" class="absolute top-4 right-4 inline-flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
      <img data-lightbox-img src="" alt="" class="max-h-[90svh] max-w-full rounded-2xl object-contain" />
    </div>
    @stack('scripts')
  </body>
</html>
