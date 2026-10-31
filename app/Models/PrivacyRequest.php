<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['reviewed_at'=>'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function consultationRequest(): BelongsTo { return $this->belongsTo(ConsultationRequest::class); }
}
