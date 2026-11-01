<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $file = $request->file('evidence');
        // store() memberi nama acak; nama asli hanya disimpan untuk ditampilkan.
        $path = $file->store(Report::EVIDENCE_DIRECTORY, Report::EVIDENCE_DISK);

        try {
            $report = $request->user()->reports()->make($request->safe()->except('evidence'));
            $report->evidence_path = $path;
            $report->evidence_name = Str::limit($file->getClientOriginalName(), 255, '');
            $report->save();
        } catch (Throwable $e) {
            // Jangan tinggalkan file yatim bila laporan gagal tersimpan.
            Storage::disk(Report::EVIDENCE_DISK)->delete($path);

            throw $e;
        }

        return redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil dikirim.');
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['school', 'distribution', 'user:id,name']);

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
