<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class RequestsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return ConsultationRequestResource::canAccess();
    }

    public function getHeading(): string
    {
        return __('admin.overview.trend_heading');
    }

    public function getDescription(): string
    {
        return __('admin.overview.trend_description');
    }

    /** Requests created per day (Oman time) for the last $days days, oldest first. */
    public static function dailyCounts(int $days): array
    {
        $zone = 'Asia/Muscat';
        $start = CarbonImmutable::now($zone)->startOfDay()->subDays($days - 1);
        $counts = [];
        for ($day = $start; $day->lte(CarbonImmutable::now($zone)); $day = $day->addDay()) {
            $counts[$day->format('Y-m-d')] = 0;
        }
        ConsultationRequest::query()->where('created_at', '>=', $start->utc())->pluck('created_at')
            ->each(function ($createdAt) use (&$counts, $zone) {
                $key = CarbonImmutable::parse($createdAt)->timezone($zone)->format('Y-m-d');
                if (isset($counts[$key])) {
                    $counts[$key]++;
                }
            });

        return $counts;
    }

    protected function getData(): array
    {
        $counts = static::dailyCounts(14);

        return [
            'datasets' => [[
                'label' => __('admin.overview.requests'),
                'data' => array_values($counts),
                'fill' => true,
                'tension' => 0.35,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                'pointRadius' => 3,
            ]],
            'labels' => array_map(fn ($date) => CarbonImmutable::parse($date)->format('m/d'), array_keys($counts)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
