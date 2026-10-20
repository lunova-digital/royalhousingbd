{{-- The booking widget: day strip → time grid → details → success state. consult.ts drives it.
     @include('consultation::partials.widget', ['availability' => Availability, 'inline' => bool]) --}}
@php($a = $availability ?? \Modules\Consultation\Availability::current())
@php($topics = \Modules\Consultation\Http\Controllers\ConsultationPages::topics())
@php($wa = $whatsapp_float ?? '')
<div data-consult-widget @if ($inline ?? false) data-consult-inline @endif data-consult-feed="/consultation/availability"
     data-consult-labels="{{ json_encode(['open' => __('consultation::widget.open_count'), 'full' => __('consultation::widget.full'), 'no_slots' => __('consultation::widget.no_slots'), 'no_days' => __('consultation::widget.no_days'), 'load_failed' => __('consultation::widget.load_failed'), 'generic' => __('consultation::widget.error_generic'), 'slot' => __('consultation::widget.error_slot'), 'selected' => __('consultation::widget.selected'), 'submitting' => __('consultation::widget.submitting')], JSON_UNESCAPED_UNICODE) }}">
  <form data-consult-form method="post" action="/consultation" novalidate class="relative grid grid-cols-[minmax(0,1fr)] gap-7">
    @csrf
    <input type="hidden" name="listing_id" data-consult-listing value="{{ $listing_id ?? '' }}" />
    <input type="hidden" name="date" data-consult-date value="" />
    <input type="hidden" name="time" data-consult-time value="" />
    <div class="absolute -left-[9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>

    <div class="{{ ($inline ?? false) ? '' : 'pr-10' }}">
      <h2 class="font-display text-2xl font-bold">{{ __('consultation::widget.heading') }}</h2>
      <p class="mt-1 text-sm text-muted-fg">{{ $a->note !== '' ? $a->note : __('consultation::widget.intro') }}</p>
    </div>

    {{-- 1. Day --}}
    <section>
      <h3 class="flex items-center gap-2.5 font-semibold"><span class="consult-step">1</span>{{ __('consultation::widget.step_day') }}</h3>
      <div data-consult-days class="scroll-row mt-3 flex gap-2 overflow-x-auto pb-1">
        <p data-consult-loading class="text-sm text-muted-fg">{{ __('consultation::widget.loading') }}</p>
      </div>
      <p data-consult-feed-error hidden class="mt-2 text-sm text-brand"></p>
      @if ($wa)<a data-consult-wa-fallback href="{{ $wa }}" target="_blank" rel="noreferrer" hidden class="btn-wa btn-sm mt-3">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('site::layout.chat_whatsapp') }}</a>@endif
    </section>

    {{-- 2. Time --}}
    <section>
      <h3 class="flex items-center justify-between gap-2.5 font-semibold"><span class="flex items-center gap-2.5"><span class="consult-step">2</span>{{ __('consultation::widget.step_time') }}</span><span class="text-xs font-medium text-muted-fg">{{ __('consultation::widget.bd_time') }}</span></h3>
      <div data-consult-slots class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
        <p class="col-span-full text-sm text-muted-fg">{{ __('consultation::widget.choose_day_first') }}</p>
      </div>
      <p data-consult-error-for="time" hidden class="mt-2 text-sm text-brand"></p>
      <p data-consult-selected hidden class="mt-3 rounded-xl bg-success-50 px-3 py-2 text-sm font-semibold text-success"></p>
    </section>

    {{-- 3. Details --}}
    <section>
      <h3 class="flex items-center gap-2.5 font-semibold"><span class="consult-step">3</span>{{ __('consultation::widget.step_details') }}</h3>
      <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-medium" data-consult-field="name"><span>{{ __('consultation::widget.name') }} *</span><input name="name" required autocomplete="name" class="input" /><span data-consult-error hidden class="text-xs font-normal text-brand"></span></label>
        <label class="grid gap-1.5 text-sm font-medium" data-consult-field="phone"><span>{{ __('consultation::widget.phone') }} *</span><input name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="01XXXXXXXXX" class="input" /><span data-consult-error hidden class="text-xs font-normal text-brand"></span><span class="text-xs font-normal text-muted-fg">{{ __('consultation::widget.phone_hint') }}</span></label>
        <label class="grid gap-1.5 text-sm font-medium sm:col-span-2" data-consult-field="email"><span>{{ __('consultation::widget.email') }}</span><input name="email" type="email" autocomplete="email" class="input" /><span data-consult-error hidden class="text-xs font-normal text-brand"></span></label>
        <fieldset class="grid gap-1.5 text-sm font-medium sm:col-span-2" data-consult-field="mode">
          <legend class="mb-1.5">{{ __('consultation::widget.mode') }}</legend>
          <div class="grid grid-cols-2 gap-2 {{ ['sm:grid-cols-1', 'sm:grid-cols-2', 'sm:grid-cols-3', 'sm:grid-cols-4'][min(4, count($a->modes)) - 1] }}">
            @foreach ($a->modes as $i => $mode)
              <label class="consult-mode"><input type="radio" name="mode" value="{{ $mode }}" class="sr-only" @checked($i === 0) />@include('site::partials.icon', ['name' => ['phone' => 'phone', 'whatsapp' => 'whatsapp', 'office' => 'pin', 'video' => 'image'][$mode], 'class' => 'size-4 shrink-0'])<span>{{ __('consultation::widget.mode_'.$mode) }}</span></label>
            @endforeach
          </div>
          <span data-consult-error hidden class="text-xs font-normal text-brand"></span>
        </fieldset>
        <label class="grid gap-1.5 text-sm font-medium sm:col-span-2" data-consult-field="topic"><span>{{ __('consultation::widget.topic') }}</span>
          <select name="topic" class="input"><option value="">{{ __('consultation::widget.topic_placeholder') }}</option>@foreach ($topics as $topic)<option>{{ $topic }}</option>@endforeach</select>
          <span data-consult-error hidden class="text-xs font-normal text-brand"></span>
        </label>
        <label class="grid gap-1.5 text-sm font-medium sm:col-span-2" data-consult-field="message"><span>{{ __('consultation::widget.message') }}</span><textarea name="message" rows="3" maxlength="1000" placeholder="{{ __('consultation::widget.message_placeholder') }}" class="input h-auto py-3"></textarea><span data-consult-error hidden class="text-xs font-normal text-brand"></span></label>
      </div>
    </section>

    <div>
      <p data-consult-message hidden class="mb-3 rounded-xl bg-brand-50 px-3 py-2 text-sm font-medium text-brand"></p>
      <button type="submit" data-consult-submit class="btn-primary h-13 w-full rounded-xl text-base">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-5']) <span data-consult-submit-label>{{ __('consultation::widget.submit') }}</span></button>
      <p class="mt-3 text-center text-xs text-muted-fg">{{ __('consultation::widget.free_note') }}</p>
    </div>
  </form>

  <div data-consult-success hidden class="py-4 text-center">
    <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-success-50 text-success">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-8'])</span>
    <h2 class="mt-5 font-display text-2xl font-bold">{{ __('consultation::widget.success_title') }}</h2>
    <p data-consult-when class="mt-2 text-base font-semibold"></p>
    <p class="mt-1 text-sm text-muted-fg">{{ __('consultation::widget.success_text') }}</p>
    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::widget.reference') }} <span data-consult-ref class="ml-1 rounded-md bg-fill px-2 py-1 font-mono text-sm tracking-normal text-fg"></span></p>
    <div class="mx-auto mt-6 grid max-w-sm gap-2">
      <a data-consult-wa href="#" target="_blank" rel="noreferrer" hidden class="btn-green h-12 rounded-xl">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('consultation::widget.whatsapp_me') }}</a>
      <a data-consult-url href="#" class="btn-outline h-12 rounded-xl">{{ __('consultation::widget.view_booking') }}</a>
    </div>
  </div>
</div>
