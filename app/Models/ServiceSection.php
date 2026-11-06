<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceSection extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order')->orderBy('id');
    }

    public function name(): string
    {
        return (app()->getLocale() === 'en' ? $this->name_en : null) ?: $this->name_ar;
    }

    public function description(): ?string
    {
        return (app()->getLocale() === 'en' ? $this->description_en : null) ?: $this->description_ar;
    }
}
