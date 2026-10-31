<?php
namespace App\Filament\Resources\DirectoryRequests;

use App\Models\DirectoryRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class DirectoryRequestResource extends Resource
{
    protected static ?string $model=DirectoryRequest::class;
    public static function getNavigationLabel(): string { return __('directory.requests'); }
    public static function getModelLabel(): string { return __('directory.requests'); }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';
    public static function getNavigationGroup(): ?string { return __('admin.directory'); }
    public static function canAccess(): bool { return auth()->user()?->hasRole('super-admin') ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function table(Table $table): Table
    {
        return $table->defaultSort('id','desc')->columns([
            TextColumn::make('id')->label('#')->formatStateUsing(fn($state)=>200000+$state),
            TextColumn::make('kind')->label(__('directory.kind'))->formatStateUsing(fn($state)=>__('directory.'.$state)),
            TextColumn::make('country_name')->label(__('directory.country'))->searchable(),
            TextColumn::make('status')->label(__('directory.status'))->badge()->formatStateUsing(fn($state)=>DirectoryRequest::statuses()[$state]??$state),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([SelectFilter::make('status')->label(__('directory.status'))->options(DirectoryRequest::statuses())])
        ->recordActions([
            Action::make('review')->label(__('directory.review'))->modalDescription(__('directory.review_notice'))
                ->fillForm(fn($record)=>$record->only(['contact_name','organization','job_title','email','phone','official_url','details','status','review_notes']))
                ->schema([
                    TextInput::make('contact_name')->label(__('directory.contact_name'))->readOnly(),
                    TextInput::make('organization')->label(__('directory.organization'))->readOnly(),
                    TextInput::make('job_title')->label(__('directory.job_title'))->readOnly(),
                    TextInput::make('email')->label(__('directory.email'))->readOnly(),
                    TextInput::make('phone')->label(__('directory.phone'))->readOnly(),
                    TextInput::make('official_url')->label(__('directory.official_url'))->readOnly(),
                    Textarea::make('details')->label(__('directory.details'))->rows(8)->readOnly(),
                    Select::make('status')->label(__('directory.status'))->options(DirectoryRequest::statuses())->required(),
                    Textarea::make('review_notes')->label(__('directory.notes'))->maxLength(5000),
                ])->action(function($record,array $data): void {
                    abort_unless(static::canAccess(),403);
                    $record->update(['status'=>$data['status'],'review_notes'=>$data['review_notes']??null,'reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
                }),
            Action::make('country')->label(__('directory.manage'))->url(fn($record)=>$record->country_id
                ? \App\Filament\Resources\Countries\CountryResource::getUrl('edit',['record'=>$record->country_id])
                : \App\Filament\Resources\Countries\CountryResource::getUrl('create')),
        ]);
    }
    public static function getPages(): array { return ['index'=>Pages\ManageDirectoryRequests::route('/')]; }
}
