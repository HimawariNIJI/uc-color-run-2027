<?php

namespace App\Enums;

/**
 * Status of one Midtrans QRIS payment attempt (payments.status).
 */
enum TransactionStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * Map a transaction_status verified with Midtrans. QRIS payments end in "settlement";
     * "capture" only happens for cards and counts as paid when the fraud check accepted it.
     * Cancelled, denied and failed transactions are all failures for the participant.
     */
    public static function fromMidtrans(string $transactionStatus, ?string $fraudStatus = null): self
    {
        return match ($transactionStatus) {
            'settlement' => self::Success,
            'capture' => in_array($fraudStatus, [null, 'accept'], true) ? self::Success : self::Pending,
            'pending' => self::Pending,
            'expire' => self::Expired,
            default => self::Failed,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Success => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
