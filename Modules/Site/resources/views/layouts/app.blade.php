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
    <meta name="theme-color" content="#faf8f5" />
    @if ($settings['logo'] ?? '')<link rel="icon" href="{{ \Modules\Site\Site::asset($settings['logo']) }}" />@endif
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:700&display=swap" rel="stylesheet" />
    @if (app()->getLocale() === 'bn')<link href="https://fonts.bunny.net/css?family=hind-siliguri:400,500,600&display=swap" rel="stylesheet" />@endif
    <script>document.documentElement.classList.add('js')</script>
    @vite('resources/css/app.css')
    {{ Modules::vite('site', 'resources/js/site.ts') }}
    @stack('head')
  </head>
  <body class="min-h-svh bg-page font-body text-base text-text antialiased pb-[calc(4rem+env(safe-area-inset-bottom,0px))] lg:pb-0">
    @php($path = '/'.ltrim(request()->path(), '/'))
    @php($whatsapp = $settings['whatsapp_number'] ?? '')
    @php($phone = $settings['phone'] ?? '')
    @php($locale = app()->getLocale())
    @php($typeHrefs = array_column($types, 'href'))
    @php($onType = in_array($path, $typeHrefs, true))
    @php($consultOn = \Modules\Consultation\Availability::enabled())

    @yield('ticker')

    <header class="sticky top-0 z-40 border-b border-line bg-white">
      <div class="container-x flex h-16 items-center gap-4 lg:h-[72px] lg:gap-8">
        @include('site::partials.brand', ['href' => '/'])

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('site::layout.main_nav') }}">
          <a href="/projects" class="nav-link" @if ($nav_active === 'projects' && ! $onType) aria-current="page" @endif>{{ __('site::layout.nav_projects') }}</a>
          <div class="group relative">
            <button type="button" class="nav-link" aria-haspopup="true" @if ($onType) aria-current="page" @endif>{{ __('site::layout.nav_listings') }} @include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5 text-muted-fg'])</button>
            <div class="invisible absolute top-full left-0 z-50 min-w-52 pt-2 opacity-0 transition duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
              <ul class="rounded-2xl border border-line bg-white p-2 shadow-lift">
                @foreach ($types as $t)<li><a href="{{ $t['href'] }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-text hover:bg-fill hover:text-fg">{{ $t['label'] }}</a></li>@endforeach
              </ul>
            </div>
          </div>
          <a href="/map" class="nav-link" @if ($nav_active === 'map') aria-current="page" @endif>{{ __('site::layout.nav_map') }}</a>
          <a href="/gallery" class="nav-link" @if ($nav_active === 'gallery') aria-current="page" @endif>{{ __('site::layout.nav_gallery') }}</a>
          <a href="/about" class="nav-link" @if ($nav_active === 'about') aria-current="page" @endif>{{ __('site::layout.nav_about') }}</a>
        </nav>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
          <button type="button" data-open-dialog="search-dialog" aria-label="{{ __('site::layout.search_open') }}" class="inline-flex size-10 items-center justify-center rounded-full text-fg transition-colors hover:bg-fill">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-5'])</button>
          {{-- Language toggle: `?lang=` is remembered by the SetLocale middleware --}}
          <span class="flex items-center overflow-hidden rounded-full border border-line text-xs font-semibold leading-none" role="group" aria-label="{{ __('site::layout.language') }}">
            @foreach (\Modules\Site\Site::LOCALES as $code => $label)
              <a href="{{ \Modules\Site\Site::localeUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" rel="nofollow" class="px-3 py-2 transition-colors {{ $code === $locale ? 'bg-ink-950 text-white' : 'text-muted-fg hover:text-fg' }}" @if ($code === $locale) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
          </span>
          <span class="hidden lg:block">@include('site::partials.cta', ['kind' => 'consult', 'class' => 'btn-sm'])</span>
        </div>
      </div>
    </header>

    <main>
      @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-6 lg:mt-12">
      <div class="container-x">
        <div class="on-dark rounded-[28px] bg-ink-950 p-6 text-white sm:p-10 lg:flex lg:items-center lg:justify-between lg:gap-10">
          <div class="max-w-xl">
            <a href="/" class="flex items-center gap-2.5" aria-label="{{ $site_name }}">
              @if ($settings['logo'] ?? '')
                <img src="{{ \Modules\Site\Site::asset($settings['logo']) }}" alt="" class="size-10 rounded-xl bg-white object-contain p-1" width="40" height="40" loading="lazy" />
              @else
                <span class="inline-flex size-10 items-center justify-center rounded-xl bg-brand font-display text-lg font-bold text-white">{{ mb_substr($site_name, 0, 1) }}</span>
              @endif
              <span class="font-display text-lg font-bold tracking-[-0.02em]">{{ $site_name }}</span>
            </a>
            <p class="mt-4 text-[15px] leading-relaxed text-white/70">{{ $settings['about_text'] ? \Illuminate\Support\Str::words($settings['about_text'], 18) : __('site::layout.footer_blurb_short') }}</p>
          </div>
          <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0 lg:shrink-0">
            @include('site::partials.cta', ['kind' => 'wa'])
            @include('site::partials.cta', ['kind' => 'consult', 'dark' => true])
          </div>
        </div>

        <div class="grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-fg">{{ __('site::layout.footer_listings') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
              <li><a href="/projects" class="text-muted-fg hover:text-fg">{{ __('site::common.all_projects') }}</a></li>
              @foreach ($types as $t)<li><a href="{{ $t['href'] }}" class="text-muted-fg hover:text-fg">{{ $t['label'] }}</a></li>@endforeach
              <li><a href="/map" class="text-muted-fg hover:text-fg">{{ __('site::layout.interactive_project_map') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-fg">{{ __('site::layout.company') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
              <li><a href="/about" class="text-muted-fg hover:text-fg">{{ __('site::layout.about_us') }}</a></li>
              <li><a href="/about#team" class="text-muted-fg hover:text-fg">{{ __('site::common.our_team') }}</a></li>
              <li><a href="/gallery" class="text-muted-fg hover:text-fg">{{ __('site::common.gallery') }}</a></li>
              <li><a href="/contact" class="text-muted-fg hover:text-fg">{{ __('site::common.contact_us') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-fg">{{ __('site::layout.support') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
              <li><a href="/consultation" class="text-muted-fg hover:text-fg">{{ __('site::layout.free_consultation') }}</a></li>
              <li><a href="/contact" class="text-muted-fg hover:text-fg">{{ __('site::layout.book_site_visit') }}</a></li>
              <li><a href="/#faq" class="text-muted-fg hover:text-fg">{{ __('home::index.faq') }}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-fg">{{ __('site::layout.footer_contact') }}</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-muted-fg">
              @if ($phone)<li>{{ __('site::layout.hotline') }}: <a href="tel:{{ $phone }}" class="font-medium text-fg hover:underline">{{ $phone }}</a></li>@endif
              @if ($settings['email'] ?? '')<li><a href="mailto:{{ $settings['email'] }}" class="hover:text-fg">{{ $settings['email'] }}</a></li>@endif
              @if ($settings['address'] ?? '')<li>{{ $settings['address'] }}</li>@endif
            </ul>
            <div class="mt-4 flex flex-wrap gap-2">
              @foreach (['facebook_url' => 'facebook', 'instagram_url' => 'instagram', 'youtube_url' => 'youtube', 'tiktok_url' => 'tiktok'] as $key => $icon)
                @if ($settings[$key] ?? '')
                  <a href="{{ $settings[$key] }}" target="_blank" rel="noreferrer" aria-label="{{ ucfirst($icon) }}" class="flex size-10 items-center justify-center rounded-full border border-line bg-white text-fg transition-colors hover:bg-ink-950 hover:text-white">@include('site::partials.icon', ['name' => $icon, 'class' => 'size-[18px]'])</a>
                @endif
              @endforeach
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-line py-6 pb-24 text-sm text-muted-fg md:flex-row md:items-center md:justify-between lg:pb-28">
          <p>© {{ date('Y') }} {{ $site_name }}. {{ __('site::layout.all_rights_reserved') }}</p>
          <p class="flex gap-5"><a href="/privacy" class="hover:text-fg">{{ __('site::layout.privacy_policy') }}</a><a href="/terms" class="hover:text-fg">{{ __('site::layout.terms_of_service') }}</a></p>
        </div>
      </div>
    </footer>

    {{-- Mobile: bottom tab bar with the raised Consult button in the middle --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 rounded-t-3xl border-t border-line bg-white lg:hidden" aria-label="{{ __('site::layout.quick_navigation') }}" style="padding-bottom: env(safe-area-inset-bottom, 0px)">
      <div class="grid h-16 grid-cols-5 items-end px-2">
        @php($tab = fn ($href, $icon, $label, $active) => compact('href', 'icon', 'label', 'active'))
        @foreach ([$tab('/', 'home', __('site::layout.nav_home'), $nav_active === 'home'), $tab('/projects', 'building', __('site::layout.nav_listings'), $nav_active === 'projects' || $onType)] as $t)
          <a href="{{ $t['href'] }}" class="flex h-full flex-col items-center justify-center gap-1 text-[11px] font-medium {{ $t['active'] ? 'text-fg' : 'text-muted-fg' }}" @if ($t['active']) aria-current="page" @endif>
            @include('site::partials.icon', ['name' => $t['icon'], 'class' => 'size-5'])<span>{{ $t['label'] }}</span>
            <span class="size-1 rounded-full {{ $t['active'] ? 'bg-accent' : 'bg-transparent' }}"></span>
          </a>
        @endforeach
        <button type="button" data-chooser-toggle aria-haspopup="dialog" class="flex flex-col items-center gap-1 pb-2 text-[11px] font-semibold text-fg">
          <span class="-mt-6 inline-flex size-14 items-center justify-center rounded-full bg-ink-950 text-white shadow-lift ring-4 ring-white">@include('site::partials.icon', ['name' => 'message-circle', 'class' => 'size-6'])</span>
          <span>{{ __('site::layout.nav_consult') }}</span>
        </button>
        @foreach ([$tab('/map', 'map', __('site::layout.nav_map'), $nav_active === 'map')] as $t)
          <a href="{{ $t['href'] }}" class="flex h-full flex-col items-center justify-center gap-1 text-[11px] font-medium {{ $t['active'] ? 'text-fg' : 'text-muted-fg' }}" @if ($t['active']) aria-current="page" @endif>
            @include('site::partials.icon', ['name' => $t['icon'], 'class' => 'size-5'])<span>{{ $t['label'] }}</span>
            <span class="size-1 rounded-full {{ $t['active'] ? 'bg-accent' : 'bg-transparent' }}"></span>
          </a>
        @endforeach
        <button type="button" data-open-dialog="menu-sheet" aria-haspopup="dialog" class="flex h-full flex-col items-center justify-center gap-1 text-[11px] font-medium text-muted-fg">
          @include('site::partials.icon', ['name' => 'menu', 'class' => 'size-5'])<span>{{ __('site::layout.nav_menu') }}</span><span class="size-1"></span>
        </button>
      </div>
    </nav>

    {{-- Desktop: the one floating button --}}
    @if ($whatsapp || $consultOn)
      <button type="button" data-chooser-toggle aria-haspopup="dialog" aria-expanded="false" aria-label="{{ __('site::layout.chooser_title') }}" class="fixed right-6 bottom-6 z-40 hidden size-14 items-center justify-center rounded-full bg-ink-950 text-white shadow-lift transition-transform duration-200 hover:-translate-y-0.5 lg:inline-flex">@include('site::partials.icon', ['name' => 'message-circle', 'class' => 'size-6'])</button>

      {{-- ConsultSheet: WhatsApp or book. Bottom sheet on phones, small card over the button on desktop. --}}
      <dialog data-chooser class="sheet chooser" aria-label="{{ __('site::layout.chooser_title') }}">
        <div class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div><h2 class="font-display text-xl font-bold tracking-[-0.02em] text-fg">{{ __('site::layout.chooser_title') }}</h2><p class="mt-1 text-sm text-muted-fg">{{ __('site::layout.chooser_text') }}</p></div>
            <button type="button" data-dialog-close aria-label="{{ __('site::layout.close') }}" class="-mt-1 -mr-1 inline-flex size-9 shrink-0 items-center justify-center rounded-full text-muted-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
          </div>
          <div class="mt-5 flex flex-col gap-3">
            @include('site::partials.cta', ['kind' => 'wa', 'class' => 'w-full'])
            @include('site::partials.cta', ['kind' => 'consult', 'class' => 'w-full'])
          </div>
          <p class="mt-4 text-center text-sm text-muted-fg">{{ __('site::layout.replies_fast') }}</p>
        </div>
      </dialog>
    @endif

    {{-- Mobile menu sheet: what the tab bar has no room for --}}
    <dialog id="menu-sheet" class="sheet" aria-label="{{ __('site::layout.nav_menu') }}">
      <div class="p-6">
        <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold tracking-[-0.02em] text-fg">{{ __('site::layout.nav_menu') }}</h2><button type="button" data-dialog-close aria-label="{{ __('site::layout.close') }}" class="inline-flex size-9 items-center justify-center rounded-full text-muted-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button></div>
        <ul class="mt-4 divide-y divide-line text-[15px] font-medium text-fg">
          <li><button type="button" data-open-dialog="search-dialog" class="flex w-full items-center justify-between py-3.5">{{ __('site::layout.search_title') }} @include('site::partials.icon', ['name' => 'search', 'class' => 'size-5 text-muted-fg'])</button></li>
          @foreach ($types as $t)<li><a href="{{ $t['href'] }}" class="flex items-center justify-between py-3.5">{{ $t['label'] }} @include('site::partials.icon', ['name' => 'chevron-right', 'class' => 'size-4 text-muted-fg'])</a></li>@endforeach
          @foreach ([['/gallery', __('site::layout.nav_gallery')], ['/about', __('site::layout.nav_about')], ['/contact', __('site::layout.contact')], ['/privacy', __('site::layout.privacy_policy')], ['/terms', __('site::layout.terms_of_service')]] as [$href, $label])
            <li><a href="{{ $href }}" class="flex items-center justify-between py-3.5">{{ $label }} @include('site::partials.icon', ['name' => 'chevron-right', 'class' => 'size-4 text-muted-fg'])</a></li>
          @endforeach
          @if ($phone)<li><a href="tel:{{ $phone }}" class="flex items-center justify-between py-3.5">{{ __('site::layout.call_us') }} <span class="text-muted-fg">{{ $phone }}</span></a></li>@endif
        </ul>
      </div>
    </dialog>

    {{-- Search overlay: the same type filter + keyword form as before --}}
    <dialog id="search-dialog" class="search-dialog" aria-label="{{ __('site::layout.search_title') }}">
      <div class="p-4 sm:p-6">
        <div class="mb-3 flex items-center justify-between"><h2 class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('site::layout.search_title') }}</h2><button type="button" data-dialog-close aria-label="{{ __('site::layout.close') }}" class="inline-flex size-9 items-center justify-center rounded-full text-muted-fg hover:bg-fill">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button></div>
        @include('site::partials.search-form', ['autofocus' => true])
      </div>
    </dialog>

    {{-- Lightbox for galleries --}}
    <div data-lightbox hidden class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4">
      <button type="button" data-lightbox-close aria-label="{{ __('site::common.close') }}" class="absolute top-4 right-4 inline-flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
      <img data-lightbox-img src="" alt="" class="max-h-[90svh] max-w-full rounded-2xl object-contain" />
    </div>
    @includeIf('consultation::partials.launcher')
    @stack('scripts')
  </body>
</html>
