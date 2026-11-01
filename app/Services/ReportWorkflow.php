<?php

namespace App\Services;

use App\Exceptions\ReportWorkflowException;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat perubahan status laporan dan penerapan business rule
 * alur laporan. Siapa yang boleh memanggil tiap tindakan dijaga ReportPolicy;
 * kelas ini menentukan kapan tindakan itu boleh terjadi.
 */
class ReportWorkflow
{
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
