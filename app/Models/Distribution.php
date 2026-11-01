<?php

namespace App\Models;

use Database\Factories\DistributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kegiatan penyaluran makanan MBG pada tanggal tertentu ke satu sekolah.
 */
#[Fillable(['school_id', 'distribution_date', 'menu', 'provider_name', 'portion_count'])]
class Distribution extends Model
{
    /** @use HasFactory<DistributionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
