<?php

namespace App\Models;

use App\Enums\FeePeriod;
use App\Enums\Gender;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TransactionStatus;
use App\Enums\TshirtSize;
use App\Support\BibNumber;
use App\Support\Midtrans;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Fillable([
    'ticket_price_id',
    'code',
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
     * The package this registration bought.
     *
     * @return BelongsTo<TicketPrice, $this>
     */
    public function ticketPrice(): BelongsTo
    {
        return $this->belongsTo(TicketPrice::class);
    }

    /**
     * The other people in a bundle, in form order (the account holder is the registration itself).
     *
     * @return HasMany<RegistrationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(RegistrationMember::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
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
     * Registrations holding a slot of their package quota: paid, or waiting for a QRIS payment
     * that has not expired yet. An expired or failed payment gives the slot back, even before
     * Midtrans reports the expiry.
     */
    #[Scope]
    protected function holdingSlot(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('payment_status', PaymentStatus::Paid)
            ->orWhere(fn (Builder $query) => $query
                ->where('payment_status', PaymentStatus::Unpaid)
                ->where('payment_expires_at', '>', now())));
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

    /**
     * The package this registration belongs to. Registrations made before packages were stored
     * are matched by type, price type and distance; null for an option that is no longer official.
     */
    public function package(): ?TicketPrice
    {
        if ($this->ticket_price_id !== null) {
            return $this->ticketPrice;
        }

        return TicketPrice::findOption($this->type, $this->type === RegistrationType::Bundling ? null : $this->fee_period, $this->category);
    }

    /**
     * A QRIS payment may only be created while the package has a price set in the database.
     */
    public function hasAnnouncedPrice(): bool
    {
        return $this->package()?->price !== null;
    }

    /**
     * The package name, for example "5K Early Bid" or "3K Family Bundle".
     */
    public function packageName(): string
    {
        return $this->package()?->name() ?? $this->category->label();
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
        $package = $this->package();

        DB::transaction(function () use ($package, $transactionId): void {
            // The package lock keeps two confirmations from handing out the same BIB number.
            $package = $package?->lockForRegistration();
            $this->refresh();

            if ($this->isPaid()) {
                return;
            }

            $this->forceFill([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'payment_expires_at' => null,
                'transaction_id' => $transactionId ?? $this->transaction_id ?? 'PAY-'.$this->code,
                'bib_number' => $this->bib_number ?? $this->firstBibNumber($package),
            ])->save();

            foreach ($this->members()->whereNull('bib_number')->get() as $member) {
                $member->forceFill(['bib_number' => BibNumber::next($package->bibPrefix())])->save();
            }
        });
    }

    /**
     * The account holder gets the number reserved in their registration code. If the registration
     * moved to another package after reserving it (Early Bid full, Normal Bid instead), they get the
     * next number of their current package so the BIB still describes what they paid for.
     */
    private function firstBibNumber(?TicketPrice $package): string
    {
        $reserved = BibNumber::fromRegistrationCode($this->code);

        if ($package === null) {
            return $reserved ?? $this->category->bibPrefix().str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
        }

        return $reserved !== null && str_starts_with($reserved, $package->bibPrefix())
            ? $reserved
            : BibNumber::next($package->bibPrefix());
    }

    /**
     * Create the Midtrans QRIS for the registration's amount and keep it as the latest payment
     * attempt. If Midtrans refuses, the attempt and the registration are marked failed (which gives
     * the slot back) and the exception is rethrown for the caller to report.
     */
    public function startQrisPayment(): Payment
    {
        $payment = $this->payments()->create([
            'order_id' => $this->code.'-'.($this->payments()->count() + 1),
            'amount' => $this->amount,
            'status' => TransactionStatus::Pending,
            'expires_at' => $this->payment_expires_at,
        ]);

        try {
            $charge = Midtrans::chargeQris(
                orderId: $payment->order_id,
                amount: $payment->amount,
                itemId: 'package-'.($this->ticket_price_id ?? 0),
                itemName: $this->packageName().' '.$this->type->label(),
                customer: ['first_name' => mb_substr($this->full_name, 0, 255), 'email' => $this->email, 'phone' => $this->phone],
                expiryMinutes: (int) config('event.payment_window_minutes'),
            );
        } catch (Throwable $exception) {
            $payment->update(['status' => TransactionStatus::Failed]);
            $this->markAsFailed();

            throw $exception;
        }

        // Midtrans reports times in Western Indonesian Time (GMT+7).
        $expiresAt = filled($charge['expiry_time'] ?? null) ? Carbon::parse($charge['expiry_time'], 'Asia/Jakarta') : $this->payment_expires_at;

        $payment->update([
            'gateway_transaction_id' => $charge['transaction_id'] ?? null,
            'qr_string' => $charge['qr_string'],
            'qr_url' => collect($charge['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        $this->forceFill([
            'payment_status' => PaymentStatus::Unpaid,
            'payment_expires_at' => $expiresAt,
            'transaction_id' => $payment->order_id,
        ])->save();

        return $payment;
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
        return $this->type === RegistrationType::Bundling
            ? $this->packageName()
            : $this->category->label().' '.$this->type->label();
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
