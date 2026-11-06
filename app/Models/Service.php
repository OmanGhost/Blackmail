<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'price_minor' => 'integer'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ServiceSection::class, 'service_section_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->whereHas('section', fn ($q) => $q->where('is_active', true));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function name(): string
    {
        return (app()->getLocale() === 'en' ? $this->name_en : null) ?: $this->name_ar;
    }

    public function summary(): string
    {
        return (app()->getLocale() === 'en' ? $this->summary_en : null) ?: $this->summary_ar;
    }

    public function details(): ?string
    {
        return (app()->getLocale() === 'en' ? $this->details_en : null) ?: $this->details_ar;
    }

    public function duration(): ?string
    {
        return (app()->getLocale() === 'en' ? $this->duration_en : null) ?: $this->duration_ar;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_minor, $this->currency);
    }
}
