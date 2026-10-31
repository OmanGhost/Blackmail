@php($paths = [
    ['blackmail.help', 'path_urgent', 'path_urgent_text', 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
    ['consultation', 'path_consult', 'path_consult_text', 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z'],
    ['consultation.track', 'path_track', 'path_track_text', 'M21 21l-4.3-4.3M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z'],
])
<section class="container-page home-paths {{ ($overlap ?? false) ? 'is-overlap' : 'py-10' }}">
    <div class="grid gap-4 md:grid-cols-3">
        @foreach($paths as [$route, $title, $text, $icon])
            <a href="{{ route($route) }}" class="path-card focus-ring {{ $route === 'blackmail.help' ? 'urgent-attention' : '' }}">
                <span class="path-icon"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg></span>
                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold text-navy dark:text-white">{{ __('workflow.'.$title) }} <span aria-hidden="true">←</span></h2>
                    <p class="mt-2 text-sm leading-7">{{ __('workflow.'.$text) }}</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
