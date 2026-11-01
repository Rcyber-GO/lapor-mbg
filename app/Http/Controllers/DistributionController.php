<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributionRequest;
use App\Models\Distribution;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(Request $request): View
    {
        $distributions = Distribution::query()
            ->with('school')
            ->when($request->filled('school_id'), fn ($query) => $query->where('school_id', $request->school_id))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('distribution_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('distribution_date', '<=', $request->date_to))
            ->orderByDesc('distribution_date')
            ->orderBy('school_id')
            ->paginate(10)
            ->withQueryString();

        return view('distributions.index', [
            'distributions' => $distributions,
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('distributions.form', [
            'distribution' => new Distribution,
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(DistributionRequest $request): RedirectResponse
    {
        Distribution::create($request->validated());

        return redirect()->route('distributions.index')->with('success', 'Distribusi berhasil ditambahkan.');
    }

    public function edit(Distribution $distribution): View
    {
        return view('distributions.form', [
            'distribution' => $distribution,
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(DistributionRequest $request, Distribution $distribution): RedirectResponse
    {
        $distribution->update($request->validated());

        return redirect()->route('distributions.index')->with('success', 'Data distribusi berhasil diperbarui.');
    }

    public function destroy(Distribution $distribution): RedirectResponse
    {
        $distribution->delete();

        return redirect()->route('distributions.index')->with('success', 'Distribusi berhasil dihapus.');
    }
}
