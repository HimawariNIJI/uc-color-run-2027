<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The last BIB sequence handed out for one official 3-digit prefix, for example "031" for
 * 3K Early Bird Internal. There is one row per prefix (created by the migration) and the number
 * only ever goes up, so a BIB is never handed out twice. Only BibNumber::reserve() changes it.
 */
#[Table(key: 'prefix', keyType: 'string', incrementing: false)]
#[Fillable(['prefix', 'last_sequence'])]
class BibSequence extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_sequence' => 'integer',
        ];
    }
}
