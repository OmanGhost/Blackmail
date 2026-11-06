@php
    $navLinks = [
        ['home', 'الرئيسية', 'home'],
        ['blog', __('blog.title'), 'blog*|articles.*|categories.*'],
        ['events', __('events.title'), 'events*'],
        ['countries', 'دليل الدول', 'countries*'],
        ['protection', 'الحماية الرقمية', 'protection'],
        ['services', __('billing.services'), 'services*'],
        ['consultation', 'الاستشارات', 'consultation'],
        ['consultation.track', 'متابعة طلب', 'consultation.track'],
    ];
    $isActive = fn (string $patterns) => request()->routeIs(...explode('|', $patterns));
@endphp
<!doctype html><html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}" x-data="{menu:false,dark:localStorage.theme==='dark'}" x-init="$watch('dark',v=>{document.documentElement.classList.toggle('dark',v);localStorage.theme=v?'dark':'light'});document.documentElement.classList.toggle('dark',dark)"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#102A43" media="(prefers-color-scheme: light)">
@include('layouts.seo')
@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles</head>
<body class="min-h-screen bg-surface text-slate-700 antialiased dark:bg-slate-950 dark:text-slate-200"><a href="#main" class="focus-ring fixed start-4 top-3 z-[100] -translate-y-20 rounded-lg bg-navy px-4 py-3 text-white focus:translate-y-0">{{ __('site.skip') }}</a>
@include('support.quick-exit')
<div class="site-strip"><div class="container-page flex items-center justify-between gap-3"><p class="hidden items-center gap-2 md:flex"><svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>لا نطلب صوراً خاصة أو كلمات مرور.</span></p>
@isset($hotline)
<div class="hotline" data-hotline="{{ $hotline['type'] }}">
    <a href="tel:{{ $hotline['tel'] }}" class="hotline-call" aria-label="{{ $hotline['type'] === 'authority' ? __('hotline.call', ['label' => $hotline['label'], 'phone' => $hotline['phone']]) : __('hotline.call_platform', ['phone' => $hotline['phone']]) }}">
        <span class="hotline-icon" aria-hidden="true"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/></svg></span>
        <span class="hotline-text">@if($hotline['type'] === 'authority')<span class="hotline-label">{{ __('hotline.report_in', ['country' => $hotline['country']]) }} · {{ $hotline['label'] }}</span>@else<span class="hotline-label">{{ __('hotline.platform') }}</span>@endif<b dir="ltr">{{ $hotline['phone'] }}</b></span>
    </a>
    <details class="hotline-menu">
        <summary>{{ __('hotline.change') }}</summary>
        <form method="post" action="{{ route('visitor-country') }}" class="hotline-form">@csrf
            <label for="hotline-country" class="font-bold">{{ __('hotline.choose') }}</label>
            <select id="hotline-country" name="country" class="member-input">
                <option value="auto" @selected(! $hotline['chosen'])>{{ __('hotline.auto') }}</option>
                @foreach($hotlineCountries as $option)<option value="{{ $option['code'] }}" @selected($hotline['chosen'] && $hotline['code'] === $option['code'])>{{ app()->getLocale() === 'en' ? $option['name_en'] : $option['name_ar'] }}</option>@endforeach
            </select>
            <button class="member-button member-button-primary">{{ __('hotline.save') }}</button>
            <p class="text-xs leading-6 text-slate-500">{{ __('hotline.note') }}</p>
        </form>
    </details>
