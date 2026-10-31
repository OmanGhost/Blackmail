<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('post_type')->label(__('blog.type'))->options(\App\Models\Article::typeOptions())->default('article')->required()->live()->helperText(__('blog.publishing')),
                TextInput::make('video_url')->label(__('blog.video_url'))->url()->rules(['nullable','url:http,https'])->maxLength(1000)->visible(fn ($get)=>$get('post_type')==='video')->required(fn ($get)=>$get('post_type')==='video')->helperText(__('blog.video_hint')),
                Select::make('category_id')
                    ->relationship('category', app()->getLocale() === 'en' ? 'name_en' : 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('author_id')
                    ->default(fn()=>auth()->id())
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
                    ->required()->maxLength(255)->regex('/^[a-zA-Z0-9_-]+$/')->unique(ignoreRecord:true),
                FileUpload::make('featured_image')
                    ->image()->disk('public')->directory('blog')->visibility('public')->maxSize(5120),
                Select::make('status')
                    ->options(['draft' => __('blog.draft'), 'review' => __('blog.review'), 'published' => __('blog.published_status'), 'archived' => __('blog.archived')])
                    ->default('draft')
                    ->required(),
                DateTimePicker::make('published_at')->timezone('Asia/Muscat')->default(now())->helperText(__('blog.timezone_hint'))->required(fn ($get)=>$get('status')==='published'),
                DatePicker::make('last_reviewed_at'),
                Repeater::make('translations')->label(__('blog.content'))->relationship()->schema([
                    Select::make('locale')->label(__('blog.locale'))->options(['ar'=>'العربية','en'=>'English'])->default('ar')->required()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    TextInput::make('title')->label(__('blog.post_title'))->required()->maxLength(255),
                    Textarea::make('summary')->label(__('blog.summary'))->required()->maxLength(2000),
                    RichEditor::make('content')->label(__('blog.body'))->required()->fileAttachments(false),
                ])->minItems(1)->defaultItems(1)->addActionLabel(__('blog.add_translation'))->columnSpanFull(),
            ]);
    }
}
