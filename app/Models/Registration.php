<?php

namespace App\Models;

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
            'amount' => 'integer',
            'agreement' => 'boolean',
        ];
    }

    /**
     * Related user if authenticated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Related Midtrans payment order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    /**
     * Helper to get category pricing.
     */
    public static function getPriceForCategory(string $category): int
    {
        return match ($category) {
            '10K Run', '10K' => 200000,
            '21K Half Marathon', '21K' => 300000,
            default => 150000, // 5K Run / default
        };
    }
}

