<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Widgets\ChartWidget;

class RequestsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return ConsultationRequestResource::canAccess();
    }

    public function getHeading(): string
    {
        return __('admin.overview.status_heading');
    }

    public function getDescription(): string
    {
        return __('admin.overview.status_description');
    }

    protected function getData(): array
    {
        $colors = ['new' => '#2563eb', 'in_review' => '#f59e0b', 'in_progress' => '#6366f1', 'contacted' => '#14b8a6', 'completed' => '#10b981', 'closed' => '#94a3b8'];
        $totals = ConsultationRequest::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $labels = ConsultationRequest::statusOptions();
        $keys = array_keys($labels);

        return [
            'datasets' => [[
                'data' => array_map(fn ($key) => (int) ($totals[$key] ?? 0), $keys),
                'backgroundColor' => array_map(fn ($key) => $colors[$key] ?? '#64748b', $keys),
                'borderWidth' => 0,
            ]],
            'labels' => array_values($labels),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '68%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
