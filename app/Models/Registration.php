<?php

namespace App\Models;

use App\Enums\FeePeriod;
use App\Enums\Gender;
use App\Enums\IdentityDocument;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TransactionStatus;
use App\Enums\TshirtSize;
use App\Exceptions\BibNumbersExhausted;
use App\Observers\ParticipantsSheetObserver;
use App\Support\BibNumber;
use App\Support\Midtrans;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

#[ObservedBy([ParticipantsSheetObserver::class])]
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
    'ktp_photo_path',
    'family_card_photo_path',
    'tshirt_size',
    'emergency_contact_name',
    'emergency_contact_phone',
    'medical_history',
    'drug_allergies',
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
            'medical_history' => 'encrypted',
            'drug_allergies' => 'encrypted',
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
     * Racepack desk scans of this registration's QR, accepted and rejected.
     *
     * @return HasMany<QrScan, $this>
     */
    public function qrScans(): HasMany
    {
        return $this->hasMany(QrScan::class);
    }

    /**
     * Search by payer name, email, registration ID, or the BIB of any participant (payer or bundle member).
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
            ->orWhereLike('code', "%{$term}%")
            ->orWhereLike('bib_number', "%{$term}%")
            ->orWhereHas('members', fn (Builder $members) => $members->whereLike('bib_number', "%{$term}%")));
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
     * The payer's KTP, or the family's Kartu Keluarga for a Family Bundle.
     */
    public function identityDocument(): IdentityDocument
    {
        return IdentityDocument::forPackage($this->package());
    }

    public function identityPhotoPath(): ?string
    {
        return $this->getAttribute($this->identityDocument()->column());
    }

    /**
     * Registrations made before the photo field existed have none.
     */
    public function hasIdentityPhoto(): bool
    {
        return filled($this->identityPhotoPath());
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
     * Unpaid and still inside its payment window, so it holds a slot of the package (see holdingSlot()).
     */
    public function isAwaitingPayment(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid && ! $this->paymentHasExpired();
    }

    /**
     * Where the payment stands, from the states the backend has verified with Midtrans:
     * "Settlement", "Pending", "Expired", "Cancelled" or "Failed". A settlement is final.
     */
    public function paymentStage(): string
    {
        if ($this->isPaid()) {
            return 'Settlement';
        }

        if ($this->isAwaitingPayment()) {
            return 'Pending';
        }

        return match ($this->latestPayment?->status) {
            TransactionStatus::Cancelled => 'Cancelled',
            TransactionStatus::Expired => 'Expired',
            TransactionStatus::Failed => 'Failed',
            default => $this->payment_status === PaymentStatus::Unpaid ? 'Expired' : 'Failed',
        };
    }

    /**
     * The payment status on My Registration: Paid, Unpaid (waiting for the payment), Expired,
     * Cancelled or Failed.
     */
    public function paymentStatusLabel(): string
    {
        return match ($stage = $this->paymentStage()) {
            'Settlement' => PaymentStatus::Paid->label(),
            'Pending' => PaymentStatus::Unpaid->label(),
            default => $stage,
        };
    }

    public function paymentStatusTone(): string
    {
        return match ($this->paymentStage()) {
            'Settlement' => 'success',
            'Pending' => 'warning',
            default => 'error',
        };
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
     * Confirm the payment and issue every participant's BIB. Only a verified Midtrans settlement
     * (or the local payment simulation) calls this, so BIBs follow the order in which payments are
     * confirmed, not the order of registering.
     *
     * One transaction locks the package row, then this registration's row, and then draws the BIBs
     * from the package's sequence (see BibNumber::reserve). A second confirmation of the same
     * registration, such as a repeated or simultaneous notification or a late settlement of an
     * older attempt, waits for those locks, sees the payment and changes nothing. A participant
     * therefore never gets a second BIB, and once issued a BIB never changes.
     *
     * Pass the verified payment attempt: its order ID becomes the transaction ID and its Midtrans
     * settlement time the payment date.
     */
    public function markAsPaid(?Payment $payment = null): void
    {
        $package = $this->package();

        DB::transaction(function () use ($package, $payment): void {
            $package = $package?->lockForRegistration();
            $registration = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($registration->isPaid()) {
                if ($payment !== null && $registration->transaction_id !== $payment->order_id) {
                    report(new RuntimeException("Registration {$registration->code} was already paid, and Midtrans settled order {$payment->order_id} as well."));
                }

                return;
            }

            $registration->forceFill([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => $payment?->paid_at ?? now(),
                'payment_expires_at' => null,
                'transaction_id' => $payment?->order_id ?? $registration->transaction_id ?? 'PAY-'.$registration->code,
            ])->save();

            $registration->issueBibNumbers($package);
        });

        $this->refresh();
    }

    /**
     * Draw the next numbers of the paid package for every participant without a BIB, the payer
     * first and then bundle members by position. A bundle draws them all at once, so they are
     * consecutive. Runs inside markAsPaid's transaction, after the payment was recorded.
     *
     * The payment stays confirmed when no BIB can be issued (the package is no longer official or
     * its numbers ran out); the problem is reported so the committee can step in.
     */
    private function issueBibNumbers(?TicketPrice $package): void
    {
        $members = $this->members()->whereNull('bib_number')->get();
        $count = ($this->bib_number === null ? 1 : 0) + $members->count();

        if ($count === 0) {
            return;
        }

        if ($package === null) {
            report(new RuntimeException("Registration {$this->code} was paid but has no official package, so no BIB was issued."));

            return;
        }

        try {
            $numbers = BibNumber::reserve($package->bibPrefix(), $count);
        } catch (BibNumbersExhausted $exception) {
            report($exception);

            return;
        }

        if ($this->bib_number === null) {
            $this->forceFill(['bib_number' => array_shift($numbers)])->save();
        }

        foreach ($members as $member) {
            $member->forceFill(['bib_number' => array_shift($numbers)])->save();
        }
    }

    /**
     * Record a new pending payment attempt for the registration's amount and payment window, with
     * its own Midtrans order ID: the registration code and the attempt number, for example
     * "UCCR-052-0001-2". Earlier attempts are kept unchanged as payment history, and an order ID
     * Midtrans has seen is never used again.
     */
    public function newPaymentAttempt(): Payment
    {
        $attempt = $this->payments()->count();

        do {
            $orderId = $this->code.'-'.++$attempt;
        } while (Payment::query()->where('order_id', $orderId)->exists());

        return $this->payments()->create([
            'order_id' => $orderId,
            'amount' => $this->amount,
            'status' => TransactionStatus::Pending,
            'expires_at' => $this->payment_expires_at,
        ]);
    }

    /**
     * Create the Midtrans QRIS for the registration's amount as a new payment attempt.
     */
    public function startQrisPayment(): Payment
    {
        return $this->chargeQris($this->newPaymentAttempt());
    }

    /**
     * Ask Midtrans to create the QRIS of a pending attempt and keep it as the current payment. If
     * Midtrans refuses, the attempt and the registration are marked failed (which gives the slot
     * back) and the exception is rethrown for the caller to report.
     */
    public function chargeQris(Payment $payment): Payment
    {
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

        // A payment confirmed meanwhile (an older attempt settling) is final and stays as it is.
        DB::transaction(function () use ($payment, $expiresAt): void {
            $registration = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($registration->isPaid()) {
                return;
            }

            $registration->forceFill([
                'payment_status' => PaymentStatus::Unpaid,
                'payment_expires_at' => $expiresAt,
                'transaction_id' => $payment->order_id,
            ])->save();
        });

        $this->refresh();

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

    /**
     * Mark the payment as failed, which gives the slot back. A confirmed payment is final: a paid
     * registration is never changed, even when a failure is reported at the same moment.
     */
    public function markAsFailed(): void
    {
        DB::transaction(function (): void {
            $registration = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($registration->isPaid()) {
                return;
            }

            $registration->forceFill([
                'payment_status' => PaymentStatus::Failed,
                'payment_expires_at' => null,
            ])->save();
        });

        $this->refresh();
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
     * Every participant's BIB, the payer first and then bundle members by position (null where no
     * BIB has been issued yet). This order is the racepack QR's order, see RacepackQr.
     *
     * @return list<?string>
     */
    public function participantBibNumbers(): array
    {
        return [$this->bib_number, ...$this->members->pluck('bib_number')->all()];
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
