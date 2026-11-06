<?php

namespace App\Filament\Resources\Invoices;

use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Services\Billing;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    public const ROLES = ['super-admin', 'support-agent', 'security-consultant'];

    protected static ?string $model = Invoice::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'number';

    public static function canAccess(): bool
    {
        return auth()->user()?->roles()->whereIn('name', self::ROLES)->exists() ?? false;
    }

    // Issued invoices are never edited or deleted; they are marked paid or cancelled instead.
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function getNavigationGroup(): ?string { return __('billing.billing'); }
    public static function getNavigationLabel(): string { return __('billing.invoices'); }
    public static function getModelLabel(): string { return __('billing.invoice'); }
    public static function getPluralModelLabel(): string { return __('billing.invoices'); }

    public static function getNavigationBadge(): ?string
    {
        $count = Invoice::where('status', 'unpaid')->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('number')->label(__('billing.invoice_number')),
                TextEntry::make('status')->label(__('billing.status_label'))->badge()->formatStateUsing(fn ($state) => __('billing.status.'.$state))
                    ->color(fn ($state) => ['paid' => 'success', 'unpaid' => 'warning', 'cancelled' => 'gray'][$state] ?? 'gray'),
                TextEntry::make('total_minor')->label(__('billing.total'))->formatStateUsing(fn ($state, Invoice $record) => $record->money($record->total_minor)),
                TextEntry::make('customer_name')->label(__('billing.customer')),
                TextEntry::make('customer_email')->label(__('billing.issuer_email')),
                TextEntry::make('consultationRequest.request_number')->label(__('billing.request'))->placeholder('—'),
                TextEntry::make('issued_at')->label(__('billing.issued_at'))->dateTime()->timezone('Asia/Muscat'),
                TextEntry::make('due_at')->label(__('billing.due_at'))->date()->placeholder('—'),
                TextEntry::make('paid_at')->label(__('billing.paid_at'))->dateTime()->timezone('Asia/Muscat')->placeholder('—'),
                TextEntry::make('payment_method')->label(__('billing.payment_method'))->formatStateUsing(fn ($state) => __('billing.method.'.$state))->placeholder('—'),
                TextEntry::make('payment_reference')->label(__('billing.payment_reference'))->placeholder('—'),
                TextEntry::make('issuedBy.name')->label(__('billing.issue_invoice'))->placeholder('—'),
            ]),
            RepeatableEntry::make('items')->label(__('billing.items'))->columns(4)->schema([
                TextEntry::make('description')->label(__('billing.item'))->columnSpan(2),
                TextEntry::make('quantity')->label(__('billing.quantity')),
                TextEntry::make('total_minor')->label(__('billing.line_total'))->formatStateUsing(fn ($state, $record) => $record->invoice?->money($state) ?? $state),
            ]),
            TextEntry::make('notes')->label(__('billing.notes'))->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('number')->label(__('billing.invoice_number'))->searchable(),
            TextColumn::make('customer_name')->label(__('billing.customer'))->searchable(['customer_name', 'customer_email'])->description(fn (Invoice $record) => $record->customer_email),
            TextColumn::make('total_minor')->label(__('billing.total'))->formatStateUsing(fn ($state, Invoice $record) => $record->money($record->total_minor))->sortable(),
            TextColumn::make('status')->label(__('billing.status_label'))->badge()
                ->formatStateUsing(fn ($state, Invoice $record) => __('billing.status.'.$state).($record->isOverdue() ? ' · '.__('billing.overdue') : ''))
                ->color(fn ($state, Invoice $record) => $record->isOverdue() ? 'danger' : (['paid' => 'success', 'unpaid' => 'warning'][$state] ?? 'gray')),
            TextColumn::make('issued_at')->label(__('billing.issued_at'))->date()->timezone('Asia/Muscat')->sortable(),
            TextColumn::make('due_at')->label(__('billing.due_at'))->date()->sortable()->toggleable(),
        ])->filters([SelectFilter::make('status')->label(__('billing.status_label'))->options(Invoice::statusOptions())])
            ->recordActions([ViewAction::make(), static::markPaidAction(), static::cancelAction()]);
    }

    public static function markPaidAction(): Action
    {
        return Action::make('mark_paid')->label(__('billing.mark_paid'))->icon('heroicon-o-banknotes')->color('success')
            ->visible(fn (Invoice $record) => $record->status === 'unpaid')
            ->schema([
                Select::make('payment_method')->label(__('billing.payment_method'))->options(Invoice::paymentMethodOptions())->default('bank_transfer')->required(),
                TextInput::make('payment_reference')->label(__('billing.payment_reference'))->maxLength(255),
            ])
            ->action(function (Invoice $record, array $data): void {
                abort_unless(static::canAccess(), 403);
                app(Billing::class)->markPaid($record, $data['payment_method'], $data['payment_reference'] ?? null, auth()->id());
                Notification::make()->title(__('billing.marked_paid'))->success()->send();
            });
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel')->label(__('billing.cancel'))->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Invoice $record) => $record->status === 'unpaid')
            ->requiresConfirmation()->modalDescription(__('billing.cancel_confirm'))
            ->action(function (Invoice $record): void {
                abort_unless(static::canAccess(), 403);
                app(Billing::class)->cancel($record, auth()->id());
                Notification::make()->title(__('billing.cancelled'))->success()->send();
            });
    }

    /** Requests of the chosen member, for linking a manual invoice. */
    public static function requestOptions(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return ConsultationRequest::where('user_id', $userId)->latest('id')->limit(50)->get()
            ->mapWithKeys(fn ($case) => [$case->id => $case->request_number.' · '.$case->created_at->format('Y-m-d')])->all();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInvoices::route('/')];
    }
}
