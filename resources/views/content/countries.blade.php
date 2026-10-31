@extends('layouts.app',['title'=>__('countries.eyebrow')])
@php
    $en = app()->getLocale() === 'en';
    $isVerified = fn ($country) => $country->verification_status === 'verified' && $country->is_published;
    $sortUrl = fn (string $column) => request()->fullUrlWithQuery(['sort' => $column, 'dir' => $filters['sort'] === $column && $filters['dir'] === 'asc' ? 'desc' : 'asc', 'page' => null]);
    $ariaSort = fn (string $column) => $filters['sort'] === $column ? ($filters['dir'] === 'asc' ? 'ascending' : 'descending') : 'none';
    $arrow = fn (string $column) => $filters['sort'] === $column ? ($filters['dir'] === 'asc' ? '▲' : '▼') : '↕';
    $hasFilters = $filters['q'] !== '' || $filters['status'] !== 'all' || $filters['sort'] !== 'name' || $filters['dir'] !== 'asc' || $filters['per_page'] !== 12;
@endphp
@section('content')
<section class="container-page py-16">
    <span class="eyebrow">{{ __('countries.eyebrow') }}</span>
    <h1 class="section-title">{{ __('countries.title') }}</h1>
    <p class="mt-5 max-w-3xl leading-8">{{ __('countries.intro') }}</p>
    <p class="mt-3 text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ __('countries.summary', ['verified' => $totals['verified'], 'all' => $totals['all']]) }}</p>

    <aside class="mt-7 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-950 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100"><p class="font-bold">{{ __('directory.cta') }}</p><a href="{{ route('directory.requests.create',['kind'=>'addition']) }}" class="member-button member-button-primary">{{ __('directory.add') }}</a></aside>

    <form method="get" action="{{ route('countries') }}" class="directory-toolbar" role="search">
        <input type="hidden" name="view" value="{{ $filters['view'] }}">
        <label class="directory-field directory-field-wide"><span class="member-label">{{ __('countries.search_label') }}</span><input type="search" name="q" value="{{ $filters['q'] }}" maxlength="60" placeholder="{{ __('countries.search') }}" class="member-input"></label>
        <label class="directory-field"><span class="member-label">{{ __('countries.status') }}</span><select name="status" class="member-input">@foreach(['all','verified','pending'] as $status)<option value="{{ $status }}" @selected($filters['status']===$status)>{{ __('countries.status_'.$status) }}</option>@endforeach</select></label>
        <label class="directory-field"><span class="member-label">{{ __('countries.sort') }}</span><select name="sort" class="member-input">@foreach(['name','status','verified_at','authorities'] as $sort)<option value="{{ $sort }}" @selected($filters['sort']===$sort)>{{ __('countries.sort_'.$sort) }}</option>@endforeach</select></label>
        <label class="directory-field"><span class="sr-only">{{ __('countries.sort') }}</span><span class="member-label" aria-hidden="true">&nbsp;</span><select name="dir" class="member-input" aria-label="{{ __('countries.sort') }}"><option value="asc" @selected($filters['dir']==='asc')>{{ __('countries.dir_asc') }}</option><option value="desc" @selected($filters['dir']==='desc')>{{ __('countries.dir_desc') }}</option></select></label>
        <label class="directory-field"><span class="member-label">{{ __('countries.per_page') }}</span><select name="per_page" class="member-input">@foreach([12,24,48] as $size)<option value="{{ $size }}" @selected($filters['per_page']===$size)>{{ $size }}</option>@endforeach</select></label>
        <div class="flex items-end gap-2"><button class="member-button member-button-primary">{{ __('countries.apply') }}</button>@if($hasFilters)<a href="{{ route('countries',['view'=>$filters['view']]) }}" class="member-button border border-slate-200 dark:border-slate-700">{{ __('countries.reset') }}</a>@endif</div>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400" role="status">@if($countries->total()){{ __('countries.results', ['from' => $countries->firstItem(), 'to' => $countries->lastItem(), 'total' => $countries->total()]) }}@endif</p>
        <nav class="directory-view-toggle" aria-label="{{ __('countries.view') }}">
            @foreach(['table','cards'] as $mode)
                <a href="{{ request()->fullUrlWithQuery(['view' => $mode]) }}" @class(['is-active' => $filters['view'] === $mode]) @if($filters['view'] === $mode) aria-current="true" @endif>{{ __('countries.view_'.$mode) }}</a>
            @endforeach
        </nav>
    </div>

    @if($countries->isEmpty())
        <div class="mt-6 rounded-3xl border border-dashed border-slate-300 p-10 text-center text-slate-500 dark:border-slate-700">{{ __('countries.empty') }}</div>
    @elseif($filters['view'] === 'table')
        <div class="directory-table-wrap">
            <table class="directory-table">
                <thead>
                    <tr>
                        <th scope="col" aria-sort="{{ $ariaSort('name') }}"><a href="{{ $sortUrl('name') }}" aria-label="{{ __('countries.sort_by', ['column' => __('countries.col_country')]) }}">{{ __('countries.col_country') }} <span aria-hidden="true">{{ $arrow('name') }}</span></a></th>
                        <th scope="col">{{ __('countries.col_code') }}</th>
                        <th scope="col" aria-sort="{{ $ariaSort('status') }}"><a href="{{ $sortUrl('status') }}" aria-label="{{ __('countries.sort_by', ['column' => __('countries.col_status')]) }}">{{ __('countries.col_status') }} <span aria-hidden="true">{{ $arrow('status') }}</span></a></th>
                        <th scope="col" aria-sort="{{ $ariaSort('authorities') }}"><a href="{{ $sortUrl('authorities') }}" aria-label="{{ __('countries.sort_by', ['column' => __('countries.col_authorities')]) }}">{{ __('countries.col_authorities') }} <span aria-hidden="true">{{ $arrow('authorities') }}</span></a></th>
                        <th scope="col" aria-sort="{{ $ariaSort('verified_at') }}"><a href="{{ $sortUrl('verified_at') }}" aria-label="{{ __('countries.sort_by', ['column' => __('countries.col_verified_at')]) }}">{{ __('countries.col_verified_at') }} <span aria-hidden="true">{{ $arrow('verified_at') }}</span></a></th>
                        <th scope="col">{{ __('countries.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $country)
                        <tr>
                            <th scope="row" class="font-bold text-navy dark:text-white">{{ $en ? $country->name_en : $country->name_ar }}</th>
                            <td dir="ltr" class="text-start font-mono text-xs">{{ $country->code }}</td>
                            <td>@if($isVerified($country))<span class="verified-badge">{{ __('countries.verified') }}</span>@else<span class="pending-badge">{{ __('countries.pending') }}</span>@endif</td>
                            <td dir="ltr" class="text-start tabular-nums">{{ $isVerified($country) ? $country->authorities_count : '—' }}</td>
                            <td dir="ltr" class="text-start tabular-nums">{{ $isVerified($country) && $country->last_verified_at ? $country->last_verified_at->format('Y-m-d') : '—' }}</td>
                            <td><div class="flex flex-wrap gap-x-4 gap-y-1 text-sm font-bold">@if($isVerified($country))<a href="{{ route('countries.show',$country) }}" class="text-brand">{{ __('countries.view_sources') }}</a>@endif<a href="{{ route('directory.requests.create',['kind'=>'correction','country'=>$country->id]) }}" class="text-slate-500 underline-offset-4 hover:text-brand hover:underline dark:text-slate-400">{{ __('directory.edit') }}</a></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($countries as $country)
                <article class="country-card">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold text-navy dark:text-white">{{ $en ? $country->name_en : $country->name_ar }}</h2>@if($isVerified($country))<span class="verified-badge">{{ __('countries.verified') }}</span>@else<span class="pending-badge">{{ __('countries.pending') }}</span>@endif</div>
                    @if($isVerified($country))<a href="{{ route('countries.show',$country) }}" class="mt-5 inline-flex font-bold text-brand">{{ __('countries.view_sources') }}</a>@else<p class="mt-4 text-sm">{{ __('countries.not_published') }}</p>@endif
                    <a href="{{ route('directory.requests.create',['kind'=>'correction','country'=>$country->id]) }}" class="mt-4 block text-sm font-bold text-brand underline">{{ __('directory.edit') }}</a>
                </article>
            @endforeach
        </div>
    @endif

    @if($countries->hasPages())<div class="mt-8">{{ $countries->onEachSide(1)->links() }}</div>@endif
</section>
@endsection
