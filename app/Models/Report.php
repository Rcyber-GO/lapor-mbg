<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public const STATUS_DITINJAU = 'ditinjau';

    public const STATUS_PERLU_DILENGKAPI = 'perlu_dilengkapi';

    public const STATUS_PERLU_DITERUSKAN = 'perlu_diteruskan';

    public const STATUS_DITERUSKAN = 'diteruskan';

    public const STATUS_SELESAI = 'selesai';

    /**
     * Label sengaja tidak memberi kesan diagnosis atau penetapan resmi (§5.6).
     */
    public const STATUS_LABELS = [
        self::STATUS_DIKIRIM => 'Dikirim',
        self::STATUS_DITINJAU => 'Sedang Ditinjau',
        self::STATUS_PERLU_DILENGKAPI => 'Perlu Dilengkapi',
        self::STATUS_PERLU_DITERUSKAN => 'Ditandai untuk Diteruskan',
        self::STATUS_DITERUSKAN => 'Sudah Diteruskan',
        self::STATUS_SELESAI => 'Selesai Dipantau',
    ];

    /**
     * BR-01: status ketika pelapor masih boleh memperbaiki laporannya.
     */
    public const EDITABLE_STATUSES = [self::STATUS_DIKIRIM, self::STATUS_PERLU_DILENGKAPI];

    /**
     * BR-04: status ketika koordinator boleh mencatat penerusan. Laporan yang
     * sudah diteruskan boleh dicatat lagi bila diteruskan ke pihak tambahan.
     */
    public const FORWARDABLE_STATUSES = [self::STATUS_PERLU_DITERUSKAN, self::STATUS_DITERUSKAN];

    /**
     * Status akhir: tidak ada perubahan status maupun catatan baru setelahnya
     * (kecuali penerusan tambahan, lihat FORWARDABLE_STATUSES).
     */
    public const FINAL_STATUSES = [self::STATUS_DITERUSKAN, self::STATUS_SELESAI];

    /**
     * BR-02: peralihan status yang boleh dilakukan Pemantau Komunitas, beserta
     * apakah catatan wajib diisi. Peralihan lain hanya terjadi lewat aksi khusus:
     * perbaikan oleh pelapor (Perlu Dilengkapi → Dikirim) dan pencatatan
     * penerusan oleh koordinator (Ditandai untuk Diteruskan → Sudah Diteruskan).
     */
    public const REVIEW_TRANSITIONS = [
        self::STATUS_DIKIRIM => [
            self::STATUS_DITINJAU => false,
        ],
        self::STATUS_DITINJAU => [
            self::STATUS_PERLU_DILENGKAPI => true,
            self::STATUS_PERLU_DITERUSKAN => true,
            self::STATUS_SELESAI => true,
        ],
    ];

    public const REVIEW_ACTION_LABELS = [
        self::STATUS_DITINJAU => 'Mulai Tinjau',
        self::STATUS_PERLU_DILENGKAPI => 'Minta Dilengkapi',
        self::STATUS_PERLU_DITERUSKAN => 'Tandai untuk Diteruskan',
        self::STATUS_SELESAI => 'Selesai Dipantau',
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

    public function notes(): HasMany
    {
        return $this->hasMany(ReportNote::class);
    }

    public function forwardings(): HasMany
    {
        return $this->hasMany(Forwarding::class);
    }

    public function isForwardable(): bool
    {
        return in_array($this->status, self::FORWARDABLE_STATUSES, true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    /**
     * Peralihan yang sah dari status saat ini: [status tujuan => catatan wajib].
     *
     * @return array<string, bool>
     */
    public function reviewTransitions(): array
    {
        return self::REVIEW_TRANSITIONS[$this->status] ?? [];
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
