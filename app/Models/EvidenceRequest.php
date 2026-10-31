<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvidenceRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['instructions'=>'encrypted','expires_at'=>'datetime'];
    public function consultationRequest(): BelongsTo { return $this->belongsTo(ConsultationRequest::class); }
    public function submissions(): HasMany { return $this->hasMany(EvidenceSubmission::class)->latest('id'); }
    public function isOpen(): bool { return $this->status==='open' && $this->expires_at->isFuture() && !$this->consultationRequest->isEnded(); }
}
