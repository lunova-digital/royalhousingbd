{{-- The share popover (desktop) / bottom sheet (phones without a native share sheet). One per page; share.ts fills in the links. --}}
<div data-share-pop hidden role="dialog" aria-label="{{ __('site::layout.share_title') }}" class="share-pop">
  <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-line sm:hidden"></div>
  <p class="mb-2 px-1 text-sm font-semibold text-fg">{{ __('site::layout.share_title') }}</p>
  <ul class="grid grid-cols-4 gap-1 sm:grid-cols-1">
    @foreach ([
      ['facebook', 'Facebook', 'M24 12a12 12 0 1 0-13.9 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12z'],
      ['whatsapp', 'WhatsApp', 'M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-1.8-.9-3-1.6-4.2-3.7-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.5.7.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.700 1 1-3.600-.2-.4A9.800 9.800 0 1 1 12 21.800zM12 0A12 12 0 0 0 1.600 17.900L0 24l6.300-1.600A12 12 0 1 0 12 0z'],
      ['messenger', 'Messenger', 'M12 0C5.300 0 0 5 0 11.700c0 3.500 1.400 6.500 3.800 8.600.2.200.3.400.3.700l.1 2.100c0 .7.700 1.100 1.300.8l2.400-1c.2-.1.4-.1.600-.1 1.100.3 2.300.5 3.500.5 6.600 0 12-5 12-11.700S18.700 0 12 0zm7.200 9-3.500 5.600c-.6.900-1.700 1.100-2.500.5l-2.800-2.100c-.3-.2-.7-.2-1 0l-3.800 2.900c-.5.400-1.200-.2-.8-.8L8.600 9.500c.6-.9 1.700-1.100 2.500-.5l2.800 2.100c.3.200.7.200 1 0l3.800-2.900c.5-.4 1.100.2.800.8z'],
      ['x', 'X', 'M18.200 2.300h3.300l-7.200 8.300 8.500 11.200h-6.700l-5.200-6.800-6 6.800H1.600l7.700-8.800L1.200 2.300H8l4.700 6.200zm-1.200 17.500h1.800L7 4.200H5z'],
      ['linkedin', 'LinkedIn', 'M20.400 20.500h-3.600v-5.600c0-1.300 0-3-1.800-3s-2.100 1.400-2.100 2.900v5.700H9.400V9h3.400v1.600c.5-.9 1.600-1.800 3.400-1.800 3.600 0 4.300 2.400 4.300 5.500zM5.300 7.400a2.100 2.100 0 1 1 0-4.100 2.100 2.100 0 0 1 0 4.100zm1.800 13.100H3.500V9h3.600zM22.200 0H1.800C.8 0 0 .8 0 1.700v20.600c0 .9.800 1.700 1.800 1.700h20.400c1 0 1.800-.8 1.800-1.700V1.700C24 .8 23.200 0 22.200 0z'],
      ['telegram', 'Telegram', 'M11.900 0a12 12 0 1 0 0 24 12 12 0 0 0 0-24zm5.600 8.200-1.900 8.900c-.1.600-.5.800-1 .5l-2.900-2.100-1.400 1.400c-.2.200-.3.300-.6.300l.2-3 5.400-4.900c.2-.2 0-.3-.4-.1l-6.700 4.200-2.900-.9c-.6-.2-.6-.6.100-.9l11.300-4.400c.5-.2 1 .1.800.9z'],
    ] as [$net, $label, $path])
      <li @if ($net === 'messenger') data-net-mobile-only @endif><a href="#" data-net="{{ $net }}" target="_blank" rel="noopener noreferrer" class="share-item"><svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg><span>{{ $label }}</span></a></li>
    @endforeach
    <li><button type="button" data-net="copy" data-copied="{{ __('site::layout.share_copied') }}" class="share-item w-full text-left">@include('site::partials.icon', ['name' => 'link', 'class' => 'size-5 shrink-0'])<span>{{ __('site::layout.share_copy') }}</span></button></li>
  </ul>
</div>
