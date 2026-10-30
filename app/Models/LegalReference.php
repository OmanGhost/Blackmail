<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalReference extends Model
{
    protected $guarded = [];

    protected $casts = ['last_verified_at' => 'date'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
