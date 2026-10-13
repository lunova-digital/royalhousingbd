@extends('site::layouts.app')
@section('content')
@php($c = $consultation)
@php($state = $c->status === 'cancelled' ? 'cancelled' : ($c->status === 'completed' ? 'completed' : 'confirmed'))
@include('site::partials.page-hero', ['crumbs' => [[__('consultation::page.crumb'), '/consultation'], [$c->reference, null]], 'title' => __('consultation::page.'.$state.'_heading'), 'text' => __('consultation::page.'.$state.'_text')])

<section class="container-x -mt-6 grid gap-6 lg:grid-cols-[1.25fr_1fr]">
  <div class="card p-5 sm:p-8">
    <div class="flex items-center gap-4">
      <span class="flex size-14 shrink-0 items-center justify-center rounded-full {{ $state === 'confirmed' ? 'bg-success-50 text-success' : 'bg-fill text-muted-fg' }}">@include('site::partials.icon', ['name' => $state === 'cancelled' ? 'x' : 'check', 'class' => 'size-7'])</span>
      <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::widget.reference') }}</p><p class="font-mono text-xl font-bold">{{ $c->reference }}</p></div>
    </div>
    <dl class="mt-6 grid gap-4 border-t border-line pt-6 sm:grid-cols-2">
      <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::page.when') }}</dt><dd class="mt-1 font-semibold">{{ $summary['date'] }}<br /><span class="text-sm font-medium text-muted-fg">{{ $summary['time'] }} · {{ __('consultation::widget.bd_time') }}</span></dd></div>
      <div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::page.how') }}</dt><dd class="mt-1 font-semibold">{{ $summary['mode'] }}</dd></div>
      @if ($c->topic)<div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::page.about') }}</dt><dd class="mt-1 font-semibold">{{ $c->topic }}</dd></div>@endif
      @if ($c->listing)<div><dt class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('consultation::page.listing') }}</dt><dd class="mt-1 font-semibold"><a href="{{ $c->listing->url() }}" class="hover:underline">{{ $c->listing->title }}</a></dd></div>@endif
    </dl>
    @if ($c->isActive())
      <div class="mt-6 flex flex-wrap gap-2">
        @if ($summary['whatsapp_url'])<a href="{{ $summary['whatsapp_url'] }}" target="_blank" rel="noreferrer" class="btn-green h-12 rounded-xl px-5">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4']) {{ __('consultation::widget.whatsapp_me') }}</a>@endif
        <a href="/projects" class="btn-outline h-12 rounded-xl px-5">{{ __('consultation::page.browse_projects') }}</a>
      </div>
    @else
      <a href="/consultation" class="btn-primary mt-6 h-12 rounded-xl px-5">@include('site::partials.icon', ['name' => 'calendar', 'class' => 'size-4']) {{ __('consultation::widget.book_another') }}</a>
    @endif
  </div>

  <aside class="grid content-start gap-4">
    @if ($c->isActive())
      <div class="card p-5">
        <h3 class="font-semibold">{{ __('consultation::page.next_title') }}</h3>
        <ol class="mt-4 grid gap-3 text-sm">
          @foreach (['next_1', 'next_3'] as $i => $key)
            <li class="flex items-start gap-3"><span class="consult-step mt-0.5">{{ $i + 1 }}</span><span class="pt-0.5">{{ __('consultation::page.'.$key) }}</span></li>
          @endforeach
        </ol>
      </div>
    @endif
    @if ($settings['phone'] ?? '')
      <div class="card flex items-center gap-4 p-5"><span class="icon-box">@include('site::partials.icon', ['name' => 'phone', 'class' => 'size-5'])</span><span><span class="block text-xs font-semibold uppercase tracking-[0.14em] text-muted-fg">{{ __('site::common.call_us') }}</span><a href="tel:{{ $settings['phone'] }}" class="block font-semibold">{{ $settings['phone'] }}</a></span></div>
    @endif
  </aside>
</section>
@endsection
