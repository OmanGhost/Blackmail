@php($now = now('Asia/Muscat')->locale(app()->getLocale()))
<div class="admin-dash-header">
    <section class="admin-welcome">
        <div class="admin-welcome-copy">
            <span class="admin-kicker">{{ __('admin.overview.kicker') }} · {{ $now->translatedFormat('l j F Y') }}</span>
            <h1>{{ __('admin.overview.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p>{{ __('admin.overview.intro') }}</p>
            <div class="admin-shortcuts">
                @if(\App\Filament\Resources\ConsultationRequests\ConsultationRequestResource::canAccess())
                    <a class="admin-shortcut-primary" href="{{ \App\Filament\Resources\ConsultationRequests\ConsultationRequestResource::getUrl() }}">{{ app()->getLocale() === 'ar' ? 'إدارة طلبات الاستشارة' : 'Manage consultations' }} <span aria-hidden="true">↗</span></a>
                @endif
                <a href="{{ route('home') }}" target="_blank" rel="noopener">{{ __('admin.overview.view_site') }} <span aria-hidden="true">↗</span></a>
            </div>
        </div>
        @if($attention !== null)
            <div class="admin-attention" aria-labelledby="admin-attention-title">
                <h2 id="admin-attention-title">{{ __('admin.overview.attention') }}</h2>
                @if(array_sum($attention) === 0)
                    <p class="admin-attention-clear"><x-filament::icon icon="heroicon-o-check-circle" class="size-5" /> {{ __('admin.overview.all_clear') }}</p>
                @endif
                <dl>
                    @foreach(['urgent' => 'danger', 'unassigned' => 'warning', 'overdue' => 'info'] as $key => $tone)
                        <div class="admin-attention-item is-{{ $tone }} {{ $attention[$key] ? 'has-items' : '' }}">
                            <dt>{{ __('admin.overview.attention_'.$key) }}</dt>
                            <dd dir="ltr">{{ number_format($attention[$key]) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($operationsUrl)<a href="{{ $operationsUrl }}" class="admin-attention-link">{{ __('admin.overview.action_operations') }} <span aria-hidden="true">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</span></a>@endif
            </div>
        @endif
    </section>

    @if(count($actions))
        <nav class="admin-quick" aria-label="{{ __('admin.overview.quick_actions') }}">
            @foreach($actions as [$label, $url, $icon])
                <a href="{{ $url }}" class="admin-quick-item">
                    <span class="admin-quick-icon"><x-filament::icon :icon="$icon" class="size-5" /></span>
                    <span>{{ __('admin.overview.'.$label) }}</span>
                </a>
            @endforeach
        </nav>
    @endif
</div>
