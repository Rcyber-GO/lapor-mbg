<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Distribution;
use App\Models\Report;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(): View
    {
        return view('reports.create', [
            'schools' => School::orderBy('name')->get(['id', 'name']),
            // Sementara semua distribusi dikirim ke form; akan diganti fetch() per sekolah.
            'distributions' => Distribution::with('school:id,name')->orderByDesc('distribution_date')->get(),
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
