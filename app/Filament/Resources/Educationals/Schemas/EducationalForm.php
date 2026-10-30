<?php

namespace App\Filament\Resources\Educationals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EducationalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        'guide' => __('admin.educational_types.guide'),
                        'tool' => __('admin.educational_types.tool'),
                        'checklist' => __('admin.educational_types.checklist'),
                        'video' => __('admin.educational_types.video'),
                    ])
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('title_ar')
                    ->required(),
                TextInput::make('title_en'),
                Textarea::make('content_ar')
                    ->required()
                    ->rows(12)
                    ->columnSpanFull(),
                Textarea::make('content_en')
                    ->rows(12)
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->required()
                    ->default(false),
            ]);
    }
}
