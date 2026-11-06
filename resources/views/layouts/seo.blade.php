@php
    $seoSite = config('app.name');
    $seoPageTitle = $metaTitle ?? $title ?? null;
    $seoFullTitle = $seoPageTitle ? $seoPageTitle.' | '.$seoSite : $seoSite;
    $seoDescription = \App\Support\Seo::text($metaDescription ?? null) ?: __('seo.default');
    $seoPage = (int) request()->query('page', 1);
    $seoCanonical = url()->current().($seoPage > 1 ? '?page='.$seoPage : '');
    $seoImage = $metaImage ?? \App\Support\Seo::defaultImage();
@endphp
<title>{{ $seoFullTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta property="og:site_name" content="{{ $seoSite }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'ar_AR' }}">
<meta property="og:type" content="{{ $metaType ?? 'website' }}">
<meta property="og:title" content="{{ $seoPageTitle ?? $seoSite }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
@unless(isset($metaImage))<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">@endunless
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoPageTitle ?? $seoSite }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="إبتزاز">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#0b1d30" media="(prefers-color-scheme: dark)">
@foreach(array_filter($jsonLd ?? []) as $seoSchema)<script type="application/ld+json">{!! json_encode($seoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
