{{-- The member's picture, or their initials on a tinted disc. @include('account::partials.avatar', ['member' => $m, 'size' => 40]) --}}
@php($px = $size ?? 40)
@if ($member->avatarUrl())
  <img src="{{ $member->avatarUrl() }}" alt="" width="{{ $px }}" height="{{ $px }}" class="shrink-0 rounded-full object-cover" style="width: {{ $px }}px; height: {{ $px }}px" />
@else
  <span aria-hidden="true" class="inline-flex shrink-0 items-center justify-center rounded-full bg-accent-soft font-semibold text-accent-text" style="width: {{ $px }}px; height: {{ $px }}px; font-size: {{ round($px * 0.38) }}px">{{ $member->initials() }}</span>
@endif
