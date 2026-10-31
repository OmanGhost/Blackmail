<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNote extends Model
{
    protected $guarded = [];
    protected $hidden = ['body'];
    protected $casts = ['body'=>'encrypted'];
    public function author(): BelongsTo { return $this->belongsTo(User::class,'user_id'); }
}
