<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name_ar')
                    ->required(),
                TextInput::make('name_en')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('flag'),
                Textarea::make('emergency_information_ar')
                    ->columnSpanFull(),
                Textarea::make('emergency_information_en')
                    ->columnSpanFull(),
                Textarea::make('reporting_instructions_ar')
                    ->columnSpanFull(),
                Textarea::make('reporting_instructions_en')
                    ->columnSpanFull(),
                Select::make('verification_status')
                    ->label(__('admin.fields.verification_status'))
                    ->options([
                        'unverified' => __('admin.statuses.unverified'),
                        'in_review' => __('admin.statuses.in_review'),
                        'verified' => __('admin.statuses.verified'),
                        'expired' => __('admin.statuses.expired'),
                    ])
                    ->required()
                    ->default('unverified'),
                DatePicker::make('last_verified_at')->disabled()->dehydrated(false),
                Select::make('verified_by')
                    ->disabled()->dehydrated(false)
                    ->label(__('admin.fields.verified_by'))
                    ->relationship('verifier', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) && (int) $state > 0 ? (int) $state : null),
                Toggle::make('is_published')
                    ->required()->helperText(app()->getLocale()==='en'?'Verification is recorded by the server. Editing verified contact data requires a new review.':'يسجل النظام المراجع وتاريخ التحقق تلقائيًا. تعديل بيانات متحققة يعيدها للمراجعة ويوقف نشرها.'),
            ]);
    }
}
