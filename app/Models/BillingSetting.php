<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    protected $guarded = [];
    protected $casts = ['tax_rate' => 'decimal:2', 'due_days' => 'integer'];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }
}
