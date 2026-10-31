<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\Article;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Event;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $stats = [];

        if (ConsultationRequestResource::canAccess()) {
            $daily = RequestsTrendChart::dailyCounts(7);
            $stats[] = Stat::make(__('admin.overview.new_requests'), ConsultationRequest::where('status', 'new')->count())
                ->description(__('admin.overview.open_requests', ['count' => ConsultationRequest::open()->count()]))
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->chart(array_values($daily))
                ->color('danger')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(ConsultationRequestResource::getUrl());
        }

        $stats[] = Stat::make(__('admin.overview.published_articles'), Article::published()->count())
            ->description(__('admin.overview.of_total', ['count' => Article::count()]))
            ->descriptionIcon('heroicon-m-document-text')
            ->color('success')
            ->icon('heroicon-o-newspaper');

        $stats[] = Stat::make(__('admin.overview.members'), User::whereHas('roles', fn ($q) => $q->where('name', 'member'))->count())
            ->description(__('admin.overview.upcoming_events', ['count' => Event::query()->where('status', 'published')->where('starts_at', '>=', now())->count()]))
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('primary')
            ->icon('heroicon-o-users');

        $stats[] = Stat::make(__('admin.overview.verified_countries'), Country::verified()->count())
            ->description(__('admin.overview.of_total', ['count' => Country::count()]))
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('warning')
            ->icon('heroicon-o-globe-alt');

        return $stats;
    }
}
