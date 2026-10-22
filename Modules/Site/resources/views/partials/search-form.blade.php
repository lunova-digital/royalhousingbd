{{-- The type filter + keyword search that feeds /projects. @include('site::partials.search-form', ['pill' => bool, 'autofocus' => bool]) --}}
<form action="/projects" method="get" role="search" class="flex items-center gap-1 rounded-full {{ ($pill ?? false) ? 'bg-surface p-1.5 shadow-lift' : 'border border-line bg-fill p-1' }}">
  <label class="relative shrink-0 border-r border-line">
    <span class="sr-only">{{ __('site::common.type') }}</span>
    <select name="type" class="h-11 w-[5.75rem] appearance-none rounded-full bg-transparent pr-6 pl-3 sm:w-auto sm:max-w-[9rem] sm:pr-7 sm:pl-4 text-sm font-medium text-fg outline-none focus-visible:ring-2 focus-visible:ring-action">
      <option value="">{{ __('site::common.all') }}</option>
      @foreach ($types as $t)<option value="{{ $t['key'] }}">{{ $t['label'] }}</option>@endforeach
    </select>
    <span class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-muted-fg">@include('site::partials.icon', ['name' => 'chevron-down', 'class' => 'size-3.5'])</span>
  </label>
  <input type="search" name="q" placeholder="{{ __('site::layout.search_placeholder') }}" aria-label="{{ __('site::common.search') }}" @if ($autofocus ?? false) data-autofocus @endif class="h-11 min-w-0 flex-1 bg-transparent px-3 text-sm text-fg outline-none placeholder:text-muted-fg" />
  <button type="submit" class="btn-consult btn-sm h-11 shrink-0 px-4 sm:px-5" aria-label="{{ __('site::common.search') }}">@include('site::partials.icon', ['name' => 'search', 'class' => 'size-4']) <span class="hidden sm:inline">{{ __('site::common.search') }}</span></button>
</form>
