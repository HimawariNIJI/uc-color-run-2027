<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Http\Controllers\PaymentController;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'registration_code',
        'user_id',
        'full_name',
        'email',
        'phone_number',
        'date_of_birth',
        'gender',
        'ktp_passport',
        'race_category',
        'tshirt_size',
        'emergency_name',
        'emergency_phone',
        'province',
        'city',
        'address',
        'amount',
        'payment_status',
        'bib_number',
        'order_id',
        'agreement',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'amount'        => 'integer',
            'agreement'     => 'boolean',
        ];
    }

    /**
     * Custom attribute for payment_status that gracefully handles
     * both legacy string values ('Pending', 'Paid', 'Unpaid') and
     * the App\Enums\PaymentStatus enum without throwing a ValueError.
     */
    protected function paymentStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value instanceof PaymentStatus) {
                    return $value;
                }
                $normalized = strtolower(trim((string) $value));

                return match ($normalized) {
                    'paid'   => PaymentStatus::Paid,
                    'failed' => PaymentStatus::Failed,
                    default  => PaymentStatus::Unpaid, // handles 'pending', 'unpaid', null, ''
                };
            },
            set: function ($value) {
                if ($value instanceof PaymentStatus) {
                    return $value->value;
                }
                $normalized = strtolower(trim((string) $value));

                return match ($normalized) {
                    'paid'   => PaymentStatus::Paid->value,
                    'failed' => PaymentStatus::Failed->value,
                    default  => PaymentStatus::Unpaid->value,
                };
            }
        );
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    // ─── Status Helpers ──────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function hasFailedPayment(): bool
    {
        return $this->payment_status === PaymentStatus::Failed;
    }

    public function isEditable(): bool
    {
        return ! $this->isPaid();
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function markAsPaid(): void
    {
        if ($this->isPaid()) {
            return;
        }

        $bib = $this->bib_number ?: PaymentController::generateBibNumber($this->race_category);

        $this->update([
            'payment_status' => PaymentStatus::Paid,
            'bib_number'     => $bib,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['payment_status' => PaymentStatus::Failed]);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Registration code alias for views that use ->code.
     */
    public function getCodeAttribute(): string
    {
        return $this->registration_code;
    }

    // ─── Pricing ─────────────────────────────────────────────────────────────

    public static function getPriceForCategory(string $category): int
    {
        return match (strtolower(trim($category))) {
            '10k', '10k run', '10 km'           => 200000,
            '21k', '21k half marathon', '21 km' => 300000,
            '3k', '3k run', '3 km'              => 100000,
            default                             => 150000, // 5K Run / default
        };
    }
}
