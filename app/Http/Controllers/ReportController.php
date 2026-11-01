<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Report;
use App\Models\School;
use App\Services\ReportWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Daftar laporan milik pelapor yang sedang login saja.
     */
    public function index(Request $request): View
    {
        $reports = $request->user()->reports()
            ->with(['school:id,name', 'distribution:id,distribution_date'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'statuses' => Report::STATUS_LABELS,
        ]);
    }

    /**
     * Seluruh laporan masuk untuk Pemantau dan Koordinator Komunitas
     * (dibatasi middleware can:viewAny pada route).
     */
    public function incoming(): View
    {
        $reports = Report::query()
            ->with(['school:id,name', 'distribution:id,distribution_date', 'user:id,name'])
            ->latest()
            ->latest('id')
            ->paginate(10);

        return view('reports.incoming', ['reports' => $reports]);
    }

    public function create(): View
    {
        return view('reports.create', [
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'relations' => Report::RELATIONS,
        ]);
    }

    public function store(StoreReportRequest $request, ReportWorkflow $workflow): RedirectResponse
    {
        $report = $workflow->submit($request->user(), $request->safe()->except('evidence'), $request->file('evidence'));

        return redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil dikirim.');
    }

    /**
     * Hanya pemilik (middleware can:update); BR-01 dicek juga di sini agar
     * pelapor tidak membuka form yang pasti ditolak saat disimpan.
     */
    public function edit(Report $report): View|RedirectResponse
    {
        if (! $report->isEditable()) {
            return redirect()->route('reports.show', $report)
                ->with('error', 'Laporan berstatus "'.$report->statusLabel().'" sedang atau sudah ditinjau sehingga tidak dapat diubah.');
        }

        return view('reports.edit', [
            'report' => $report,
            'schools' => School::orderBy('name')->get(['id', 'name']),
            'relations' => Report::RELATIONS,
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report, ReportWorkflow $workflow): RedirectResponse
    {
        $wasIncomplete = $report->status === Report::STATUS_PERLU_DILENGKAPI;

        $workflow->update($report, $request->safe()->except('evidence'), $request->file('evidence'));

        return redirect()->route('reports.show', $report)
            ->with('success', $wasIncomplete ? 'Laporan diperbarui dan dikirim ulang untuk ditinjau.' : 'Laporan berhasil diperbarui.');
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['school', 'distribution', 'user:id,name', 'notes' => fn ($query) => $query->with('user:id,name')->oldest('id')]);

        return view('reports.show', ['report' => $report]);
    }

    /**
     * Bukti disajikan lewat controller (bukan URL publik) agar hak akses selalu dicek.
     */
    public function evidence(Report $report): StreamedResponse
    {
        Gate::authorize('viewEvidence', $report);

        $disk = Storage::disk(Report::EVIDENCE_DISK);
        abort_unless($disk->exists($report->evidence_path), 404);

        return $disk->response($report->evidence_path, $report->evidence_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
