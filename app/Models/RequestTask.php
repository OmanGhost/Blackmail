<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestTask extends Model
{
    protected $guarded = [];
    protected $casts = ['title'=>'encrypted', 'completed_at'=>'datetime'];
    protected static function booted(): void
    {
        static::created(fn (self $task) => RequestUpdate::create(['consultation_request_id'=>$task->consultation_request_id,'kind'=>'task']));
    }
}
