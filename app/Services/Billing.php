<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BillingSetting;
use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Billing
{
    public function __construct(private PlatformMail $mail) {}

    /**
     * Issue an invoice. Totals, the issuer details and the tax rate are fixed at issue time.
     *
     * @param  array<int, array{description: string, quantity: int, unit_price_minor: int}>  $items
     */
    public function issue(User $user, array $items, ?ConsultationRequest $case = null, ?Service $service = null, ?string $notes = null, ?int $issuedBy = null, ?string $dueAt = null): Invoice
    {
        $items = array_values(array_filter($items, fn ($item) => filled($item['description'] ?? null)));
        if ($items === []) {
            throw new InvalidArgumentException('An invoice needs at least one item.');
        }
        if ($case && $case->user_id !== $user->id) {
            throw new InvalidArgumentException('The request belongs to another user.');
        }
        $settings = BillingSetting::current();

        $invoice = DB::transaction(function () use ($user, $items, $case, $service, $notes, $issuedBy, $dueAt, $settings) {
            $lines = [];
            $subtotal = 0;
            foreach ($items as $item) {
                $quantity = max(1, (int) $item['quantity']);
                $unit = max(0, (int) $item['unit_price_minor']);
                $lines[] = ['description' => mb_substr(trim($item['description']), 0, 255), 'quantity' => $quantity, 'unit_price_minor' => $unit, 'total_minor' => $quantity * $unit];
                $subtotal += $quantity * $unit;
            }
            $rate = (float) $settings->tax_rate;
            $tax = (int) round($subtotal * $rate / 100);
            $invoice = Invoice::create([
                'user_id' => $user->id, 'consultation_request_id' => $case?->id, 'service_id' => $service?->id ?? $case?->service_id,
                'status' => 'unpaid', 'currency' => $service?->currency ?? 'OMR',
                'subtotal_minor' => $subtotal, 'tax_rate' => $rate, 'tax_minor' => $tax, 'total_minor' => $subtotal + $tax,
                'issuer' => $settings->only(['issuer_name', 'issuer_address', 'issuer_email', 'issuer_phone', 'tax_number', 'payment_instructions']),
                'customer_name' => $user->name, 'customer_email' => $user->email, 'notes' => $notes,
                'due_at' => $dueAt ?? now('Asia/Muscat')->addDays($settings->due_days)->toDateString(),
                'issued_at' => now(), 'issued_by' => $issuedBy,
            ]);
            $invoice->update(['number' => 'INV-'.now('Asia/Muscat')->format('Y').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT)]);
            $invoice->items()->createMany($lines);
            $this->audit('invoice.issued', $invoice, $issuedBy);

            return $invoice;
        });

        $this->mail->send($user, 'invoice_issued', $invoice->number);

        return $invoice;
    }

    /** Issue the invoice for a service request, priced from its service. */
    public function issueForRequest(ConsultationRequest $case, ?int $issuedBy = null): Invoice
    {
        if (! $case->user || ! $case->service) {
            throw new InvalidArgumentException('Only member service requests can be invoiced automatically.');
        }

        return $this->issue($case->user, [[
            'description' => $case->service->name_ar, 'quantity' => 1, 'unit_price_minor' => $case->service->price_minor,
        ]], $case, $case->service, null, $issuedBy);
    }

    public function markPaid(Invoice $invoice, string $method, ?string $reference, ?int $by = null): Invoice
    {
        if (! in_array($method, Invoice::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('Unknown payment method.');
        }
        $updated = DB::transaction(function () use ($invoice, $method, $reference, $by) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== 'unpaid') {
                return null;
            }
            $locked->update(['status' => 'paid', 'paid_at' => now(), 'payment_method' => $method, 'payment_reference' => $reference ? mb_substr($reference, 0, 255) : null]);
            $this->audit('invoice.paid', $locked, $by);

            return $locked;
        });
        if ($updated) {
            $this->mail->send($updated->user, 'invoice_paid', $updated->number);
        }

        return $invoice->refresh();
    }

    public function cancel(Invoice $invoice, ?int $by = null): Invoice
    {
        DB::transaction(function () use ($invoice, $by) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status === 'unpaid') {
                $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                $this->audit('invoice.cancelled', $locked, $by);
            }
        });

        return $invoice->refresh();
    }

    private function audit(string $event, Invoice $invoice, ?int $by): void
    {
        AuditLog::create(['user_id' => $by, 'event' => $event, 'auditable_type' => Invoice::class, 'auditable_id' => $invoice->id,
            'metadata' => ['number' => $invoice->number, 'total_minor' => $invoice->total_minor, 'status' => $invoice->status]]);
    }
}
