<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'registration_id',
        'name',
        'email',
        'bib_number',
        'category',
        'qr_token',
        'racepack_collected',
        'collected_at',
        'sheets_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'racepack_collected' => 'boolean',
            'collected_at' => 'datetime',
            'sheets_synced_at' => 'datetime',
        ];
    }

    /** Waktu pengambilan dalam zona waktu acara (WIB), format yang sama dengan di Sheets. */
    public function collectedAtLocal(): ?string
    {
        return $this->collected_at
            ?->copy()
            ->timezone(config('uc-color-run.site.timezone', 'Asia/Jakarta'))
            ->format('Y-m-d H:i:s');
    }

    /** Peserta yang baru saja mengambil racepack, terbaru di atas. */
    public static function recentlyCollected(int $limit = 10): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('racepack_collected', true)
            ->orderByDesc('collected_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (self $p) => $p->toScanArray());
    }

    /** Data ringkas untuk tabel scanner & respons JSON. */
    public function toScanArray(): array
    {
        return [
            'registration_id' => $this->registration_id,
            'name' => $this->name,
            'email' => $this->email,
            'category' => $this->category,
            'bib_number' => $this->bib_number,
            'collected_at' => $this->collectedAtLocal(),
        ];
    }
}
