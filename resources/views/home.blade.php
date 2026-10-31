@extends('layouts.app',['title'=>'لا تواجه الابتزاز وحدك'])
@section('content')
@php
    $hero = $homepage->get('hero');
    $en = app()->getLocale() === 'en';
@endphp

@if($hero)
<section class="home-hero" data-hero-slideshow>
    @include('support.hero-slideshow')
    <div class="hero-content container-page">
        <div class="hero-copy">
            <span class="hero-eyebrow"><span class="site-dot" aria-hidden="true"></span>{{ $hero->eyebrow_ar ?: 'دعم آمن • سري • بلا أحكام' }}</span>
            <h1 class="hero-title">{{ $hero->heading_ar }}</h1>
            <p class="hero-lead">{{ $hero->description_ar }}</p>
            <div class="hero-actions">
                @if($hero->primary_button_text_ar)<a href="{{ \App\Support\SafeNavigation::url($hero->primary_button_url) }}" class="hero-btn hero-btn-danger">{{ $hero->primary_button_text_ar }}</a>@endif
                @if($hero->secondary_button_text_ar)<a href="{{ \App\Support\SafeNavigation::url($hero->secondary_button_url) }}" class="hero-btn hero-btn-light">{{ $hero->secondary_button_text_ar }}</a>@endif
            </div>
        </div>
        <aside class="hero-safety" aria-labelledby="hero-safety-title">
            <h2 id="hero-safety-title"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/></svg>ابدأ بأمان</h2>
            <ol>
                @foreach(['لا تدفع تحت الضغط','احفظ الأدلة والروابط والتواريخ','أمّن حساباتك وفعّل التحقق الثنائي','اطلب الدعم من شخص تثق به'] as $step)
                    <li><span class="hero-step-number">{{ ['١','٢','٣','٤'][$loop->index] }}</span><span>{{ $step }}</span></li>
                @endforeach
            </ol>
        </aside>
    </div>
</section>
@endif

@include('support.home-paths', ['overlap' => (bool) $hero])

@if($section=$homepage->get('help'))
<section class="home-section container-page text-center">
    <span class="eyebrow">{{ $section->eyebrow_ar ?: $section->name_ar }}</span>
    <h2 class="section-title">{{ $section->heading_ar }}</h2>
    <p class="section-lead mx-auto">{{ $section->description_ar }}</p>
    <div class="mt-12 grid gap-5 text-start md:grid-cols-3">
        @foreach([
            ['M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z','خصوصية كاملة','لا نطلب ملفات خاصة أو كلمات مرور.'],
            ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm4.2-14.2-2.1 6.3-6.3 2.1 2.1-6.3 6.3-2.1Z','خطوات واضحة','إرشادات عملية بعيداً عن التهويل.'],
            ['M9 12l2 2 4-4M7.8 4.4a3 3 0 0 0 1.7-.7 3 3 0 0 1 5 0 3 3 0 0 0 1.7.7 3 3 0 0 1 2.7 2.7c.1.6.3 1.2.7 1.7a3 3 0 0 1 0 5 3 3 0 0 0-.7 1.7 3 3 0 0 1-2.7 2.7 3 3 0 0 0-1.7.7 3 3 0 0 1-5 0 3 3 0 0 0-1.7-.7 3 3 0 0 1-2.7-2.7 3 3 0 0 0-.7-1.7 3 3 0 0 1 0-5c.4-.5.6-1.1.7-1.7a3 3 0 0 1 2.7-2.7Z','مصادر متحققة','لا ننشر بيانات رسمية دون مراجعة.'],
        ] as [$icon,$heading,$text])
            <article class="feature-card">
                <span class="feature-icon"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg></span>
                <h3 class="mt-5 text-xl font-bold text-navy dark:text-white">{{ $heading }}</h3>
                <p class="mt-2 leading-7">{{ $text }}</p>
            </article>
        @endforeach
    </div>
    <a href="{{ \App\Support\SafeNavigation::url($section->primary_button_url) }}" class="btn-primary mt-10">{{ $section->primary_button_text_ar }}</a>
</section>
@endif

