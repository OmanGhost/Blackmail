@extends('layouts.app',['title'=>$service->name(),'metaDescription'=>$service->summary(),'jsonLd'=>[\App\Support\Seo::breadcrumbs([[__('seo.home_crumb'),route('home')],[__('billing.services'),route('services')],[$service->name(),route('services.show',$service)]])]])
@section('content')
<section class="bg-navy py-12 text-white"><div class="container-page"><nav class="flex flex-wrap gap-3 text-sm text-slate-300"><a href="{{ route('services') }}">{{ __('billing.services') }}</a><span aria-hidden="true">/</span><a href="{{ route('services') }}#{{ $service->section->slug }}">{{ $service->section->name() }}</a></nav><h1 class="mt-4 text-4xl font-extrabold">{{ $service->name() }}</h1><p class="mt-5 max-w-3xl leading-8 text-slate-200">{{ $service->summary() }}</p></div></section>
<section class="container-page grid items-start gap-8 py-12 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="min-w-0 space-y-8"><article class="rounded-3xl border border-slate-200 bg-white p-6 md:p-9 dark:border-slate-700 dark:bg-slate-900"><h2 class="text-xl font-extrabold text-navy dark:text-white">{{ __('billing.details') }}</h2>
        @if($service->details())<div class="category-guide mt-4">{!! \Illuminate\Support\Str::markdown($service->details(),['html_input'=>'strip','allow_unsafe_links'=>false]) !!}</div>@else<p class="mt-4 leading-8">{{ $service->summary() }}</p>@endif
    </article>
    @if($related->isNotEmpty())<div class="grid gap-4 sm:grid-cols-3">@foreach($related as $other)<a href="{{ route('services.show',$other) }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-brand dark:border-slate-700 dark:bg-slate-900"><b class="text-navy dark:text-white">{{ $other->name() }}</b><span class="mt-2 block text-sm text-slate-500">{{ $other->formattedPrice() }}</span></a>@endforeach</div>@endif
    </div>
    <aside class="space-y-5 lg:sticky lg:top-24"><div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <dl class="space-y-4"><div><dt class="text-xs text-slate-500">{{ __('billing.price') }}</dt><dd class="mt-1 text-3xl font-extrabold text-navy dark:text-white">{{ $service->formattedPrice() }}</dd></div>@if($service->duration())<div><dt class="text-xs text-slate-500">{{ __('billing.duration') }}</dt><dd class="mt-1 font-bold">{{ $service->duration() }}</dd></div>@endif</dl><p class="mt-3 text-xs text-slate-500">{{ __('billing.tax_note') }}</p>
        @auth
        <form method="post" action="{{ route('services.request',$service) }}" class="mt-6 space-y-4 border-t border-slate-100 pt-5 dark:border-slate-700">@csrf <x-form-errors/><input name="website" class="hidden" tabindex="-1" autocomplete="off">
            <h2 class="font-bold">{{ __('billing.request_title',['service'=>$service->name()]) }}</h2>
            <label class="block text-sm font-bold">{{ __('billing.request_description') }}<textarea name="description" required minlength="20" maxlength="3000" rows="5" class="mt-2 w-full rounded-xl border p-3 font-normal" placeholder="{{ __('billing.request_placeholder') }}">{{ old('description') }}</textarea></label>
            <p class="rounded-xl bg-blue-50 p-3 text-xs leading-6 text-blue-950">{{ __('billing.request_note') }}</p>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="consent" value="1" required><span>{{ __('billing.request_consent') }}</span></label>
            <button class="member-button member-button-primary w-full">{{ __('billing.send_request') }}</button><x-human-check :action="route('services.request',$service)" />
        </form>
        @else
        <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-700"><a href="{{ route('login') }}" class="member-button member-button-primary w-full">{{ __('billing.login_to_request') }}</a><p class="mt-4 text-sm leading-7">{{ __('billing.register_hint') }} <a href="{{ route('register') }}" class="font-bold text-brand underline">{{ __('billing.register') }}</a></p></div>
        @endauth
    </div><p class="text-sm leading-7">{{ __('billing.free_help') }} <a href="{{ route('consultation') }}" class="font-bold text-brand underline">{{ __('billing.free_help_link') }}</a></p></aside>
</section>
@endsection
