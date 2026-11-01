<?php

namespace App\Support;

use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search and filter values for admin registration lists, read from the query string.
 * Unknown values are ignored instead of rejected, so a stale link never breaks a page.
 */
final readonly class RegistrationFilters
{
    public function __construct(
        public ?string $search = null,
        public ?PaymentStatus $status = null,
        public ?RaceCategory $category = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $search = $request->string('search')->trim()->limit(100, '')->toString();

        return new self(
            search: $search === '' ? null : $search,
            status: $request->enum('status', PaymentStatus::class),
            category: $request->enum('category', RaceCategory::class),
        );
    }

    /**
     * @param  Builder<Registration>  $query
     * @return Builder<Registration>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->search($this->search)
            ->withPaymentStatus($this->status)
            ->inCategory($this->category);
    }

    /**
     * The active filters as query string parameters, for links that keep them.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->status?->value,
            'category' => $this->category?->value,
        ]);
    }

    public function isFiltered(): bool
    {
        return $this->toQuery() !== [];
    }
}
