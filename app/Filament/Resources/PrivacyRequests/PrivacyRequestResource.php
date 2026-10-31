<?php
namespace App\Filament\Resources\PrivacyRequests;

use App\Models\PrivacyRequest;
use App\Services\PrivacyErasure;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class PrivacyRequestResource extends Resource
{
    protected static ?string $model = PrivacyRequest::class;
    public static function getNavigationLabel(): string { return 'طلبات حذف البيانات'; }
    public static function getModelLabel(): string { return 'طلب حذف البيانات'; }
    public static function getPluralModelLabel(): string { return 'طلبات حذف البيانات'; }
    public static function getNavigationGroup(): ?string { return __('admin.system'); }
    public static function canAccess(): bool { return auth()->user()?->hasRole('super-admin') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table
    {
        return $table->defaultSort('id','desc')->columns([
            TextColumn::make('user.name')->label('المستخدم')->placeholder('زائر / محذوف'),
            TextColumn::make('consultationRequest.request_number')->label('رقم الطلب')->placeholder('الحساب وطلباته'),
            TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state)=>__('workflow.deletion_'.$state)),
            TextColumn::make('created_at')->label('وقت التقديم')->dateTime(),
            TextColumn::make('reviewed_at')->label('وقت المعالجة')->dateTime(),
        ])->recordActions([
            Action::make('review')->label('بدء المراجعة')->visible(fn ($record)=>$record->status==='pending')
                ->action(fn ($record)=>$record->update(['status'=>'in_review','reviewed_by'=>auth()->id(),'reviewed_at'=>now()])),
            Action::make('reject')->label('رفض')->requiresConfirmation()->visible(fn ($record)=>in_array($record->status,['pending','in_review']))
                ->action(fn ($record)=>$record->update(['status'=>'rejected','reviewed_by'=>auth()->id(),'reviewed_at'=>now()])),
            Action::make('erase')->label('الموافقة وتنفيذ الحذف النهائي')->color('danger')->requiresConfirmation()
                ->modalDescription('سيُحذف الطلب ورسائله وخطته وملاحظاته، أو الحساب وجميع طلباته إذا كان المطلوب حذف الحساب. لا يمكن التراجع عن العملية.')
                ->visible(fn ($record)=>in_array($record->status,['pending','in_review']))
                ->action(function ($record): void {
                    app(PrivacyErasure::class)->execute($record->id,auth()->id());
                    Notification::make()->title('تم تنفيذ الحذف النهائي')->success()->send();
                }),
        ]);
    }
    public static function getPages(): array { return ['index'=>Pages\ManagePrivacyRequests::route('/')]; }
}
