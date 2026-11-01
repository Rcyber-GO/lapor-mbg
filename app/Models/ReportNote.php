<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan peninjauan dari Pemantau Komunitas. Hanya dibuat oleh ReportWorkflow.
 */
#[Fillable(['user_id', 'status', 'note'])]
class ReportNote extends Model
{
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): ?string
    {
        return $this->status ? Report::STATUS_LABELS[$this->status] ?? $this->status : null;
    }
}
