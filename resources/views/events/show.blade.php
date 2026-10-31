@extends('layouts.app',['title'=>$event->localized('title')])
@section('content')
@php
    $ended=$event->ends_at->isPast();
    $cancelled=$event->status==='cancelled';
@endphp
<section class="container-page py-12"><a href="{{ route('events') }}" class="font-bold text-brand">{{ __('events.back') }} ←</a><div class="mt-6 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_22rem]"><article><span class="eyebrow">{{ \App\Models\Event::types()[$event->event_type]??$event->event_type }}</span><h1 class="section-title">{{ $event->localized('title') }}</h1><p class="mt-5 text-lg leading-8">{{ $event->localized('summary') }}</p>@if($cancelled || $ended)<p role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 leading-7 text-amber-950">{{ __('events.'.($cancelled?'cancelled_notice':'ended_notice')) }}</p>@endif
@if($event->cover_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($event->cover_image) }}" alt="" class="mt-6 max-h-96 w-full rounded-3xl object-cover">@endif
<h2 class="mt-8 text-2xl font-bold">{{ __('events.description') }}</h2><p class="mt-4 whitespace-pre-line leading-9">{{ $event->localized('description') }}</p>
@if($event->agenda)<section class="card mt-8"><h2 class="text-2xl font-bold">{{ __('events.agenda') }}</h2><ol class="mt-5 space-y-4">@foreach($event->agenda as $item)<li class="flex flex-wrap gap-4 border-b border-slate-200 pb-4 last:border-0"><bdi class="font-bold text-brand" dir="ltr">{{ $item['time']??'' }}</bdi><span>{{ app()->getLocale()==='en'?($item['title_en']??$item['title_ar']??''):($item['title_ar']??'') }}</span></li>@endforeach</ol></section>@endif
</article><aside class="card lg:sticky lg:top-24"><h2 class="text-xl font-bold">{{ __('events.details') }}</h2><dl class="mt-5 space-y-5">@foreach(['start'=>$event->starts_at->timezone('Asia/Muscat')->format('Y-m-d H:i'),'end'=>$event->ends_at->timezone('Asia/Muscat')->format('Y-m-d H:i'),'mode'=>\App\Models\Event::modes()[$event->attendance_mode]??$event->attendance_mode,'venue'=>$event->localized('venue'),'speaker'=>$event->localized('speaker')] as $label=>$value)@if($value)<div><dt class="text-sm font-bold text-brand">{{ __('events.'.$label) }}</dt><dd class="mt-1 leading-7">{{ $value }}</dd></div>@endif @endforeach</dl><p class="mt-5 text-xs leading-7">{{ __('events.timezone') }}</p>
@if(!$ended && !$cancelled)
@if($event->registration_url && in_array(strtolower(parse_url($event->registration_url,PHP_URL_SCHEME)??''),['https','http'],true))<a href="{{ $event->registration_url }}" target="_blank" rel="noopener noreferrer" class="member-button member-button-primary mt-6 w-full">{{ __('events.register') }}</a>@else<p class="mt-5 rounded-xl bg-blue-50 p-4 text-sm leading-7 text-blue-950">{{ __('events.no_registration') }}</p>@endif
@if($event->location_url && in_array(strtolower(parse_url($event->location_url,PHP_URL_SCHEME)??''),['https','http'],true))<a href="{{ $event->location_url }}" target="_blank" rel="noopener noreferrer" class="member-button mt-3 w-full border">{{ __('events.location') }}</a>@endif
@endif</aside></div></section>
@endsection
