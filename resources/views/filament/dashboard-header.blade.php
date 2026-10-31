<div class="admin-welcome">
    <div class="admin-welcome-copy">
        <span class="admin-kicker">{{ app()->getLocale() === 'ar' ? 'مركز إدارة المنصة' : 'Platform control center' }}</span>
        <h1>{{ app()->getLocale() === 'ar' ? 'مرحبًا، ' : 'Welcome, ' }}{{ auth()->user()->name }}</h1>
        <p>{{ app()->getLocale() === 'ar' ? 'تابع الطلبات، نظّم المحتوى، وأدر خدمات المنصة من مكان واحد.' : 'Manage requests, organize content, and oversee platform services in one place.' }}</p>
        <div class="admin-shortcuts">
            @if(\App\Filament\Resources\ConsultationRequests\ConsultationRequestResource::canAccess())
            <a class="admin-shortcut-primary" href="{{ \App\Filament\Resources\ConsultationRequests\ConsultationRequestResource::getUrl() }}">{{ app()->getLocale() === 'ar' ? 'إدارة طلبات الاستشارة' : 'Manage consultations' }} <span aria-hidden="true">↗</span></a>
            @endif
            <a href="{{ route('home') }}" target="_blank" rel="noopener">{{ app()->getLocale() === 'ar' ? 'عرض الموقع' : 'View website' }} <span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="admin-welcome-mark" aria-hidden="true"><svg viewBox="0 0 100 100" fill="none"><path d="M50 10 20 22v25c0 22 13 36 30 43 17-7 30-21 30-43V22L50 10Z" stroke="currentColor" stroke-width="3"/><path d="m35 49 10 10 21-23" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg><span>{{ config('app.name') }}</span></div>
</div>
