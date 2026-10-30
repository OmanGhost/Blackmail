<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('طلبات الاستشارة الجديدة', ConsultationRequest::where('status', 'new')->count())->description('تحتاج متابعة')->color('danger')->icon('heroicon-o-chat-bubble-left-right'),
            Stat::make('المقالات المنشورة', Article::published()->count())->description('من إجمالي '.Article::count())->color('success')->icon('heroicon-o-document-text'),
            Stat::make('الأعضاء', User::whereHas('roles', fn ($q) => $q->where('name', 'member'))->count())->color('primary')->icon('heroicon-o-users'),
            Stat::make('الدول المتحققة', Country::verified()->count())->description('من 22 دولة')->color('warning')->icon('heroicon-o-globe-alt'),
        ];
    }
}
