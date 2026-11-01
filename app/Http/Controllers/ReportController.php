<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'statuses' => Report::STATUS_LABELS,
        ]);
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
        $report = $request->user()->reports()->create($request->validated());

        return redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil dikirim.');
    }

    public function show(Report $report): View
    {
        // Sementara hanya pemilik yang boleh melihat; aturan lengkap per peran
        // akan dipindahkan ke ReportPolicy.
        abort_unless($report->user_id === auth()->id(), 404);

        $report->load(['school', 'distribution']);

        return view('reports.show', ['report' => $report]);
    }
}
