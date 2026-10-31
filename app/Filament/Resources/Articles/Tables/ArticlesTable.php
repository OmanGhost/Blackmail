<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at','desc')
            ->columns([
                TextColumn::make('translations.title')->label(__('blog.post_title'))->searchable()->limit(50),
                TextColumn::make('post_type')->label(__('blog.type'))->badge()->formatStateUsing(fn($state)=>\App\Models\Article::typeOptions()[$state]??$state),
                TextColumn::make(app()->getLocale() === 'en' ? 'category.name_en' : 'category.name_ar')
                    ->label(__('admin.fields.category'))
                    ->searchable(),
                TextColumn::make('author.name')
                    ->searchable(),
                TextColumn::make('reviewer.name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                ImageColumn::make('featured_image'),
                TextColumn::make('status')
                    ->badge()->formatStateUsing(fn($state)=>__('blog.'.(['published'=>'published_status'][$state]??$state))),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_reviewed_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('preview')->label(__('blog.read'))->url(fn($record)=>route('articles.show',$record))->openUrlInNewTab()->visible(fn($record)=>$record->status==='published' && $record->published_at?->isPast()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
