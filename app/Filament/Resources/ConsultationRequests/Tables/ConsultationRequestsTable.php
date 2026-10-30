<?php

namespace App\Filament\Resources\ConsultationRequests\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConsultationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('request_number')
                    ->label('رقم الطلب')
                    ->copyable()
                    ->sortable(['id'])
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('consultation_requests.id', ctype_digit($search) ? (int) $search - 100000 : -1);
                    }),
                TextColumn::make('category')
                    ->searchable(),
                TextColumn::make('urgency')
                    ->formatStateUsing(fn (string $state): string => ['normal' => 'عادي', 'urgent' => 'عاجل', 'critical' => 'حرج'][$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) { 'critical' => 'danger', 'urgent' => 'warning', default => 'gray' })
                    ->sortable(),
                TextColumn::make(app()->getLocale() === 'en' ? 'country.name_en' : 'country.name_ar')
                    ->label(__('admin.fields.country'))
                    ->sortable(),
                TextColumn::make('preferred_contact_method')
                    ->searchable(),
                TextColumn::make('consented_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => \App\Models\ConsultationRequest::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->searchable(),
                TextColumn::make('rating')->label('التقييم / 5')->placeholder('لم يُقيّم')->sortable(),
                TextColumn::make('assignedTo.name')
                    ->sortable(),
                TextColumn::make('retain_until')
                    ->dateTime()
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
                SelectFilter::make('urgency')->label('الأولوية')->options(['normal' => 'عادي', 'urgent' => 'عاجل', 'critical' => 'حرج']),
                SelectFilter::make('status')->label('الحالة')->options(\App\Models\ConsultationRequest::statusOptions()),
                SelectFilter::make('assigned_to')->label('المسؤول')->relationship('assignedTo', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
