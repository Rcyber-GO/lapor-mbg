<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewReportRequest;
use App\Models\Report;
use App\Services\ReportWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Tindakan peninjauan oleh Pemantau Komunitas (dibatasi middleware can:review).
 */
class ReportReviewController extends Controller
{
    public function __construct(private ReportWorkflow $workflow) {}

    public function updateStatus(ReviewReportRequest $request, Report $report): RedirectResponse
    {
        $this->workflow->review($report, $request->user(), $request->validated('to_status'), $request->validated('note'));

        return redirect()->route('reports.show', $report)
            ->with('success', 'Status laporan diubah menjadi "'.Report::STATUS_LABELS[$request->validated('to_status')].'".');
    }

    public function storeNote(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate(['note' => ['required', 'string', 'max:2000']], [], ['note' => 'catatan']);

        $this->workflow->addNote($report, $request->user(), $validated['note']);

        return redirect()->route('reports.show', $report)->with('success', 'Catatan berhasil ditambahkan.');
    }
}
