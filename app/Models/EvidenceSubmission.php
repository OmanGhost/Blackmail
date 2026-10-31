<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvidenceSubmission extends Model
{
    protected $guarded = [];
    protected $hidden = ['body','file_path','original_name','ciphertext_sha256'];
    protected $casts = ['body'=>'encrypted','original_name'=>'encrypted','consented_at'=>'datetime','withdrawn_at'=>'datetime'];
    public function evidenceRequest(): BelongsTo { return $this->belongsTo(EvidenceRequest::class); }
    public function isAvailable(): bool
    {
        return ! $this->withdrawn_at && $this->created_at->gt(now()->subDays(30)) && (! $this->evidenceRequest->consultationRequest->retain_until || $this->evidenceRequest->consultationRequest->retain_until->isFuture());
    }
    protected static function booted(): void
    {
        static::deleted(function (self $submission): void {
            $path=$submission->file_path;
            if ($path) DB::afterCommit(fn()=>Storage::disk('secure_evidence')->delete($path));
        });
    }
}
