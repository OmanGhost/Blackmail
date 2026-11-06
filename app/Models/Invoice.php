<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUSES = ['unpaid', 'paid', 'cancelled'];
    public const PAYMENT_METHODS = ['bank_transfer', 'cash', 'card', 'other'];

    protected $guarded = [];
    protected $casts = [
        'issuer' => 'array', 'tax_rate' => 'decimal:2', 'due_at' => 'date', 'issued_at' => 'datetime', 'paid_at' => 'datetime', 'cancelled_at' => 'datetime',
        'subtotal_minor' => 'integer', 'tax_minor' => 'integer', 'total_minor' => 'integer',
    ];

    public static function statusOptions(): array
    {
        return collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => __('billing.status.'.$s)])->all();
    }

    public static function paymentMethodOptions(): array
    {
        return collect(self::PAYMENT_METHODS)->mapWithKeys(fn ($m) => [$m => __('billing.method.'.$m)])->all();
    }

    public function items(): HasMany { return $this->hasMany(InvoiceItem::class)->orderBy('id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function consultationRequest(): BelongsTo { return $this->belongsTo(ConsultationRequest::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function issuedBy(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid' && $this->due_at && $this->due_at->endOfDay()->isPast();
    }

    public function money(int $minor): string
    {
        return Money::format($minor, $this->currency);
    }
}
