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
use Illuminate\Validation\Rule;
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
    public function incoming(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Report::STATUS_LABELS))],
            'school_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [], [
            'q' => 'kata kunci',
            'date_from' => 'tanggal awal',
            'date_to' => 'tanggal akhir',
        ]);

        $reports = Report::query()
            ->with(['school:id,name', 'distribution:id,distribution_date', 'user:id,name'])
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(10)
            // Filter aktif ikut terbawa pada tautan halaman berikutnya.
            ->withQueryString();

        return view('reports.incoming', [
            'reports' => $reports,
            'statuses' => Report::STATUS_LABELS,
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
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

        $report->load([
            'school', 'distribution', 'user:id,name',
            'notes' => fn ($query) => $query->with('user:id,name')->oldest('id'),
            'forwardings' => fn ($query) => $query->with('user:id,name')->oldest('id'),
        ]);

        if (Gate::allows('viewAny', Report::class)) {
            $report->load(['statusLogs' => fn ($query) => $query->with('user:id,name')->oldest('id')]);
        }

        return view('reports.show', [
            'report' => $report,
            'related' => Gate::allows('viewAny', Report::class) ? $this->relatedTo($report) : null,
        ]);
    }

    /**
     * Keterkaitan laporan untuk peninjauan staf. Sengaja tanpa data pelapor:
     * yang dibutuhkan hanya gambaran kejadian pada distribusi/sekolah yang sama.
     */
    private function relatedTo(Report $report): array
    {
        $sameDistribution = Report::query()
            ->where('distribution_id', $report->distribution_id)
            ->whereKeyNot($report->id)
            ->latest('id')
            ->get(['id', 'status', 'affected_count', 'created_at']);

        return [
            'sameDistribution' => $sameDistribution,
            'sameSchoolCount' => Report::where('school_id', $report->school_id)->whereKeyNot($report->id)->count(),
        ];
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
