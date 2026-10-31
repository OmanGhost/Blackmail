@php
    $english = app()->getLocale() === 'en';
    $shareUrl = route('articles.show', $article);
    $shareText = $translation->title;
@endphp
<aside class="article-share" data-article-share data-url="{{ $shareUrl }}" data-title="{{ $shareText }}" data-copied="{{ $english ? 'Link copied' : 'تم نسخ الرابط' }}" data-fallback="{{ $english ? 'Select and copy the link below.' : 'حدد الرابط أدناه وانسخه يدويًا.' }}" aria-label="{{ $english ? 'Share this post' : 'مشاركة المنشور' }}">
    <strong class="text-sm">{{ $english ? 'Share this post' : 'شارك هذا المنشور' }}</strong>
    <div class="flex flex-wrap gap-2">
        <a href="https://wa.me/?text={{ rawurlencode($shareText."\n".$shareUrl) }}" target="_blank" rel="noopener noreferrer" class="share-button share-whatsapp">{{ $english ? 'WhatsApp' : 'واتساب' }}</a>
        <a href="https://x.com/intent/tweet?{{ http_build_query(['text'=>$shareText,'url'=>$shareUrl], '', '&', PHP_QUERY_RFC3986) }}" target="_blank" rel="noopener noreferrer" class="share-button">X</a>
        <button type="button" data-copy-link class="share-button" hidden>{{ $english ? 'Copy link' : 'نسخ الرابط' }}</button>
        <button type="button" data-native-share class="share-button" hidden>{{ $english ? 'More sharing options' : 'خيارات مشاركة أخرى' }}</button>
    </div>
    <p data-share-status role="status" aria-live="polite" class="text-xs"></p>
    <label data-share-fallback hidden class="text-xs">{{ $english ? 'Post link' : 'رابط المنشور' }}<input type="url" value="{{ $shareUrl }}" readonly dir="ltr" class="mt-2 w-full rounded-lg border bg-white p-3 text-slate-900" onclick="this.select()"></label>
</aside>
