@extends('layouts.app',['title'=>__('billing.services'),'metaDescription'=>__('billing.services_intro')])
@section('content')
<section class="bg-navy py-12 text-white"><div class="container-page"><span class="text-sm text-blue-200">{{ __('billing.services') }}</span><h1 class="mt-3 text-4xl font-extrabold">{{ __('billing.services') }}</h1><p class="mt-5 max-w-3xl leading-8 text-slate-200">{{ __('billing.services_intro') }}</p><p class="mt-4 text-sm text-emerald-200">{{ __('billing.free_help') }} <a href="{{ route('consultation') }}" class="font-bold underline">{{ __('billing.free_help_link') }}</a></p></div></section>
<section class="container-page space-y-12 py-10">
@forelse($sections as $section)
    <div id="{{ $section->slug }}"><h2 class="text-2xl font-extrabold text-navy dark:text-white">{{ $section->name() }}</h2>@if($section->description())<p class="mt-3 max-w-3xl leading-8 text-slate-600 dark:text-slate-300">{{ $section->description() }}</p>@endif
    <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($section->services as $service)
        <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900"><h3 class="text-lg font-bold text-navy dark:text-white"><a href="{{ route('services.show',$service) }}" class="hover:text-brand">{{ $service->name() }}</a></h3><p class="mt-3 flex-1 text-sm leading-7">{{ $service->summary() }}</p>
        <div class="mt-5 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4 dark:border-slate-700"><div><span class="block text-xs text-slate-500">{{ __('billing.price') }}</span><strong class="text-xl text-navy dark:text-white">{{ $service->formattedPrice() }}</strong>@if($service->duration())<span class="mt-1 block text-xs text-slate-500">{{ __('billing.duration') }}: {{ $service->duration() }}</span>@endif</div><a href="{{ route('services.show',$service) }}" class="member-button member-button-primary text-sm">{{ __('billing.view_service') }}</a></div></article>
    @endforeach</div></div>
@empty
    <p class="py-12 text-center text-slate-500">{{ __('billing.empty') }}</p>
@endforelse
</section>
@endsection
