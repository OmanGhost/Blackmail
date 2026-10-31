@if ($paginator->hasPages())
@php
    $en = app()->getLocale() === 'en';
    $rtl = ! $en;
    $prevLabel = $en ? 'Previous' : 'السابق';
    $nextLabel = $en ? 'Next' : 'التالي';
@endphp
<nav class="site-pagination" role="navigation" aria-label="{{ $en ? 'Pagination' : 'التنقل بين الصفحات' }}">
    @if (method_exists($paginator, 'total'))
        <p class="site-pagination-summary">{{ $en ? 'Page' : 'صفحة' }} <b dir="ltr">{{ $paginator->currentPage() }}</b> {{ $en ? 'of' : 'من' }} <b dir="ltr">{{ $paginator->lastPage() }}</b></p>
    @endif
    <ul>
        <li>
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true"><span aria-hidden="true">{{ $rtl ? '→' : '←' }}</span> {{ $prevLabel }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"><span aria-hidden="true">{{ $rtl ? '→' : '←' }}</span> {{ $prevLabel }}</a>
            @endif
        </li>
        @isset($elements)
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="is-gap" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="is-current" aria-current="page" dir="ltr">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" dir="ltr" aria-label="{{ $en ? 'Go to page' : 'الانتقال إلى الصفحة' }} {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        @endisset
        <li>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ $nextLabel }} <span aria-hidden="true">{{ $rtl ? '←' : '→' }}</span></a>
            @else
                <span class="is-disabled" aria-disabled="true">{{ $nextLabel }} <span aria-hidden="true">{{ $rtl ? '←' : '→' }}</span></span>
            @endif
        </li>
    </ul>
</nav>
@endif
