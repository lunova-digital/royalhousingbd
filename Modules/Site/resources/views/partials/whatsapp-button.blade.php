{{-- @include('site::partials.whatsapp-button', ['href' => ..., 'label' => ..., 'class' => '']) --}}
<a href="{{ $href }}" target="_blank" rel="noreferrer" class="btn-green {{ $class ?? '' }}">@include('site::partials.icon', ['name' => 'whatsapp', 'class' => 'size-4'])<span>{{ $label }}</span></a>
