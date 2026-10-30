<?php

namespace App\Filament\Resources\Educationals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EducationalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_en')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => __('admin.educational_types.'.$state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'guide' => __('admin.educational_types.guide'),
                        'tool' => __('admin.educational_types.tool'),
                        'checklist' => __('admin.educational_types.checklist'),
                        'video' => __('admin.educational_types.video'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
