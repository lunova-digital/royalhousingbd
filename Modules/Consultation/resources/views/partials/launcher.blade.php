{{-- The floating "Book a free consultation" button on every public page and the dialog it opens.
     Included by the site layout inside the fixed bottom-right stack; the /consultation page
     carries the widget inline instead. --}}
@if (\Modules\Consultation\Availability::enabled())
  @unless (request()->routeIs('consultation.index'))
    <button type="button" data-consult-open class="consult-fab" aria-haspopup="dialog">
      <span class="consult-fab-ring" aria-hidden="true"></span>
      <span class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-white/15">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5'])</span>
      <span class="relative text-left leading-tight"><span class="block text-[10px] font-bold uppercase tracking-[0.16em] text-white/80">{{ __('consultation::widget.fab_eyebrow') }}</span><span class="block font-display text-sm font-bold">{{ __('consultation::widget.fab_label') }}</span></span>
    </button>
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
