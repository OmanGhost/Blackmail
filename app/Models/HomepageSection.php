<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $guarded = [];

    protected $casts = ['extra' => 'array', 'is_enabled' => 'boolean'];
}
