{{-- One title, one sub-line, optionally an eyebrow and one ghost link.
     @include('site::partials.section-heading', ['eyebrow' => ..., 'title' => ..., 'text' => ..., 'action' => ['View all', '/projects'], 'center' => false]) --}}
@php($center = $center ?? false)
<div class="flex flex-col gap-3 {{ $center ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between' }}">
  <div class="{{ $center ? 'max-w-2xl' : 'max-w-2xl' }}">
    @if ($eyebrow ?? '')<p class="eyebrow">{{ $eyebrow }}</p>@endif
    <h2 class="section-title {{ ($eyebrow ?? '') ? 'mt-2' : '' }}">{{ $title }}</h2>
    @if (isset($text) && $text !== '')<p class="mt-2 text-base text-muted-fg">{{ $text }}</p>@endif
  </div>
  @if ($action ?? null)<a href="{{ $action[1] }}" class="link-arrow shrink-0">{{ $action[0] }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endif
</div>
