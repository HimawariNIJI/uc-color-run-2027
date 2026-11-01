<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'participant_name',
        'race_category',
        'tshirt_size',
        'item_name',
        'amount',
        'status',
        'qr_url',
        'qr_string',
        'raw_midtrans_response',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'raw_midtrans_response' => 'array',
        ];
    }

    /**
     * Related registration.
     */
    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class, 'order_id', 'order_id');
    }
}
