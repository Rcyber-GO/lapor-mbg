<?php

namespace App\Observers;

use App\Models\Report;
use Illuminate\Support\Facades\Auth;

/**
 * Mencatat setiap perubahan status laporan agar dapat diperiksa kembali.
 * Observer hanya merespons perubahan model; keputusan boleh/tidaknya perubahan
 * sudah dibuat ReportPolicy dan ReportWorkflow sebelum model disimpan.
 *
 * Catatan: event model tidak terpicu oleh mass update (Report::where()->update()),
 * karena itu status selalu diubah lewat $report->save() di ReportWorkflow.
 */
class ReportObserver
{
    public function created(Report $report): void
    {
        $report->statusLogs()->create([
            'user_id' => Auth::id(),
            'from_status' => null,
            'to_status' => $report->status,
        ]);
    }

    public function updated(Report $report): void
    {
        // Pada event "updated", getOriginal() masih berisi nilai sebelum disimpan.
        if (! $report->wasChanged('status')) {
            return;
        }

        $report->statusLogs()->create([
            'user_id' => Auth::id(),
            'from_status' => $report->getOriginal('status'),
            'to_status' => $report->status,
        ]);
    }
}
