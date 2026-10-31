<div class="hero-slides" aria-hidden="true">
    @foreach(['center', 'left', 'right'] as $position)
        <div class="hero-slide hero-slide-{{ $position }} {{ $loop->first ? 'is-active' : '' }}">
            <img src="{{ asset('images/hero-support.png') }}" alt="" @if($loop->first) fetchpriority="high" @endif>
        </div>
    @endforeach
</div>
<div class="hero-shade" aria-hidden="true"></div>
<div class="hero-slideshow-controls">
    <button type="button" data-hero-toggle aria-pressed="false" class="hero-toggle" hidden>
        <span data-hero-toggle-label data-pause="{{ app()->getLocale() === 'en' ? 'Pause slideshow' : 'إيقاف العرض مؤقتًا' }}" data-play="{{ app()->getLocale() === 'en' ? 'Play slideshow' : 'تشغيل العرض' }}"></span>
    </button>
    <div class="hero-dots" role="group" aria-label="{{ app()->getLocale() === 'en' ? 'Background slides' : 'شرائح الخلفية' }}" hidden>
        @foreach(range(1, 3) as $slide)
            <button type="button" data-hero-slide="{{ $slide - 1 }}" aria-label="{{ app()->getLocale() === 'en' ? 'Slide' : 'الشريحة' }} {{ $slide }}" aria-pressed="{{ $slide === 1 ? 'true' : 'false' }}"></button>
        @endforeach
    </div>
</div>
