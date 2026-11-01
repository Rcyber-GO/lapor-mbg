<?php

namespace App\Services;

use App\Exceptions\ReportWorkflowException;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Satu-satunya tempat perubahan status laporan dan penerapan business rule
 * alur laporan. Siapa yang boleh memanggil tiap tindakan dijaga ReportPolicy;
 * kelas ini menentukan kapan tindakan itu boleh terjadi.
 */
class ReportWorkflow
{
    /**
     * Membuat laporan baru beserta bukti pendukungnya.
     */
    public function submit(User $reporter, array $data, UploadedFile $evidence): Report
    {
        // store() memberi nama acak; nama asli hanya disimpan untuk ditampilkan.
        $path = $evidence->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);

        try {
            return DB::transaction(function () use ($reporter, $data, $evidence, $path) {
                // Kunci baris pelapor agar dua pengiriman bersamaan tidak lolos BR-05.
                User::whereKey($reporter->getKey())->lockForUpdate()->first();
                $this->ensureNoActiveDuplicate($reporter->id, (int) $data['distribution_id']);

                $report = $reporter->reports()->make($data);
                $report->evidence_path = $path;
                $report->evidence_name = Str::limit($evidence->getClientOriginalName(), 255, '');
                $report->save();

                return $report;
            });
        } catch (Throwable $e) {
            // Jangan tinggalkan file yatim bila laporan gagal tersimpan.
            Storage::disk(Report::EVIDENCE_DISK)->delete($path);

            throw $e;
        }
    }

    /**
     * BR-01: pelapor hanya dapat memperbaiki laporan berstatus Dikirim atau
     * Perlu Dilengkapi. Laporan yang diperbaiki setelah diminta dilengkapi
     * kembali berstatus Dikirim agar ditinjau ulang.
     */
    public function update(Report $report, array $data, ?UploadedFile $evidence): void
    {
        $newPath = $evidence?->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);
        $oldPath = null;

        try {
            DB::transaction(function () use ($report, $data, $evidence, $newPath, &$oldPath) {
                $report = $this->lock($report);

                if (! $report->isEditable()) {
                    throw new ReportWorkflowException('Laporan berstatus "'.$report->statusLabel().'" sedang atau sudah ditinjau sehingga tidak dapat diubah.');
                }

                if ((int) $data['distribution_id'] !== $report->distribution_id) {
                    $this->ensureNoActiveDuplicate($report->user_id, (int) $data['distribution_id'], $report->id);
                }

                $report->fill($data);

                if ($newPath) {
                    $oldPath = $report->evidence_path;
                    $report->evidence_path = $newPath;
                    $report->evidence_name = Str::limit($evidence->getClientOriginalName(), 255, '');
                }

                if ($report->status === Report::STATUS_PERLU_DILENGKAPI) {
                    $report->status = Report::STATUS_DIKIRIM;
                }

                $report->save();
            });
        } catch (Throwable $e) {
            // Perubahan dibatalkan: buang file baru, file lama tetap dipakai.
            if ($newPath) {
                Storage::disk(Report::EVIDENCE_DISK)->delete($newPath);
            }

            throw $e;
        }

        // File lama baru dihapus setelah laporan pasti tersimpan dengan file baru.
        if ($oldPath) {
            Storage::disk(Report::EVIDENCE_DISK)->delete($oldPath);
        }
    }

    /**
     * BR-02: Pemantau mengubah status hanya sesuai Report::REVIEW_TRANSITIONS.
     */
    public function review(Report $report, User $reviewer, string $toStatus, ?string $note): void
    {
        DB::transaction(function () use ($report, $reviewer, $toStatus, $note) {
            $report = $this->lock($report);
            $transitions = $report->reviewTransitions();

            if (! array_key_exists($toStatus, $transitions)) {
                throw new ReportWorkflowException(sprintf(
                    'Laporan berstatus "%s" tidak dapat diubah menjadi "%s".',
                    $report->statusLabel(),
                    Report::STATUS_LABELS[$toStatus] ?? $toStatus,
                ));
            }

            if ($transitions[$toStatus] && blank($note)) {
                throw new ReportWorkflowException(sprintf(
                    'Catatan wajib diisi untuk mengubah status menjadi "%s".',
                    Report::STATUS_LABELS[$toStatus],
                ));
            }

            $report->status = $toStatus;
            $report->save();

            if (filled($note)) {
                $report->notes()->create(['user_id' => $reviewer->id, 'status' => $toStatus, 'note' => $note]);
            }
        });
    }

    /**
     * Catatan tambahan tanpa perubahan status, selama laporan belum berstatus akhir.
     */
    public function addNote(Report $report, User $reviewer, string $note): void
    {
        DB::transaction(function () use ($report, $reviewer, $note) {
            $report = $this->lock($report);
            $this->ensureNotFinal($report);

            $report->notes()->create(['user_id' => $reviewer->id, 'note' => $note]);
        });
    }

    /**
     * BR-05: satu pelapor hanya boleh memiliki satu laporan aktif (belum
     * berstatus akhir) untuk distribusi yang sama, untuk mencegah laporan ganda.
     */
    private function ensureNoActiveDuplicate(int $userId, int $distributionId, ?int $exceptReportId = null): void
    {
        $existing = Report::query()
            ->where('user_id', $userId)
            ->where('distribution_id', $distributionId)
            ->whereNotIn('status', Report::FINAL_STATUSES)
            ->when($exceptReportId, fn ($query) => $query->whereKeyNot($exceptReportId))
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'distribution_id' => "Anda masih memiliki laporan aktif (#{$existing->id}) untuk distribusi ini. Silakan perbarui laporan tersebut.",
            ]);
        }
    }

    private function ensureNotFinal(Report $report): void
    {
        if ($report->isFinal()) {
            throw new ReportWorkflowException('Laporan berstatus "'.$report->statusLabel().'" sudah tidak dapat diubah.');
        }
    }

    /**
     * Mengunci baris laporan selama transaksi agar dua pengguna tidak mengubah
     * status laporan yang sama secara bersamaan berdasarkan data yang sudah usang.
     */
    private function lock(Report $report): Report
    {
        return Report::whereKey($report->getKey())->lockForUpdate()->firstOrFail();
    }
}
