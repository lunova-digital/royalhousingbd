{{-- A calm page header: breadcrumb, optional eyebrow, one title, one sub-line.
     @include('site::partials.page-hero', ['crumbs' => [['About', null]], 'title' => ..., 'text' => ..., 'chip' => ...]) --}}
<section class="container-x pt-5 lg:pt-8">
  @include('site::partials.breadcrumb', ['items' => $crumbs ?? []])
  <div class="mt-4 max-w-3xl">
    @if ($chip ?? '')<p class="eyebrow">{{ $chip }}</p>@endif
    <h1 class="{{ ($chip ?? '') ? 'mt-2' : '' }} font-display text-[38px] leading-[1.08] text-fg lg:text-[54px]">{{ $title }}</h1>
    @if ($text ?? '')<p class="mt-3 text-base text-muted-fg sm:text-lg">{{ $text }}</p>@endif
  </div>
</section>
