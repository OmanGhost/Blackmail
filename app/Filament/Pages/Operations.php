<?php
namespace App\Filament\Pages;

use App\Models\ConsultationRequest;
use App\Filament\Resources\ConsultationRequests\ConsultationRequestResource;
use App\Filament\Resources\ConsultationRequests\Tables\ConsultationRequestsTable;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;

class Operations extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.operations';
    public static function canAccess(): bool { return ConsultationRequestResource::canAccess(); }
    public static function getNavigationLabel(): string { return __('workflow.operations'); }
    public static function getNavigationGroup(): ?string { return __('admin.operations'); }
    public function getTitle(): string { return __('workflow.operations'); }
    public function counts(): array
    {
        return [
            'open'=>ConsultationRequest::open()->count(),
            'unassigned'=>ConsultationRequest::open()->whereNull('assigned_to')->count(),
            'urgent'=>ConsultationRequest::open()->whereIn('urgency',['urgent','critical'])->count(),
            'overdue'=>ConsultationRequest::overdue()->count(),
        ];
    }
    public function table(Table $table): Table
    {
        return ConsultationRequestsTable::configure($table)
            ->query(ConsultationRequest::query()->open())
            ->recordUrl(fn ($record) => ConsultationRequestResource::getUrl('view',['record'=>$record]))
            ->recordActions([\Filament\Actions\Action::make('open')->label('عرض الطلب')->url(fn ($record)=>ConsultationRequestResource::getUrl('view',['record'=>$record]))])
            ->pushFilters([
                Filter::make('unassigned')->label('غير مسندة')->query(fn ($query)=>$query->whereNull('assigned_to')),
                Filter::make('urgent_only')->label('العاجلة والحرجة')->query(fn ($query)=>$query->whereIn('urgency',['urgent','critical'])),
                Filter::make('overdue')->label('دون رد من الفريق خلال 24 ساعة')->query(fn ($query)=>$query->overdue()),
            ]);
    }
}
