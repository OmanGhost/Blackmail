<?php

namespace App\Filament\Resources\ConsultationRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConsultationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category')
                    ->disabled()->dehydrated(false),
                Select::make('urgency')->options(['normal' => 'عادي', 'urgent' => 'عاجل', 'critical' => 'حرج'])->disabled()->dehydrated(false),
                TextInput::make('incident_stage')->disabled()->dehydrated(false),
                Select::make('country_id')
                    ->relationship('country', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('preferred_contact_method')
                    ->disabled()->dehydrated(false),
                TextInput::make('safe_contact_time')->disabled()->dehydrated(false),
                Textarea::make('contact_details')->label('بيانات التواصل (مشفرة في التخزين)')->disabled()->dehydrated(false)->columnSpanFull(),
                Textarea::make('description')->label('وصف الحالة (مشفّر في التخزين)')->disabled()->dehydrated(false)->rows(8)->columnSpanFull(),
                DateTimePicker::make('consented_at')
                    ->disabled()->dehydrated(false),
                Select::make('status')->options(\App\Models\ConsultationRequest::statusOptions())->required(),
                Select::make('assigned_to')->relationship('assignedTo', 'name')->searchable()->preload()->nullable()->default(null),
                DateTimePicker::make('retain_until'),
            ]);
    }
}
