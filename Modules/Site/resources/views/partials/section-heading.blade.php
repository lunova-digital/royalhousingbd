{{-- @include('site::partials.section-heading', ['eyebrow' => ..., 'title' => ..., 'text' => ..., 'action' => ['View all', '/projects'], 'center' => false]) --}}
@php($center = $center ?? false)
<div class="flex flex-col gap-3 {{ $center ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between' }}">
  <div class="{{ $center ? 'max-w-2xl' : '' }}">
    @if ($eyebrow ?? '')<p class="eyebrow">{{ $eyebrow }}</p>@endif
    <h2 class="mt-1.5 font-display text-2xl font-extrabold tracking-tight sm:text-[26px]">{{ $title }}</h2>
    @if (isset($text) && $text !== '')<p class="mt-1.5 text-sm text-muted-fg sm:text-base">{{ $text }}</p>@endif
  </div>
  @if ($action ?? null)<a href="{{ $action[1] }}" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-fg hover:text-brand">{{ $action[0] }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>@endif
</div>
