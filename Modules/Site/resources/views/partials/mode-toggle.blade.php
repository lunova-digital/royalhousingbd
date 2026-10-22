@php($sun = '<svg class="mode-sun size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M18.7 5.3l-1.6 1.6M6.9 17.1l-1.6 1.6"/></svg>')
@php($moon = '<svg class="mode-moon size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z"/></svg>')
@if (($row ?? false))
  <button type="button" data-mode-toggle data-label-dark="{{ __('site::layout.mode_to_dark') }}" data-label-light="{{ __('site::layout.mode_to_light') }}" class="flex w-full items-center justify-between py-3.5"><span data-mode-text>{{ __('site::layout.mode_to_dark') }}</span><span class="text-muted-fg">{!! $sun !!}{!! $moon !!}</span></button>
@else
  <button type="button" data-mode-toggle data-label-dark="{{ __('site::layout.mode_to_dark') }}" data-label-light="{{ __('site::layout.mode_to_light') }}" aria-label="{{ __('site::layout.mode_to_dark') }}" class="inline-flex size-10 items-center justify-center rounded-full text-fg transition-colors hover:bg-fill">{!! $sun !!}{!! $moon !!}</button>
@endif
