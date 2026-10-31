<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingAuthority extends Model
{
    protected $guarded = [];

    protected $casts = ['is_published' => 'boolean', 'last_verified_at' => 'date'];

    protected static function booted(): void
    {
        static::saved(fn () => \App\Services\VisitorHotline::flush());
        static::deleted(fn () => \App\Services\VisitorHotline::flush());
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
