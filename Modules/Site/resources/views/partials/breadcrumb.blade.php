{{-- @include('site::partials.breadcrumb', ['items' => [['Projects', '/projects'], ['Plot', null]], 'dark' => false]) --}}
@php($dark = $dark ?? false)
<nav aria-label="{{ __('Breadcrumb') }}" class="scroll-row overflow-x-auto whitespace-nowrap text-sm {{ $dark ? 'text-white/50' : 'text-muted-fg' }}">
  <ol class="flex items-center gap-1.5">
    <li><a href="/" class="{{ $dark ? 'hover:text-white' : 'hover:text-fg' }}">{{ __('Home') }}</a></li>
    @foreach ($items as [$label, $href])
      <li aria-hidden="true">@include('site::partials.icon', ['name' => 'chevron-right', 'class' => 'size-3.5'])</li>
      <li>@if ($href)<a href="{{ $href }}" class="{{ $dark ? 'hover:text-white' : 'hover:text-fg' }}">{{ $label }}</a>@else<span class="font-medium {{ $dark ? 'text-white' : 'text-fg' }}" aria-current="page">{{ $label }}</span>@endif</li>
    @endforeach
  </ol>
</nav>
