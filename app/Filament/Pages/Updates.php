<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Services\{ApplicationUpdates,ApplicationUpdater,UpdateFailed};
class Updates extends Page
{
    protected string $view='filament.updates';
    #[\Livewire\Attributes\Locked]
    public ?array $result=null;
    public static function canAccess(): bool { $user=\Illuminate\Support\Facades\Auth::guard('admin')->user();return $user?->is_active && $user->hasRole('super-admin'); }
    public static function getNavigationLabel(): string { return __('updates.title'); }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    public static function getNavigationGroup(): ?string { return __('admin.system'); }
    public function getTitle(): string { return __('updates.title'); }
    public function mount(): void
    {
        abort_unless(static::canAccess(),403);
        // An update copied in the previous request finishes here, in a fresh PHP process running the new code.
        try {
            if ($done=app(ApplicationUpdater::class)->finalize()) Notification::make()->title(__('updates.installed',['version'=>$done['to']]))->success()->persistent()->send();
        } catch (UpdateFailed $e) {
            report($e);
            Notification::make()->title($e->userMessage())->danger()->persistent()->send();
        }
        $this->result=app(ApplicationUpdates::class)->check(true);
    }
    public function canInstall(): bool
    {
        return static::canAccess() && app(ApplicationUpdates::class)->displayStatus($this->result)==='available' && ($this->result['package']??false) && !app(ApplicationUpdater::class)->pending();
    }
    protected function getHeaderActions(): array
    {
        $password=fn()=>TextInput::make('password')->label(__('updates.password'))->password()->required()->rule('current_password:admin');
        return [
            Action::make('install')->label(fn()=>__('updates.install',['version'=>$this->result['latest']??'']))->icon('heroicon-o-arrow-down-tray')->color('success')
                ->visible(fn()=>$this->canInstall())
                ->requiresConfirmation()->modalHeading(__('updates.install_heading'))->modalDescription(__('updates.install_warning'))
                ->schema([$password()])
                ->action(function(): void {
                    abort_unless($this->canInstall(),403);
                    try {
                        app(ApplicationUpdater::class)->install(auth('admin')->id());
                    } catch (UpdateFailed $e) {
                        report($e);
                        Notification::make()->title($e->userMessage())->danger()->persistent()->send();
                        return;
                    }
                    // Load the page again so migrations run with the new code.
                    $this->redirect(static::getUrl());
                }),
            Action::make('rollback')->label(__('updates.rollback'))->icon('heroicon-o-arrow-uturn-left')->color('gray')
                ->visible(fn()=>static::canAccess() && app(ApplicationUpdater::class)->backups()!==[])
                ->requiresConfirmation()->modalHeading(__('updates.rollback'))->modalDescription(__('updates.rollback_warning'))
                ->schema([
                    Select::make('backup')->label(__('updates.backup'))->required()
                        ->options(fn()=>collect(app(ApplicationUpdater::class)->backups())->mapWithKeys(fn($b)=>[$b['id']=>__('updates.backup_option',['from'=>$b['from'],'to'=>$b['to'],'date'=>\Carbon\Carbon::parse($b['created_at'])->timezone('Asia/Muscat')->format('Y-m-d H:i')])])),
                    $password(),
                ])
                ->action(function(array $data): void {
                    abort_unless(static::canAccess(),403);
                    try {
                        $backup=app(ApplicationUpdater::class)->rollback($data['backup'],auth('admin')->id());
                    } catch (UpdateFailed $e) {
                        report($e);
                        Notification::make()->title($e->userMessage())->danger()->persistent()->send();
                        return;
                    }
                    Notification::make()->title(__('updates.rolled_back',['version'=>$backup['from']]))->success()->persistent()->send();
                    $this->redirect(static::getUrl());
                }),
            Action::make('check')->label(__('updates.check'))->action(function(): void {
                abort_unless(static::canAccess(),403);$this->result=app(ApplicationUpdates::class)->check(true);
            }),
        ];
    }
}
