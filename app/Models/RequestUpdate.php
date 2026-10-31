<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestUpdate extends Model
{
    protected $guarded = [];
    protected $casts = ['read_at'=>'datetime'];
    public function consultationRequest(): BelongsTo { return $this->belongsTo(ConsultationRequest::class); }
    public function getLabelAttribute(): string
    {
        return __('workflow.update_'.$this->kind);
    }
}
