<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationStatusHistory extends Model
{
    protected $table = 'consultation_status_history';
    protected $guarded = [];
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
