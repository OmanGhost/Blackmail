<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $guarded = [];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'encrypted', 'enabled' => 'boolean'];
}
