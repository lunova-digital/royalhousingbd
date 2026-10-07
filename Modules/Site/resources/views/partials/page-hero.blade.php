{{-- Full-bleed dark band with breadcrumb, like the reference's About/Legal pages.
     @include('site::partials.page-hero', ['crumbs' => [['About', null]], 'title' => ..., 'text' => ..., 'chip' => ...]) --}}
<section class="dark-band">
  <div class="container-x py-10 sm:py-14 lg:py-16">
    @include('site::partials.breadcrumb', ['items' => $crumbs ?? [], 'dark' => true])
    <h1 class="mt-6 font-display text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[40px]">{{ $title }}</h1>
    @if ($text ?? '')<p class="mt-3 max-w-2xl text-base text-white/70 sm:text-lg">{{ $text }}</p>@endif
    @if ($chip ?? '')<span class="pill-tag mt-5">{{ $chip }}</span>@endif
  </div>
</section>
