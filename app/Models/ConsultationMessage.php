<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationMessage extends Model
{
    protected $guarded = [];
    protected $casts = ['body' => 'encrypted'];
    protected static function booted(): void
    {
        static::created(function (self $message): void {
            if ($message->sender === 'staff') RequestUpdate::create(['consultation_request_id'=>$message->consultation_request_id,'kind'=>'message']);
        });
    }
}
