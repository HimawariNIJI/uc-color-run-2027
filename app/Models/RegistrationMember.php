<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\TshirtSize;
use App\Observers\ParticipantsSheetObserver;
use Database\Factories\RegistrationMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Another person in a Family or Couple Bundle (positions 2 to 4; the account holder is the
 * registration itself). Members share the holder's emergency contact and address. Couple Bundle
 * members type a KTP/Passport number; Family Bundle members have none, because the payer uploads
 * the family's Kartu Keluarga instead.
 *
 * Every member gets their own BIB once the bundle's payment is confirmed, together with the payer
 * and right after the payer's number (see Registration::markAsPaid).
 */
#[ObservedBy([ParticipantsSheetObserver::class])]
#[Fillable(['position', 'full_name', 'phone', 'birth_date', 'gender', 'identity_number', 'tshirt_size', 'medical_history', 'drug_allergies'])]
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
            'medical_history' => 'encrypted',
            'drug_allergies' => 'encrypted',
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
