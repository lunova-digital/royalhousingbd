{{-- A Share button. @include('site::partials.share-button', ['title' => ..., 'text' => ..., 'url' => '/plot/x', 'variant' => 'card'|'detail'])
     On phones it opens the native share sheet; elsewhere the popover in the layout. share.ts does the rest. --}}
@php($detail = ($variant ?? 'card') === 'detail')
<button type="button" data-share data-share-title="{{ $title }}" data-share-text="{{ $text }}" data-share-url="{{ \Modules\Site\Site::baseUrl().$url }}" aria-haspopup="dialog" aria-label="{{ __('site::layout.share') }}: {{ $title }}" class="{{ $detail ? 'save-pill' : 'save-dot' }}">
  @include('site::partials.icon', ['name' => 'share', 'class' => $detail ? 'size-5' : 'size-[17px]'])
  @if ($detail)<span>{{ __('site::layout.share') }}</span>@endif
</button>
