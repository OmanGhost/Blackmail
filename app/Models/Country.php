<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['is_published' => 'boolean', 'last_verified_at' => 'date'];

    public function reportingAuthorities(): HasMany
    {
        return $this->hasMany(ReportingAuthority::class);
    }

    public function legalReferences(): HasMany
    {
        return $this->hasMany(LegalReference::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified')->where('is_published', true);
    }
}