@if($section=$homepage->get('categories'))
<section id="blackmail-types" class="home-section-alt scroll-mt-24">
    <div class="container-page">
        <div class="section-head">
            <div><span class="eyebrow">{{ $section->name_ar }}</span><h2 class="section-title">{{ $section->heading_ar }}</h2></div>
            <p class="section-lead">{{ $section->description_ar }}</p>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($categories as $category)
                <a href="{{ route('categories.show',$category) }}" class="category-card focus-ring">
                    <span class="category-index" dir="ltr">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="text-xl font-bold text-navy dark:text-white">{{ $en ? ($category->name_en ?: $category->name_ar) : $category->name_ar }}</h3>
                    <p class="mt-3 leading-7">{{ ($en ? $category->description_en : $category->description_ar) ?: ($en ? 'Learn the signs and explore practical guidance.' : 'تعرّف على العلامات والخطوات العملية للتعامل مع هذا النوع.') }}</p>
                    <span class="category-link">{{ $en ? 'View details' : 'عرض المعلومات والتفاصيل' }} <span aria-hidden="true">{{ $en ? '→' : '←' }}</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($section=$homepage->get('protection'))
<section class="home-section container-page">
    <div class="protection-panel">
        <div>
            <span class="eyebrow">{{ $section->name_ar }}</span>
            <h2 class="section-title">{{ $section->heading_ar }}</h2>
            <p class="mt-5 leading-8">{{ $section->description_ar }}</p>
            <a href="{{ \App\Support\SafeNavigation::url($section->primary_button_url) }}" class="btn-primary mt-8">{{ $section->primary_button_text_ar }}</a>
        </div>
        <ul class="protection-list">
            @foreach(['كلمة مرور فريدة لكل حساب','المصادقة متعددة العوامل','مراجعة الجلسات النشطة','تحديث وسائل الاسترداد'] as $tip)
                <li><span class="protection-check" aria-hidden="true"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg></span>{{ $tip }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif

@if($section=$homepage->get('countries'))
<section class="home-section-alt">
    <div class="container-page">
        <div class="text-center">
            <span class="eyebrow">{{ $section->name_ar }}</span>
            <h2 class="section-title">{{ $section->heading_ar }}</h2>
            <p class="section-lead mx-auto">{{ $section->description_ar }}</p>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($countries as $country)
                <article class="country-card">
                    <a href="{{ route('countries.show', $country) }}" class="block">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-bold text-navy dark:text-white">{{ $en ? $country->name_en : $country->name_ar }}</h3>
                            <span class="verified-badge"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>{{ $en ? 'Verified' : 'متحقق' }}</span>
                        </div>
                        @if($information=$en ? $country->emergency_information_en : $country->emergency_information_ar)<p class="mt-3 line-clamp-2 text-sm text-slate-600 dark:text-slate-300">{{ $information }}</p>@endif
                        @if($instructions=$en ? $country->reporting_instructions_en : $country->reporting_instructions_ar)<p class="mt-3 line-clamp-2 text-sm font-semibold">{{ $instructions }}</p>@endif
                    </a>
                    <a href="{{ route('directory.requests.create',['kind'=>'correction','country'=>$country->id]) }}" class="mt-5 inline-flex text-sm font-bold text-brand underline-offset-4 hover:underline">{{ __('directory.edit') }}</a>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 p-10 text-center text-slate-500 dark:border-slate-700">بيانات الدول قيد المراجعة والتحقق</div>
            @endforelse
        </div>
        <div class="mt-10 text-center"><a href="{{ \App\Support\SafeNavigation::url($section->primary_button_url) }}" class="btn-outline">{{ $section->primary_button_text_ar }} <span aria-hidden="true">←</span></a></div>
    </div>
</section>
@endif

@include('support.public-statistics')

@if($section=$homepage->get('consultation'))
<section class="home-section container-page">
    <div class="cta-panel">
        <svg class="cta-mark" viewBox="0 0 100 100" fill="none" aria-hidden="true"><path d="M50 8 18 21v26c0 23 14 38 32 45 18-7 32-22 32-45V21L50 8Z" stroke="currentColor" stroke-width="2.5"/><path d="m35 50 10 10 21-23" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <h2 class="text-3xl font-extrabold md:text-4xl">{{ $section->heading_ar }}</h2>
        <p class="mx-auto mt-4 max-w-2xl leading-8 text-slate-300">{{ $section->description_ar }}</p>
        <a href="{{ \App\Support\SafeNavigation::url($section->primary_button_url) }}" class="hero-btn mt-8 bg-success text-white hover:brightness-110">{{ $section->primary_button_text_ar }}</a>
    </div>
</section>
@endif
@endsection
