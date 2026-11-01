<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForwardingRequest;
use App\Models\Report;
use App\Services\ReportWorkflow;
use Illuminate\Http\RedirectResponse;

/**
 * Pencatatan penerusan informasi oleh Koordinator Komunitas (dibatasi middleware can:forward).
 */
class ForwardingController extends Controller
{
    public function store(StoreForwardingRequest $request, Report $report, ReportWorkflow $workflow): RedirectResponse
    {
        $workflow->recordForwarding($report, $request->user(), $request->validated());

        return redirect()->route('reports.show', $report)->with('success', 'Penerusan informasi berhasil dicatat.');
    }
}
