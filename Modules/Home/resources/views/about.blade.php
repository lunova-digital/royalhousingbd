@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@include('site::partials.page-hero', ['crumbs' => [[__('home::about.eyebrow'), null]], 'title' => $settings['about_title'], 'text' => $settings['about_text'] ?: __('home::about.intro')])

@if ($stats)
<section class="container-x pt-8">
  <dl class="grid grid-cols-2 gap-y-6 divide-x divide-line rounded-[20px] border border-line bg-white py-6 sm:grid-cols-4">
    @foreach ($stats as $stat)
      <div class="px-5 text-center sm:px-6"><dt class="num text-[28px] leading-none text-fg sm:text-[34px]">{{ $stat['value'] ?? '' }}</dt><dd class="mt-2 text-sm text-muted-fg">{{ \Modules\Site\Site::tr($stat['label'] ?? '') }}</dd></div>
    @endforeach
  </dl>
</section>
@endif

<section class="container-x grid gap-10 py-12 lg:grid-cols-2 lg:gap-16 lg:py-20">
  <div>
    <h2 class="section-title">{{ __('home::about.our_story') }}</h2>
    <div class="mt-4 grid gap-4 text-[17px] leading-relaxed">
      @foreach (preg_split('/\n\s*\n/', trim($settings['about_story'])) as $paragraph)
        <p>{!! nl2br(e($paragraph)) !!}</p>
      @endforeach
    </div>
  </div>
  <div>
    <h2 class="section-title">{{ __('home::about.different_title') }}</h2>
    <ul class="mt-6 grid gap-5">
      @foreach ([[__('home::about.point_documents'), __('home::about.point_documents_text')], [__('home::about.point_map'), __('home::about.point_map_text')], [__('home::about.point_visits'), __('home::about.point_visits_text')], [__('home::about.point_instalments'), __('home::about.point_instalments_text')]] as [$strong, $text])
        <li class="flex gap-3"><span class="mt-0.5 text-accent-text">@include('site::partials.icon', ['name' => 'check', 'class' => 'size-5'])</span><span><strong class="font-semibold text-fg">{{ $strong }}</strong> {{ $text }}</span></li>
      @endforeach
    </ul>
  </div>
  </div>
</section>

@if ($team)
@php($leaders = array_values(array_filter($team, fn ($m) => ! empty($m['leader']))))
@php($members = array_values(array_filter($team, fn ($m) => empty($m['leader']))))
<section id="team" class="container-x pb-12 lg:pb-20">
  @include('site::partials.section-heading', ['eyebrow' => __('home::about.our_people'), 'title' => __('home::about.meet_leadership'), 'text' => __('home::about.leadership_text')])
  @if ($leaders)
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($leaders as $member)
        <figure class="card overflow-hidden">
          <div class="relative aspect-[4/5] bg-fill">
            @if ($member['photo'] ?? '')<img src="{{ $asset($member['photo']) }}" alt="{{ \Modules\Site\Site::tr($member['name'] ?? '') }}" loading="lazy" class="size-full object-cover" />@else<span class="flex size-full items-center justify-center text-faint-fg">@include('site::partials.icon', ['name' => 'user', 'class' => 'size-12'])</span>@endif
            <span class="absolute top-3 left-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-fg">{{ __('home::about.leadership') }}</span>
          </div>
          <figcaption class="p-4"><p class="text-lg font-semibold">{{ \Modules\Site\Site::tr($member['name'] ?? '') }}</p><p class="mt-0.5 text-sm text-muted-fg">{{ \Modules\Site\Site::tr($member['role'] ?? '') }}</p></figcaption>
        </figure>
      @endforeach
    </div>
  @endif
  @if ($members)
    <h3 class="mt-10 text-xl font-semibold">{{ __('site::common.our_team') }}</h3>
    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      @foreach ($members as $member)
        <figure class="card overflow-hidden">
          <div class="aspect-square bg-fill">@if ($member['photo'] ?? '')<img src="{{ $asset($member['photo']) }}" alt="{{ \Modules\Site\Site::tr($member['name'] ?? '') }}" loading="lazy" class="size-full object-cover" />@else<span class="flex size-full items-center justify-center text-faint-fg">@include('site::partials.icon', ['name' => 'user', 'class' => 'size-10'])</span>@endif</div>
          <figcaption class="p-3"><p class="text-sm font-semibold">{{ \Modules\Site\Site::tr($member['name'] ?? '') }}</p><p class="mt-0.5 text-xs text-muted-fg">{{ \Modules\Site\Site::tr($member['role'] ?? '') }}</p></figcaption>
        </figure>
      @endforeach
    </div>
  @endif
</section>
@endif

@if ($partners)
<section class="container-x pb-12 lg:pb-20">
  @include('site::partials.section-heading', ['eyebrow' => __('home::about.partners'), 'title' => __('home::about.partners_title'), 'text' => __('home::about.partners_text')])
  <div class="card mt-6 p-3 sm:p-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
      @foreach ($partners as $partner)
        <figure class="tile relative flex min-h-24 flex-col items-center justify-center gap-2 p-4 text-center">
          @if ($partner['role'] ?? '')<span class="absolute top-2 right-2 text-[10px] text-faint-fg">{{ \Modules\Site\Site::tr($partner['role']) }}</span>@endif
          @if ($partner['logo'] ?? '')<img src="{{ $asset($partner['logo']) }}" alt="{{ \Modules\Site\Site::tr($partner['name'] ?? '') }}" class="h-10 w-20 object-contain" />@endif
          <figcaption class="text-sm font-semibold">{{ \Modules\Site\Site::tr($partner['name'] ?? '') }}</figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="container-x pb-16 lg:pb-24">
  <div class="on-dark flex flex-col gap-6 rounded-[28px] bg-ink-950 p-6 text-white sm:p-10 lg:flex-row lg:items-center lg:justify-between">
    <div><h2 class="font-display text-[28px] leading-tight">{{ __('home::about.cta_title') }}</h2><p class="mt-2 max-w-lg text-white/70">{{ __('home::about.cta_text') }}</p></div>
    <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">@include('site::partials.cta', ['kind' => 'wa'])@include('site::partials.cta', ['kind' => 'consult', 'dark' => true])</div>
  </div>
</section>
@endsection
