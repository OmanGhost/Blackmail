<?php

namespace App\Filament\Resources\ConsultationRequests;

use App\Filament\Resources\ConsultationRequests\Pages\CreateConsultationRequest;
use App\Filament\Resources\ConsultationRequests\Pages\EditConsultationRequest;
use App\Filament\Resources\ConsultationRequests\Pages\ListConsultationRequests;
use App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest;
use App\Filament\Resources\ConsultationRequests\Schemas\ConsultationRequestForm;
use App\Filament\Resources\ConsultationRequests\Schemas\ConsultationRequestInfolist;
use App\Filament\Resources\ConsultationRequests\Tables\ConsultationRequestsTable;
use App\Models\ConsultationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConsultationRequestResource extends Resource
{
    protected static ?string $model = ConsultationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.operations');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.consultations');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.consultations');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', ['super-admin', 'security-consultant', 'support-agent'])->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ConsultationRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsultationRequestsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConsultationRequestInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultationRequests::route('/'),
            'view' => ViewConsultationRequest::route('/{record}'),
            'edit' => EditConsultationRequest::route('/{record}/edit'),
        ];
    }
}
