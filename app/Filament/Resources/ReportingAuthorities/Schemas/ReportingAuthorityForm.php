<?php

namespace App\Filament\Resources\ReportingAuthorities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReportingAuthorityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_id')
                    ->relationship('country', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name_ar')
                    ->required(),
                TextInput::make('name_en')
                    ->default(null),
                TextInput::make('official_url')
                    ->url()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                Textarea::make('instructions_ar')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('verification_status')
                    ->options([
                        'unverified' => 'Unverified',
                        'in_review' => 'In review',
                        'verified' => 'Verified',
                        'expired' => 'Expired',
                    ])
                    ->default('unverified')
                    ->required(),
                DatePicker::make('last_verified_at'),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
