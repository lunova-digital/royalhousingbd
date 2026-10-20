{{-- The heart. @include('account::partials.save-button', ['listing' => $l, 'variant' => 'card'|'detail']) — account.ts does the rest.
     A card heart sits on the photo; the detail one is a labelled pill. --}}
@php($saved = in_array($listing->id, $savedIds ?? [], true))
@php($detail = ($variant ?? 'card') === 'detail')
<button type="button" data-save="{{ $listing->id }}" aria-pressed="{{ $saved ? 'true' : 'false' }}"
  data-label-save="{{ __('account::saved.save') }}: {{ $listing->title }}" data-label-saved="{{ __('account::saved.remove') }}: {{ $listing->title }}" aria-label="{{ $saved ? __('account::saved.remove') : __('account::saved.save') }}: {{ $listing->title }}"
  data-text-save="{{ __('account::saved.save') }}" data-text-saved="{{ __('account::saved.saved') }}" data-auth-prompt="{{ __('account::auth.save_prompt') }}"
  class="{{ $detail ? 'save-pill' : 'save-dot' }}">
  @include('site::partials.icon', ['name' => 'heart', 'class' => $detail ? 'size-5' : 'size-[18px]'])
  @if ($detail)<span data-save-text>{{ $saved ? __('account::saved.saved') : __('account::saved.save') }}</span>@endif
</button>
