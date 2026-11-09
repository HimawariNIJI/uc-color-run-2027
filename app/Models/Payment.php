<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Support\QrCode;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * One Midtrans QRIS payment attempt for a registration.
 */
#[Fillable(['order_id', 'gateway_transaction_id', 'amount', 'status', 'qr_string', 'qr_url', 'expires_at', 'paid_at'])]
#[Hidden(['qr_string'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => TransactionStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Pending and still scannable, so no second QRIS should be created.
     */
    public function isActive(): bool
    {
        return $this->isPending() && $this->qr_string !== null && ! $this->hasExpired();
    }

    /**
     * The QRIS code exactly as Midtrans returned it, drawn as an SVG.
     */
    public function qrSvg(int $size = 240): ?string
    {
        return $this->qr_string === null ? null : QrCode::svg($this->qr_string, $size);
    }

    /**
     * Apply a transaction status that was verified with Midtrans. Safe to call again with the same
     * status (duplicate notifications), and "success" is final: no later status can undo a payment.
     * A failed or expired latest attempt gives the registration's slot back.
     *
     * @param  array<string, mixed>  $status  Midtrans transaction status response
     */
    public function applyGatewayStatus(array $status): void
    {
        if (($status['order_id'] ?? null) !== $this->order_id) {
            throw new RuntimeException("Midtrans status for another order was applied to {$this->order_id}.");
        }

        $result = TransactionStatus::fromMidtrans((string) ($status['transaction_status'] ?? ''), $status['fraud_status'] ?? null);

        if ($this->status === TransactionStatus::Success || $result === TransactionStatus::Pending || $result === $this->status) {
            return;
        }

        if ($result === TransactionStatus::Success && (int) round((float) ($status['gross_amount'] ?? 0)) !== $this->amount) {
            report(new RuntimeException("Midtrans reported a different amount for {$this->order_id}; the payment was not confirmed."));

            return;
        }

        $this->forceFill([
            'status' => $result,
            'gateway_transaction_id' => $this->gateway_transaction_id ?? ($status['transaction_id'] ?? null),
            'paid_at' => $result === TransactionStatus::Success
                ? (isset($status['settlement_time']) ? Carbon::parse($status['settlement_time'], 'Asia/Jakarta') : now())
                : null,
        ])->save();

        $registration = $this->registration;

        if ($result === TransactionStatus::Success) {
            $registration->markAsPaid($this->order_id);
        } elseif (! $registration->isPaid() && $registration->latestPayment()->first()?->is($this)) {
            $registration->markAsFailed();
        }
    }
}
