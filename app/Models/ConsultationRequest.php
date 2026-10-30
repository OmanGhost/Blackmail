<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class ConsultationRequest extends Model
{
    protected $guarded = [];

    protected $hidden = ['contact_details_encrypted', 'description_encrypted', 'tracking_hash', 'technical_metadata'];

    protected $casts = ['consented_at' => 'datetime', 'retain_until' => 'datetime', 'safe_contact_time' => 'encrypted', 'technical_metadata' => 'encrypted:array', 'closed_at' => 'datetime', 'rated_at' => 'datetime', 'rating' => 'integer', 'rating_comment' => 'encrypted'];

    public static function statusOptions(): array
    {
        return ['new' => __('account.new'), 'in_review' => __('account.in_review'), 'in_progress' => __('account.in_progress'), 'contacted' => __('account.contacted'), 'completed' => __('account.completed'), 'closed' => __('account.closed')];
    }

    public function isEnded(): bool
    {
        return in_array($this->status, ['completed', 'closed']);
    }

    protected static function booted(): void
    {
        static::updating(function (self $case): void {
            if ($case->isDirty('status')) {
                $case->closed_at = $case->isEnded() ? now() : null;
                $case->closed_by_role = $case->isEnded() ? ($case->isDirty('closed_by_role') ? $case->closed_by_role : 'staff') : null;
            }
        });
        static::updated(function (self $case): void {
            if ($case->wasChanged('status')) {
                $case->statusHistory()->create(['from_status' => $case->getRawOriginal('status'), 'to_status' => $case->status, 'changed_by' => auth()->id()]);
            }
        });
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ConsultationStatusHistory::class)->latest('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function getRequestNumberAttribute(): string
    {
        return (string) (100000 + $this->id);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class)->orderBy('id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getContactDetailsAttribute(): string
    {
        try {
            return Crypt::decryptString($this->contact_details_encrypted);
        } catch (Throwable) {
            return '[تعذر فك التشفير]';
        }
    }

    public function getDescriptionAttribute(): string
    {
        try {
            return Crypt::decryptString($this->description_encrypted);
        } catch (Throwable) {
            return '[تعذر فك التشفير]';
        }
    }
}
