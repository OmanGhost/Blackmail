<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\Billing;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('billing.issue_invoice'))->modalHeading(__('billing.issue_invoice'))->createAnother(false)
                ->schema([
                    Select::make('user_id')->label(__('billing.customer'))->required()->searchable()->live()
                        ->getSearchResultsUsing(fn (string $search) => User::where('is_active', true)
                            ->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                            ->limit(20)->get()->mapWithKeys(fn ($user) => [$user->id => $user->name.' — '.$user->email])->all())
                        ->getOptionLabelUsing(fn ($value) => ($user = User::find($value)) ? $user->name.' — '.$user->email : null),
                    Select::make('consultation_request_id')->label(__('billing.request'))
                        ->options(fn (Get $get) => InvoiceResource::requestOptions($get('user_id'))),
                    Repeater::make('items')->label(__('billing.items'))->addActionLabel(__('billing.add_item'))->required()->minItems(1)->maxItems(30)->defaultItems(1)->columns(4)->schema([
                        TextInput::make('description')->label(__('billing.item'))->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('quantity')->label(__('billing.quantity'))->integer()->minValue(1)->maxValue(1000)->default(1)->required(),
                        TextInput::make('unit_price')->label(__('billing.unit_price').' (OMR)')->required()->regex('/^\d{1,9}(\.\d{1,3})?$/'),
                    ])->columnSpanFull(),
                    DatePicker::make('due_at')->label(__('billing.due_at'))->minDate(today()),
                    Textarea::make('notes')->label(__('billing.notes'))->maxLength(2000)->rows(3)->columnSpanFull(),
                ])
                ->using(function (array $data) {
                    $user = User::findOrFail($data['user_id']);
                    $case = filled($data['consultation_request_id'] ?? null) ? ConsultationRequest::where('user_id', $user->id)->findOrFail($data['consultation_request_id']) : null;
                    $items = collect($data['items'])->map(fn ($item) => [
                        'description' => $item['description'], 'quantity' => (int) $item['quantity'], 'unit_price_minor' => Money::toMinor($item['unit_price']),
                    ])->all();

                    return app(Billing::class)->issue($user, $items, $case, null, $data['notes'] ?? null, auth()->id(), $data['due_at'] ?? null);
                })
                ->successNotificationTitle(fn ($record) => __('billing.issued', ['number' => $record->number])),
        ];
    }
}
