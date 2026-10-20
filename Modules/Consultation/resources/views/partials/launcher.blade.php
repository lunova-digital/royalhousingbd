{{-- The booking dialog every "Book free consultation" button opens (header, floating button, tab bar,
     homepage band). Buttons come from site::partials.cta; the /consultation page carries the widget inline. --}}
@if (\Modules\Consultation\Availability::enabled())
  @unless (request()->routeIs('consultation.index'))
    <dialog data-consult-dialog class="consult-dialog" aria-label="{{ __('consultation::widget.heading') }}">
      <div class="relative p-5 sm:p-8">
        <button type="button" data-consult-close aria-label="{{ __('consultation::widget.close') }}" class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-full bg-fill text-fg hover:bg-line">@include('site::partials.icon', ['name' => 'x', 'class' => 'size-5'])</button>
        @include('consultation::partials.widget')
      </div>
    </dialog>
  @endunless
  @push('scripts')
    {{ Modules::vite('consultation', 'resources/js/consult.ts') }}
  @endpush
@endif
