<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\TshirtSize;
use Database\Factories\RegistrationMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Another person in a Family or Couple Bundle (positions 2 to 4; the account holder is the
 * registration itself). Members share the holder's emergency contact and address.
 */
#[Fillable(['position', 'full_name', 'phone', 'birth_date', 'gender', 'identity_number', 'tshirt_size'])]
class RegistrationMember extends Model
{
    /** @use HasFactory<RegistrationMemberFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'birth_date' => 'date',
            'gender' => Gender::class,
            'tshirt_size' => TshirtSize::class,
            'identity_number' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
