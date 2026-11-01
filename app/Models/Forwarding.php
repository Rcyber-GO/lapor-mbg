<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan bahwa informasi laporan telah diteruskan kepada pihak eksternal.
 * Hanya dibuat oleh ReportWorkflow.
 */
#[Fillable(['user_id', 'recipient_type', 'recipient_name', 'forwarded_at', 'note'])]
class Forwarding extends Model
{
    public const RECIPIENT_TYPES = [
        'sekolah' => 'Sekolah',
        'sppg' => 'SPPG',
        'faskes' => 'Fasilitas kesehatan',
        'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'forwarded_at' => 'date',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipientTypeLabel(): string
    {
        return self::RECIPIENT_TYPES[$this->recipient_type] ?? $this->recipient_type;
    }
}