</div>
@endisset
</div></div>
<header class="site-header"><div class="container-page flex h-[4.75rem] items-center justify-between gap-5"><a href="{{ route('home') }}" class="focus-ring flex items-center gap-3 rounded-xl"><span class="site-logo-mark"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/></svg></span><span class="whitespace-nowrap"><b class="block text-lg leading-6 text-navy dark:text-white">{{ config('app.name') }}</b><small class="text-xs text-slate-500 dark:text-slate-400">أمانك الرقمي أولاً</small></span></a>
<nav class="site-nav hidden xl:flex" aria-label="التنقل الرئيسي">@foreach($navLinks as [$route,$label,$patterns])<a href="{{ route($route) }}" @class(['is-active'=>$isActive($patterns)]) @if($isActive($patterns)) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
<div class="flex items-center gap-2"><form method="post" action="{{ route('locale') }}">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale()==='ar'?'en':'ar' }}"><button class="site-icon-btn focus-ring text-sm font-bold">{{ app()->getLocale()==='ar'?'EN':'ع' }}</button></form><button @click="dark=!dark" class="site-icon-btn focus-ring" aria-label="الوضع الداكن"><svg x-show="!dark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg><svg x-show="dark" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button>@auth<a href="{{ route('account') }}" class="site-btn-ghost hidden sm:inline-flex">حسابي</a>@else<a href="{{ route('login') }}" class="site-btn-ghost hidden sm:inline-flex">دخول</a>@endauth<a href="{{ route('blackmail.help') }}" class="site-btn-danger hidden sm:inline-flex"><span class="site-dot" aria-hidden="true"></span>مساعدة عاجلة</a><button @click="menu=!menu" class="site-icon-btn border border-slate-200 xl:hidden dark:border-slate-700" aria-label="القائمة" :aria-expanded="menu.toString()"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path x-show="!menu" d="M4 7h16M4 12h16M4 17h16"/><path x-show="menu" x-cloak d="M6 6l12 12M18 6 6 18"/></svg></button></div></div>
<nav x-show="menu" x-cloak x-transition class="container-page site-mobile-nav xl:hidden" aria-label="القائمة">@foreach($navLinks as [$route,$label,$patterns])<a href="{{ route($route) }}" @class(['is-active'=>$isActive($patterns)])>{{ $label }}</a>@endforeach @auth<a href="{{ route('account') }}">حسابي</a>@else<a href="{{ route('login') }}">تسجيل الدخول</a>@endauth<button type="button" data-pwa-install hidden class="site-install-btn"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>{{ __('site.install_app') }}</button><a class="site-btn-danger mt-2 justify-center" href="{{ route('blackmail.help') }}">مساعدة عاجلة ضد الابتزاز</a></nav></header>
<main id="main">{{ $slot??'' }}@yield('content')</main>
<footer class="site-footer"><div class="container-page grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]"><div><a href="{{ route('home') }}" class="flex items-center gap-3"><span class="site-logo-mark bg-white/10"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/></svg></span><b class="text-xl text-white">{{ config('app.name') }}</b></a><p class="mt-5 leading-8">منصة عربية مستقلة للتوعية والإرشاد. لسنا جهة إنفاذ قانون أو قناة بلاغات حكومية.</p></div>
<div><h2 class="site-footer-title">روابط مهمة</h2><div class="site-footer-links"><a href="{{ route('help') }}">المساعدة الفورية</a><a href="{{ route('protection') }}">الحماية الرقمية</a><a href="{{ route('contact') }}">تواصل معنا</a></div></div>
<div><h2 class="site-footer-title">استكشف</h2><div class="site-footer-links"><a href="{{ route('blog') }}">{{ __('blog.title') }}</a><a href="{{ route('events') }}">{{ __('events.title') }}</a><a href="{{ route('services') }}">{{ __('billing.services') }}</a><a href="{{ route('countries') }}">دليل الدول</a><a href="{{ route('consultation') }}">الاستشارات</a><a href="{{ route('consultation.track') }}">متابعة طلب</a></div></div>
<div><h2 class="site-footer-title">الخصوصية أولاً</h2><p class="mt-4 leading-8">لا نطلب صوراً خاصة أو كلمات مرور. استخدم خدمات الطوارئ المحلية عند وجود خطر جسدي مباشر.</p></div></div>
<div class="border-t border-white/10"><div class="container-page flex flex-wrap items-center justify-between gap-3 py-6 text-sm"><span>© {{ date('Y') }} {{ config('app.name') }}</span><span class="flex flex-wrap items-center gap-4">@if(is_file((string) config('platform.geo.database')))<span class="text-xs">{{ __('hotline.attribution') }} <a href="https://db-ip.com" rel="noopener" target="_blank" class="underline hover:text-white">IP Geolocation by DB-IP</a></span>@endif<a href="{{ route('privacy.center') }}" class="hover:text-white">مركز الخصوصية</a></span></div></div></footer>@livewireScripts</body></html>
