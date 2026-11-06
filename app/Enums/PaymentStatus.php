<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Failed = 'failed';

    /**
     * Label used on the My Registration page.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
        };
    }

    /**
     * Label used on the payment pages.
     */
    public function settlementLabel(): string
    {
        return $this === self::Paid ? 'LUNAS' : 'BELUM LUNAS';
    }

    /**
     * Registration status of an account, shown in the admin console.
     */
    public function accountStatusLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Pending Payment',
            self::Paid => 'Registered',
            self::Failed => 'Payment Failed',
        };
    }

    /**
     * Badge tone used to render this status.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Failed => 'error',
        };
    }
}
