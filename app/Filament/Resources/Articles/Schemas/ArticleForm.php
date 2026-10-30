<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('reviewer_id')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->default(null),
                TextInput::make('slug')
                    ->required(),
                FileUpload::make('featured_image')
                    ->image(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'review' => 'Review', 'published' => 'Published', 'archived' => 'Archived'])
                    ->default('draft')
                    ->required(),
                DateTimePicker::make('published_at'),
                DatePicker::make('last_reviewed_at'),
            ]);
    }
}
