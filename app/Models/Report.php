<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * user_id dan status sengaja tidak fillable: pemilik diisi lewat relasi
 * user->reports(), status hanya berubah melalui alur peninjauan.
 * evidence_path/evidence_name juga tidak fillable: hanya diisi dari file
 * yang sudah divalidasi dan disimpan server.
 */
#[Fillable([
    'school_id',
    'distribution_id',
    'reporter_relation',
    'incident_date',
    'affected_count',
    'symptoms',
    'description',
])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    public const STATUS_DIKIRIM = 'dikirim';

    public const STATUS_LABELS = [
        self::STATUS_DIKIRIM => 'Dikirim',
    ];

    public const EVIDENCE_DISK = 'local';

    public const EVIDENCE_DIRECTORY = 'evidence';

    public const RELATIONS = [
        'orang_tua' => 'Orang tua/wali siswa',
        'guru' => 'Guru/tenaga sekolah',
        'siswa' => 'Siswa',
        'lainnya' => 'Lainnya',
    ];

    protected $attributes = [
        'status' => self::STATUS_DIKIRIM,
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function relationLabel(): string
    {
        return self::RELATIONS[$this->reporter_relation] ?? $this->reporter_relation;
    }

    public function evidenceIsImage(): bool
    {
        return in_array(strtolower(pathinfo($this->evidence_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true);
    }
}
