<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jejak perubahan status laporan. Hanya ditulis ReportObserver.
 */
#[Fillable(['user_id', 'from_status', 'to_status'])]
class ReportStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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

    public function fromLabel(): ?string
    {
        return $this->from_status ? Report::STATUS_LABELS[$this->from_status] ?? $this->from_status : null;
    }

    public function toLabel(): string
    {
        return Report::STATUS_LABELS[$this->to_status] ?? $this->to_status;
    }
}
