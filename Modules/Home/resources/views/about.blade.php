@extends('site::layouts.app')
@section('content')
@php($asset = fn ($p) => \Modules\Site\Site::asset($p))
@include('site::partials.page-hero', ['crumbs' => [[__('home::about.eyebrow'), null]], 'title' => $settings['about_title'], 'text' => $settings['about_text'] ?: __('home::about.intro')])

<section class="container-x grid gap-8 py-10 sm:py-14 lg:grid-cols-[1fr_380px] lg:gap-14">
  <div class="max-w-3xl">
    <h2 class="font-display text-2xl font-bold sm:text-[26px]">{{ __('home::about.our_story') }}</h2>
    <div class="prose-light mt-4 grid gap-4 sm:text-lg">
      @foreach (preg_split('/\n\s*\n/', trim($settings['about_story'])) as $paragraph)
        <p>{!! nl2br(e($paragraph)) !!}</p>
      @endforeach
    </div>
    <h2 class="mt-10 font-display text-2xl font-bold sm:text-[26px]">{{ __('home::about.different_title') }}</h2>
    <ul class="mt-4 grid gap-3 sm:text-lg">
      @foreach ([[__('home::about.point_documents'), __('home::about.point_documents_text')], [__('home::about.point_map'), __('home::about.point_map_text')], [__('home::about.point_visits'), __('home::about.point_visits_text')], [__('home::about.point_instalments'), __('home::about.point_instalments_text')]] as [$strong, $text])
        <li class="flex gap-3"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-brand"></span><span><strong class="font-semibold text-fg">{{ $strong }}</strong> {{ $text }}</span></li>
      @endforeach
    </ul>
  </div>
  @if ($stats)
    <div class="grid h-fit grid-cols-2 gap-3">
      @foreach ($stats as $i => $stat)
        <div class="card p-5"><span class="icon-box size-10 rounded-lg">@include('site::partials.icon', ['name' => ['building', 'users', 'award', 'shield-check'][$i % 4], 'class' => 'size-5'])</span><p class="mt-4 font-display text-2xl font-extrabold">{{ $stat['value'] ?? '' }}</p><p class="mt-0.5 text-sm text-muted-fg">{{ \Modules\Site\Site::tr($stat['label'] ?? '') }}</p></div>
      @endforeach
    </div>
  @endif
</section>

@if ($team)
@php($leaders = array_values(array_filter($team, fn ($m) => ! empty($m['leader']))))
@php($members = array_values(array_filter($team, fn ($m) => empty($m['leader']))))
<section id="team" class="container-x pb-10 sm:pb-14">
  @include('site::partials.section-heading', ['eyebrow' => __('home::about.our_people'), 'title' => __('home::about.meet_leadership'), 'text' => __('home::about.leadership_text')])
  @if ($leaders)
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($leaders as $member)
        <figure class="card overflow-hidden">
          <div class="relative aspect-[4/5] bg-fill">
            @if ($member['photo'] ?? '')<img src="{{ $asset($member['photo']) }}" alt="{{ \Modules\Site\Site::tr($member['name'] ?? '') }}" loading="lazy" class="size-full object-cover" />@else<span class="flex size-full items-center justify-center text-faint-fg">@include('site::partials.icon', ['name' => 'user', 'class' => 'size-12'])</span>@endif
            <span class="badge-sale absolute top-3 left-3">{{ __('home::about.leadership') }}</span>
          </div>
          <figcaption class="p-4"><p class="font-display text-lg font-bold">{{ \Modules\Site\Site::tr($member['name'] ?? '') }}</p><p class="mt-0.5 text-sm text-muted-fg">{{ \Modules\Site\Site::tr($member['role'] ?? '') }}</p></figcaption>
        </figure>
      @endforeach
    </div>
  @endif
  @if ($members)
    <h3 class="mt-10 font-display text-xl font-bold">{{ __('site::common.our_team') }}</h3>
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
<section class="container-x pb-10 sm:pb-14">
  @include('site::partials.section-heading', ['eyebrow' => __('home::about.partners'), 'title' => __('home::about.partners_title'), 'text' => __('home::about.partners_text')])
  <div class="card mt-6 p-3 sm:p-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
      @foreach ($partners as $partner)
        <figure class="tile relative flex min-h-24 flex-col items-center justify-center gap-2 p-4 text-center">
          @if ($partner['role'] ?? '')<span class="absolute top-2 right-2 text-[10px] text-faint-fg">{{ \Modules\Site\Site::tr($partner['role']) }}</span>@endif
          @if ($partner['logo'] ?? '')<img src="{{ $asset($partner['logo']) }}" alt="{{ \Modules\Site\Site::tr($partner['name'] ?? '') }}" class="h-10 w-20 object-contain" />@endif
          <figcaption class="font-display text-sm font-bold">{{ \Modules\Site\Site::tr($partner['name'] ?? '') }}</figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="container-x">
  <div class="card p-6 sm:p-8">
    <h2 class="font-display text-2xl font-bold">{{ __('site::common.contact') }}</h2>
    <ul class="mt-4 grid gap-2 sm:text-lg">
      @if ($settings['email'] ?? '')<li>{{ __('site::common.email') }} <a href="mailto:{{ $settings['email'] }}" class="font-semibold text-brand">{{ $settings['email'] }}</a></li>@endif
      @if ($settings['phone'] ?? '')<li>{{ __('site::common.hotline') }} <a href="tel:{{ $settings['phone'] }}" class="font-semibold text-brand">{{ $settings['phone'] }}</a></li>@endif
      @if ($settings['address'] ?? '')<li>{{ __('site::common.office') }}: {{ $settings['address'] }}</li>@endif
    </ul>
  </div>
</section>
@endsection
