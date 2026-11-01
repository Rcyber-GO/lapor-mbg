<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Rekap sederhana kondisi laporan untuk Pemantau dan Koordinator Komunitas
 * (dibatasi middleware can:viewAny pada route).
 */
class RecapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'school_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [], [
            'date_from' => 'tanggal awal',
            'date_to' => 'tanggal akhir',
        ]);

        $rows = $this->perSchool($filters);

        return view('recap.index', [
            'rows' => $rows,
            'summary' => [
                'total' => $rows->sum('total'),
                'in_progress' => $rows->sum('in_progress'),
                'to_forward' => $rows->sum('to_forward'),
                'forwarded' => $rows->sum('forwarded'),
                'affected' => $rows->sum('affected'),
            ],
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Satu query agregat per sekolah; kartu ringkasan dijumlahkan dari baris ini
     * sehingga angka kartu dan tabel selalu konsisten.
     */
    private function perSchool(array $filters): Collection
    {
        $inProgress = implode(',', array_fill(0, count(Report::IN_PROGRESS_STATUSES), '?'));

        return Report::query()
            ->filter($filters)
            ->join('schools', 'schools.id', '=', 'reports.school_id')
            ->groupBy('reports.school_id', 'schools.name')
            ->selectRaw('schools.name as school_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN reports.status IN ($inProgress) THEN 1 ELSE 0 END) as in_progress", Report::IN_PROGRESS_STATUSES)
            ->selectRaw('SUM(CASE WHEN reports.status = ? THEN 1 ELSE 0 END) as to_forward', [Report::STATUS_PERLU_DITERUSKAN])
            ->selectRaw('SUM(CASE WHEN reports.status = ? THEN 1 ELSE 0 END) as forwarded', [Report::STATUS_DITERUSKAN])
            ->selectRaw('SUM(CASE WHEN reports.status = ? THEN 1 ELSE 0 END) as finished', [Report::STATUS_SELESAI])
            ->selectRaw('SUM(reports.affected_count) as affected')
            ->orderByDesc('total')
            ->orderBy('schools.name')
            ->toBase()
            ->get()
            ->map(fn ($row) => (object) [
                'school_name' => $row->school_name,
                'total' => (int) $row->total,
                'in_progress' => (int) $row->in_progress,
                'to_forward' => (int) $row->to_forward,
                'forwarded' => (int) $row->forwarded,
                'finished' => (int) $row->finished,
                'affected' => (int) $row->affected,
            ]);
    }
}
