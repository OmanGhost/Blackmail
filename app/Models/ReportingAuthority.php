<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingAuthority extends Model
{
    protected $guarded = [];

    protected $casts = ['is_published' => 'boolean', 'last_verified_at' => 'date'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
