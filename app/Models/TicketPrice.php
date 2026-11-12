<?php

namespace App\Models;

use App\Enums\FeePeriod;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Support\BibNumber;
use App\Support\NumberFormat;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One official registration package, for example "3K Early Bid" for Internal participants or
 * "3K Family Bundle" (see TicketPriceSeeder). The price is what one package costs in whole
 * Rupiah: a bundle's price covers the whole bundle, not each person.
 *
 * Paid registrations and registrations whose QRIS payment has not expired yet hold a slot;
 * expired or failed payments give it back. Individual packages count participants, bundles
 * count whole packages (not the BIB numbers they use). A package is open when it is active, its
 * price is set and slots are left. A null quota means unlimited and is never checked (the Normal
 * External packages); 0 means no slots at all.
 */
#[Fillable(['registration_type', 'bundle_type', 'race_category', 'price_type', 'price_basis', 'max_participants', 'price', 'quota', 'is_active'])]
class TicketPrice extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_type' => RegistrationType::class,
            'race_category' => RaceCategory::class,
            'price_type' => FeePeriod::class,
            'max_participants' => 'integer',
            'price' => 'integer',
            'quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Active packages with the number of slots taken, in the official order.
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('is_active', true)
            ->withCount(['registrations as taken_count' => fn (Builder $registrations) => $registrations->holdingSlot()])
            ->orderBy('id');
    }

    /**
     * Every active package with the slots taken.
     *
     * @return Collection<int, self>
     */
    public static function catalogue(): Collection
    {
        return self::query()->listed()->get();
    }

    /**
     * The package for this choice. Bundles have no price type, so they are found with null.
     */
    public static function findOption(RegistrationType $type, ?FeePeriod $priceType, RaceCategory $category): ?self
    {
        return self::query()->listed()
            ->where('registration_type', $type)
            ->where('race_category', $category)
            ->where('price_type', $priceType)
            ->first();
    }

    public static function findBundle(string $bundleType): ?self
    {
        return self::query()->listed()
            ->where('registration_type', RegistrationType::Bundling)
            ->where('bundle_type', $bundleType)
            ->first();
    }

    /**
     * Lock this package row until the database transaction ends and recount its slots, so
     * simultaneous registrations or payments can neither exceed the quota nor reuse a number.
     * It must be the first query of the transaction, so the recount sees every committed row.
     */
    public function lockForRegistration(): self
    {
        $locked = self::query()->lockForUpdate()->findOrFail($this->getKey());
        $locked->setAttribute('taken_count', $locked->registrations()->holdingSlot()->count());

        return $locked;
    }

    public function isBundle(): bool
    {
        return $this->bundle_type !== null;
    }

    /**
     * The Family Bundle proves identity with the family's Kartu Keluarga instead of a KTP.
     */
    public function isFamilyBundle(): bool
    {
        return $this->bundle_type === 'family';
    }

    /**
     * Official package name, for example "3K Early Bid" or "3K Family Bundle".
     */
    public function name(): string
    {
        return $this->race_category->label().' '.$this->kindLabel();
    }

    /**
     * "Early Bid", "Normal Bid", "Family Bundle" or "Couple Bundle".
     */
    public function kindLabel(): string
    {
        return $this->isBundle() ? Str::title($this->bundle_type).' Bundle' : $this->price_type->label();
    }

    /**
     * Who the package is for: "Internal", "External" or "4 peserta" for a bundle. A bundle is for
     * exactly max_participants people: the Family Bundle 4, the Couple Bundle 2.
     */
    public function eligibility(): string
    {
        return $this->isBundle() ? "{$this->max_participants} peserta" : $this->registration_type->label();
    }

    public function takenSlots(): int
    {
        return (int) ($this->taken_count ?? $this->registrations()->holdingSlot()->count());
    }

    /**
     * A null quota means unlimited (the Normal External packages): the quota is never checked.
     */
    public function hasUnlimitedQuota(): bool
    {
        return $this->quota === null;
    }

    /**
     * Slots left, or null when the quota is unlimited.
     */
    public function remaining(): ?int
    {
        return $this->hasUnlimitedQuota() ? null : max(0, $this->quota - $this->takenSlots());
    }

    public function isSoldOut(): bool
    {
        return ! $this->hasUnlimitedQuota() && $this->remaining() === 0;
    }

    public function isOpen(): bool
    {
        return $this->is_active && $this->price !== null && ! $this->isSoldOut();
    }

    public function priceLabel(): string
    {
        return $this->price === null ? 'Price To Be Announced' : Rupiah::format($this->price);
    }

    /**
     * For example "Kuota 75 slot", "Sisa 12 dari 75 slot", "Kuota 20 paket" or "Kuota tidak terbatas".
     */
    public function quotaLabel(): string
    {
        $unit = $this->isBundle() ? 'paket' : 'slot';

        return match (true) {
            $this->hasUnlimitedQuota() => 'Kuota tidak terbatas',
            $this->isSoldOut() => 'Sold out',
            $this->takenSlots() === 0 => 'Kuota '.NumberFormat::integer($this->quota).' '.$unit,
            default => 'Sisa '.NumberFormat::integer($this->remaining()).' dari '.NumberFormat::integer($this->quota).' '.$unit,
        };
    }

    /**
     * Why the package cannot be registered right now, or null when it is open.
     */
    public function unavailableMessage(): ?string
    {
        $package = "{$this->name()} ({$this->eligibility()})";

        return match (true) {
            $this->isSoldOut() => "Kuota paket {$package} sudah habis. Silakan pilih paket lain.",
            ! $this->is_active => "Paket {$package} sedang tidak tersedia.",
            $this->price === null => "Harga paket {$package} belum diumumkan, jadi pendaftarannya belum dibuka.",
            default => null,
        };
    }

    /**
     * The official 3-digit prefix of every BIB in this package, for example "031" for 3K Early
     * Bird Internal (see BibNumber::PREFIXES).
     */
    public function bibPrefix(): string
    {
        return BibNumber::prefix($this->race_category->value, $this->price_type?->value, $this->registration_type->value, $this->bundle_type);
    }
}
