<?php

namespace App\Filament\Resources\HomepageSections\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomepageSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('المعرّف التقني')->required()->disabledOn('edit'),
                TextInput::make('name_ar')
                    ->label('اسم القسم')->required(),
                TextInput::make('heading_ar')
                    ->label('العنوان العربي')
                    ->default(null),
                TextInput::make('heading_en')
                    ->label('العنوان الإنجليزي')
                    ->default(null),
                Textarea::make('description_ar')
                    ->label('الوصف العربي')->rows(4)
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->label('الوصف الإنجليزي')->rows(4)
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('primary_button_text_ar')
                    ->label('نص الزر الرئيسي')
                    ->default(null),
                TextInput::make('primary_button_url')
                    ->label('رابط الزر الرئيسي')
                    ->default(null),
                TextInput::make('secondary_button_text_ar')
                    ->label('نص الزر الثانوي')
                    ->default(null),
                TextInput::make('secondary_button_url')
                    ->label('رابط الزر الثانوي')
                    ->default(null),
                TextInput::make('eyebrow_ar')
                    ->label('النص الصغير أعلى العنوان')
                    ->default(null),
                KeyValue::make('extra')->label('خيارات إضافية')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_enabled')
                    ->label('إظهار القسم')->required(),
                TextInput::make('sort_order')
                    ->label('الترتيب')->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
