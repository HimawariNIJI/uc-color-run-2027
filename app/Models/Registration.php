<?php

namespace App\Models;

use App\Enums\FeePeriod;
use App\Enums\Gender;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TshirtSize;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type',
    'category',
    'full_name',
    'email',
    'phone',
    'birth_date',
    'gender',
    'identity_number',
    'tshirt_size',
    'emergency_contact_name',
    'emergency_contact_phone',
    'province',
    'city',
    'address',
    'amount',
    'fee_period',
])]
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    /**
     * Characters used for registration codes (no look-alike characters).
     */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * First segment of the racepack collection QR code.
     */
    private const RACEPACK_PAYLOAD_PREFIX = 'UCR2027';

    protected static function booted(): void
    {
        static::creating(function (Registration $registration): void {
            $registration->code ??= static::generateUniqueCode();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RegistrationType::class,
            'category' => RaceCategory::class,
            'gender' => Gender::class,
            'tshirt_size' => TshirtSize::class,
            'fee_period' => FeePeriod::class,
            'payment_status' => PaymentStatus::class,
            'identity_number' => 'encrypted',
            'birth_date' => 'date',
            'amount' => 'integer',
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'bib_collected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who handed over the BIB at racepack collection.
     *
     * @return BelongsTo<Admin, $this>
     */
    public function bibCollector(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'bib_collected_by');
    }

    /**
     * Search by participant name, email or registration ID.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereLike('full_name', "%{$term}%")
            ->orWhereLike('email', "%{$term}%")
            ->orWhereLike('code', "%{$term}%"));
    }

    #[Scope]
    protected function withPaymentStatus(Builder $query, ?PaymentStatus $status): void
    {
        if ($status) {
            $query->where('payment_status', $status);
        }
    }

    #[Scope]
    protected function inCategory(Builder $query, ?RaceCategory $category): void
    {
        if ($category) {
            $query->where('category', $category);
        }
    }

    /**
     * Early Bird registrations holding one of the limited slots: paid, or waiting for payment.
     */
    #[Scope]
    protected function holdingEarlyBirdSlot(Builder $query): void
    {
        $query->where('fee_period', FeePeriod::EarlyBird)
            ->whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::Unpaid]);
    }

    /**
     * Registrations whose BIB has been handed over at racepack collection.
     */
    #[Scope]
    protected function bibCollected(Builder $query): void
    {
        $query->whereNotNull('bib_collected_at');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function hasFailedPayment(): bool
    {
        return $this->payment_status === PaymentStatus::Failed;
    }

    /**
     * Participants may edit their data until the payment is confirmed.
     */
    public function isEditable(): bool
    {
        return ! $this->isPaid();
    }

    public function paymentHasExpired(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid
            && ($this->payment_expires_at === null || $this->payment_expires_at->isPast());
    }

    /**
     * Start a fresh QRIS payment window.
     */
    public function openPaymentWindow(): void
    {
        $this->forceFill([
            'payment_status' => PaymentStatus::Unpaid,
            'payment_expires_at' => now()->addMinutes((int) config('event.payment_window_minutes')),
        ])->save();
    }

    /**
     * Confirm the payment and issue the participant's BIB number.
     *
     * Pass the payment gateway's reference once a gateway is integrated; until then the
     * transaction ID is derived from the registration ID.
     */
    public function markAsPaid(?string $transactionId = null): void
    {
        $this->forceFill([
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'payment_expires_at' => null,
            'transaction_id' => $transactionId ?? $this->transaction_id ?? 'PAY-'.$this->code,
            'bib_number' => $this->bib_number ?? $this->category->bibPrefix().str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
        ])->save();
    }

    public function hasCollectedBib(): bool
    {
        return $this->bib_collected_at !== null;
    }

    /**
     * Record that the participant collected their BIB. Returns false when it was already
     * collected, also when two collection desks scan the same QR code at the same time.
     */
    public function markBibAsCollected(Admin $admin): bool
    {
        $collectedAt = now();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->whereNull('bib_collected_at')
            ->update(['bib_collected_at' => $collectedAt, 'bib_collected_by' => $admin->getKey()]);

        if ($updated === 0) {
            $this->refresh();

            return false;
        }

        $this->forceFill(['bib_collected_at' => $collectedAt, 'bib_collected_by' => $admin->getKey()])->syncOriginal();

        return true;
    }

    public function markAsFailed(): void
    {
        $this->forceFill([
            'payment_status' => PaymentStatus::Failed,
            'payment_expires_at' => null,
        ])->save();
    }

    /**
     * Human readable race category, for example "5K External".
     */
    public function raceLabel(): string
    {
        return $this->category->label().' '.$this->type->label();
    }

    /**
     * Payload encoded in the racepack collection QR code.
     */
    public function racepackPayload(): string
    {
        return implode('|', [self::RACEPACK_PAYLOAD_PREFIX, $this->code, $this->bib_number]);
    }

    /**
     * Read a scanned racepack QR code. Returns null when it is not one of ours.
     *
     * @return array{code: string, bib_number: string}|null
     */
    public static function parseRacepackPayload(string $payload): ?array
    {
        $parts = explode('|', trim($payload));

        if (count($parts) !== 3 || $parts[0] !== self::RACEPACK_PAYLOAD_PREFIX || blank($parts[1]) || blank($parts[2])) {
            return null;
        }

        return ['code' => $parts[1], 'bib_number' => $parts[2]];
    }

    public static function generateUniqueCode(): string
    {
        do {
            $suffix = '';

            for ($i = 0; $i < 5; $i++) {
                $suffix .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = 'UCR-2027-'.$suffix;
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
