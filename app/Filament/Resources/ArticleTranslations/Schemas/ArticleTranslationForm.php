<?php

namespace App\Filament\Resources\ArticleTranslations\Schemas;

use App\Models\Article;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleTranslationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('article_id')
                    ->relationship('article', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Article $record): string => $record->translations
                        ->firstWhere('locale', app()->getLocale())?->title
                        ?? $record->translations->first()?->title
                        ?? $record->slug)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('locale')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('summary')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('seo_title')
                    ->default(null),
                TextInput::make('seo_description')
                    ->default(null),
                Textarea::make('open_graph')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('sources')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
