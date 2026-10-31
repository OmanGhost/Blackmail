<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Services\ApplicationUpdates;
class Updates extends Page
{
    protected string $view='filament.updates';
    #[\Livewire\Attributes\Locked]
    public ?array $result=null;
    public static function canAccess(): bool { $user=\Illuminate\Support\Facades\Auth::guard('admin')->user();return $user?->is_active && $user->hasRole('super-admin'); }
    public static function getNavigationLabel(): string { return __('updates.title'); }
    public static function getNavigationGroup(): ?string { return __('admin.system'); }
    public function getTitle(): string { return __('updates.title'); }
    public function mount(): void { abort_unless(static::canAccess(),403);$this->result=app(ApplicationUpdates::class)->check(); }
    protected function getHeaderActions(): array
    {
        return [Action::make('check')->label(__('updates.check'))->action(function(): void {
            abort_unless(static::canAccess(),403);$this->result=app(ApplicationUpdates::class)->check(true);
        })];
    }
}
