<?php

namespace App\Filament\Resources\LegalReferences\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LegalReferenceForm
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
                TextInput::make('title_ar')
                    ->required(),
                TextInput::make('title_en')
                    ->default(null),
                TextInput::make('official_url')
                    ->url()
                    ->default(null),
                Textarea::make('summary_ar')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('approval_status')
                    ->options(['draft' => 'Draft', 'review' => 'Review', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('draft')
                    ->required(),
                Select::make('approved_by')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) && (int) $state > 0 ? (int) $state : null)
                    ->default(null),
                DatePicker::make('last_verified_at'),
            ]);
    }
}
