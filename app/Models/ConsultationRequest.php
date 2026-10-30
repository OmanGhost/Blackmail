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

    protected $casts = ['consented_at' => 'datetime', 'retain_until' => 'datetime', 'safe_contact_time' => 'encrypted', 'technical_metadata' => 'encrypted:array'];

    public function getStatusLabelAttribute(): string
    {
        return ['new' => 'جديد', 'in_review' => 'قيد المراجعة', 'contacted' => 'تم التواصل', 'closed' => 'مغلق'][$this->status] ?? $this->status;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
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
