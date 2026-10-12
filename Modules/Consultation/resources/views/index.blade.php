@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('consultation::page.crumb'), null]], 'title' => __('consultation::page.heading'), 'text' => __('consultation::page.intro'), 'chip' => __('consultation::page.chip')])

<section class="container-x -mt-6 grid gap-6 lg:grid-cols-[1.25fr_1fr]">
  <div class="card p-5 sm:p-8">
    @if ($availability->enabled)
      @include('consultation::partials.widget', ['availability' => $availability, 'inline' => true])
    @else
      <h2 class="font-display text-2xl font-bold">{{ __('consultation::widget.heading') }}</h2>
      <p class="mt-2 text-sm text-muted-fg">{{ __('consultation::widget.no_days') }}</p>
      @if ($settings['whatsapp_number'] ?? '')<a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="btn-green mt-5 h-12 rounded-xl">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('site::common.chat_on_whatsapp') }}</a>@endif
    @endif
  </div>

  <aside class="grid content-start gap-4">
    <div class="card p-5">
      <h3 class="font-semibold">{{ __('consultation::page.how_title') }}</h3>
      <ol class="mt-4 grid gap-3 text-sm">
        @foreach ([__('consultation::page.how_1'), __('consultation::page.how_2'), __('consultation::page.how_3')] as $i => $step)
          <li class="flex items-start gap-3"><span class="consult-step mt-0.5">{{ $i + 1 }}</span><span class="pt-0.5">{{ $step }}</span></li>
        @endforeach
      </ol>
    </div>
    <div class="card p-5">
      <h3 class="font-semibold">{{ __('consultation::page.cover_title') }}</h3>
      <ul class="mt-3 grid gap-2.5 text-sm">
        @foreach (['cover_1', 'cover_2', 'cover_3', 'cover_4', 'cover_5'] as $key)
          <li class="flex items-start gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'mt-0.5 size-4 shrink-0 text-success'])<span>{{ __('consultation::page.'.$key) }}</span></li>
        @endforeach
      </ul>
    </div>
    @if ($settings['whatsapp_number'] ?? '')
      <div class="dark-panel flex flex-wrap items-center gap-4 p-6">
        <span class="inline-flex size-12 items-center justify-center rounded-xl bg-whatsapp text-white">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-6'])</span>
        <span class="min-w-0 flex-1"><span class="block font-display text-lg font-bold">{{ __('consultation::page.prefer_whatsapp') }}</span><span class="block text-sm text-white/65">{{ __('consultation::page.prefer_whatsapp_text') }}</span></span>
        <a href="{{ $whatsapp_float }}" target="_blank" rel="noreferrer" class="btn-light h-10 px-4">{{ __('site::common.whatsapp') }} @include('site::partials.icon', ['name' => 'arrow', 'class' => 'size-4'])</a>
      </div>
    @endif
  </aside>
</section>
@endsection
