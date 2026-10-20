@extends('site::layouts.app')
@section('content')
@include('site::partials.page-hero', ['crumbs' => [[__('consultation::page.crumb'), null]], 'title' => __('consultation::page.heading'), 'text' => __('consultation::page.intro'), 'chip' => __('consultation::page.chip')])

<section class="container-x grid gap-6 pt-8 pb-16 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] lg:pb-24">
  <div class="card p-5 sm:p-8">
    @if ($availability->enabled)
      @include('consultation::partials.widget', ['availability' => $availability, 'inline' => true])
    @else
      <h2 class="font-display text-2xl font-bold tracking-[-0.02em] text-fg">{{ __('consultation::widget.heading') }}</h2>
      <p class="mt-2 text-sm text-muted-fg">{{ __('consultation::widget.no_days') }}</p>
      <div class="mt-5">@include('site::partials.cta', ['kind' => 'wa'])</div>
    @endif
  </div>

  <aside class="grid content-start gap-4">
    <div class="card p-5">
      <h3 class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('consultation::page.how_title') }}</h3>
      <ol class="mt-4 grid gap-3 text-sm">
        @foreach ([__('consultation::page.how_1'), __('consultation::page.how_2'), __('consultation::page.how_3')] as $i => $step)
          <li class="flex items-start gap-3"><span class="consult-step mt-0.5">{{ $i + 1 }}</span><span class="pt-0.5">{{ $step }}</span></li>
        @endforeach
      </ol>
    </div>
    <div class="card p-5">
      <h3 class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('consultation::page.cover_title') }}</h3>
      <ul class="mt-3 grid gap-2.5 text-sm">
        @foreach (['cover_1', 'cover_2', 'cover_3', 'cover_4', 'cover_5'] as $key)
          <li class="flex items-start gap-2.5">@include('site::partials.icon', ['name' => 'check', 'class' => 'mt-0.5 size-4 shrink-0 text-accent-text'])<span>{{ __('consultation::page.'.$key) }}</span></li>
        @endforeach
      </ul>
    </div>
    @if ($settings['whatsapp_number'] ?? '')
      <div class="card p-6"><p class="font-display text-lg font-bold tracking-[-0.02em] text-fg">{{ __('consultation::page.prefer_whatsapp') }}</p><p class="mt-1 text-sm text-muted-fg">{{ __('consultation::page.prefer_whatsapp_text') }}</p><div class="mt-4">@include('site::partials.cta', ['kind' => 'wa', 'class' => 'btn-sm'])</div></div>
    @endif
  </aside>
</section>
@endsection
