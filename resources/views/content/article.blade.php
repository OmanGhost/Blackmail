@extends('layouts.app',['title'=>$translation->title])
@section('content')
<article class="container-page py-12"><div class="mx-auto max-w-4xl"><a href="{{ route('blog') }}" class="text-sm font-bold text-brand">{{ __('blog.back') }} ←</a><div class="mt-6 flex flex-wrap gap-4 text-sm"><span class="font-bold text-brand">{{ \App\Models\Article::typeOptions()[$article->post_type]??__('blog.article') }}</span><time>{{ __('blog.published') }}: {{ $article->published_at->format('Y-m-d') }}</time><span>{{ __('blog.author') }}: {{ $article->author?->name }}</span></div><h1 class="mt-5 text-4xl font-extrabold leading-tight">{{ $translation->title }}</h1><p class="mt-5 text-lg leading-8">{{ $translation->summary }}</p>
@if($article->featured_image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($article->featured_image) }}" alt="" class="mt-8 max-h-[32rem] w-full rounded-3xl object-cover">@endif
@if($article->post_type==='video' && $article->video_url && in_array(strtolower(parse_url($article->video_url,PHP_URL_SCHEME)??''),['https','http'],true))<aside class="mt-8 rounded-2xl bg-navy p-6 text-white"><a href="{{ $article->video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-3 text-lg font-bold">▶ {{ __('blog.watch') }}</a></aside>@endif
<div class="category-guide mt-8">{!! (new \Symfony\Component\HtmlSanitizer\HtmlSanitizer((new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig)->allowSafeElements()))->sanitize($translation->content) !!}</div>
</div></article>
@endsection
