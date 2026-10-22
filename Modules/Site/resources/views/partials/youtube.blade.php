{{-- A click-to-load YouTube embed: thumbnail first, iframe on demand. @include('site::partials.youtube', ['id' => ...]) --}}
<button type="button" data-youtube="{{ $id }}" class="group relative block aspect-video w-full overflow-hidden rounded-2xl border border-line bg-fill" aria-label="{{ __('site::layout.play_video') }}">
  <img src="https://i.ytimg.com/vi/{{ $id }}/hqdefault.jpg" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.05]" />
  <span class="absolute inset-0 flex items-center justify-center"><span class="inline-flex size-14 items-center justify-center rounded-full bg-surface text-brand shadow-lg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ml-1 size-7" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></span>
</button>
