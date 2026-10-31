<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRequests extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ConsultationRequestResource::canAccess();
    }

    public function table(Table $table): Table
    {
        $categories = __('admin.overview.categories');
        $urgency = __('admin.overview.urgency');

        return $table
            ->heading(__('admin.overview.latest_heading'))
            ->description(__('admin.overview.latest_description'))
            ->query(ConsultationRequest::query()->open()->latest('id')->limit(6))
            ->paginated(false)
            ->recordUrl(fn (ConsultationRequest $record) => ConsultationRequestResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('request_number')->label(__('admin.overview.request_number'))->weight('bold'),
                TextColumn::make('category')->label(__('admin.fields.category'))
                    ->formatStateUsing(fn (?string $state) => $categories[$state] ?? $state),
                TextColumn::make('urgency')->label(__('admin.overview.priority'))->badge()
                    ->formatStateUsing(fn (?string $state) => $urgency[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) { 'critical' => 'danger', 'urgent' => 'warning', default => 'gray' }),
                TextColumn::make('status')->label(__('admin.overview.status'))->badge()
                    ->formatStateUsing(fn (string $state) => ConsultationRequest::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'new' => 'info', 'in_review' => 'warning', 'completed' => 'success', default => 'primary' }),
                TextColumn::make('created_at')->label(__('admin.overview.received'))->since()->timezone('Asia/Muscat'),
            ])
            ->emptyStateHeading(__('admin.overview.latest_empty'))
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
