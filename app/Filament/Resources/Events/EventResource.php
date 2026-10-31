<?php
namespace App\Filament\Resources\Events;
use App\Models\Event;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\{TextInput,Textarea,Select,DateTimePicker,FileUpload,Repeater};
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\{EditAction,DeleteAction,Action};
class EventResource extends Resource
{
    protected static ?string $model=Event::class;
    public static function getNavigationLabel(): string { return __('events.title'); }
    public static function getModelLabel(): string { return __('events.title'); }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    public static function getNavigationGroup(): ?string { return __('admin.content'); }
    public static function canAccess(): bool { return auth()->user()?->roles()->whereIn('name',['super-admin','content-manager'])->exists()??false; }
    public static function form(Schema $schema): Schema
    {
        $fields=[TextInput::make('slug')->label(__('events.slug'))->required()->regex('/^[a-zA-Z0-9_-]+$/')->maxLength(255)->unique(ignoreRecord:true),
            Select::make('event_type')->label(__('events.type'))->options(Event::types())->default('workshop')->required(),
            Select::make('attendance_mode')->label(__('events.mode'))->options(Event::modes())->default('onsite')->required(),
            Select::make('status')->label(__('events.status'))->options(Event::statuses())->default('draft')->required(),
            DateTimePicker::make('starts_at')->label(__('events.start'))->timezone('Asia/Muscat')->required()->helperText(__('events.timezone')),
            DateTimePicker::make('ends_at')->label(__('events.end'))->timezone('Asia/Muscat')->required()->after('starts_at'),
            FileUpload::make('cover_image')->label(__('events.cover'))->image()->disk('public')->directory('events')->visibility('public')->maxSize(5120),
            TextInput::make('registration_url')->label(__('events.registration_url'))->url()->rules(['nullable','url:http,https'])->maxLength(1000),
            TextInput::make('location_url')->label(__('events.location_url'))->url()->rules(['nullable','url:http,https'])->maxLength(1000)];
        foreach(['ar','en'] as $locale) {
            $fields[]=TextInput::make('title_'.$locale)->label(__('events.post_title').' ('.__('events.'.$locale).')')->required($locale==='ar')->maxLength(255);
            $fields[]=Textarea::make('summary_'.$locale)->label(__('events.summary').' ('.__('events.'.$locale).')')->required($locale==='ar')->maxLength(2000);
            $fields[]=Textarea::make('description_'.$locale)->label(__('events.description').' ('.__('events.'.$locale).')')->required($locale==='ar')->rows(6)->maxLength(20000)->columnSpanFull();
            $fields[]=TextInput::make('venue_'.$locale)->label(__('events.venue').' ('.__('events.'.$locale).')')->maxLength(255);
            $fields[]=TextInput::make('speaker_'.$locale)->label(__('events.speaker').' ('.__('events.'.$locale).')')->maxLength(255);
        }
        $fields[]=Repeater::make('agenda')->label(__('events.agenda'))->helperText(__('events.agenda_hint'))->schema([
            TextInput::make('time')->label(__('events.time'))->required()->maxLength(60),
            TextInput::make('title_ar')->label(__('events.topic').' (العربية)')->required()->maxLength(255),
            TextInput::make('title_en')->label(__('events.topic').' (English)')->maxLength(255),
        ])->defaultItems(0)->maxItems(30)->columnSpanFull();
        return $schema->components($fields);
    }
    public static function table(Table $table): Table
    {
        return $table->defaultSort('starts_at')->columns([
            TextColumn::make(app()->getLocale()==='en'?'title_en':'title_ar')->label(__('events.post_title'))->searchable()->limit(45),
            TextColumn::make('event_type')->label(__('events.type'))->formatStateUsing(fn($state)=>Event::types()[$state]??$state),
            TextColumn::make('status')->label(__('events.status'))->badge()->formatStateUsing(fn($state)=>Event::statuses()[$state]??$state),
            TextColumn::make('starts_at')->label(__('events.start'))->dateTime()->timezone('Asia/Muscat')->sortable(),
            TextColumn::make('ends_at')->label(__('events.end'))->dateTime()->timezone('Asia/Muscat'),
        ])->filters([SelectFilter::make('status')->label(__('events.status'))->options(Event::statuses()),SelectFilter::make('event_type')->label(__('events.type'))->options(Event::types())])
        ->recordActions([EditAction::make(),Action::make('public')->label(__('events.public_link'))->url(fn($record)=>route('events.show',$record))->openUrlInNewTab()->visible(fn($record)=>$record->status!=='draft'),DeleteAction::make()]);
    }
    public static function getPages(): array { return ['index'=>Pages\ManageEvents::route('/')]; }
}
