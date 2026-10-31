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
                TextColumn::make('visibility')->label(__('blog.visibility'))->state(fn($record)=>
                    $record->status!=='published' ? __('blog.'.$record->status) :
                    (!$record->translations()->exists() ? __('blog.missing_content') :
                    (!$record->published_at ? __('blog.missing_date') :
                    ($record->published_at->isFuture() ? __('blog.scheduled') : __('blog.visible')))))->badge(),
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
                    ->timezone('Asia/Muscat')
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
                \Filament\Actions\Action::make('publish_now')->label(__('blog.publish_now'))->requiresConfirmation()
                    ->visible(fn($record)=>$record->status==='published' && $record->translations()->exists() && (!$record->published_at || $record->published_at->isFuture()))
                    ->action(fn($record)=>$record->update(['published_at'=>now()])),
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
