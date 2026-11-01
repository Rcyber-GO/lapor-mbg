<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolRequest;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $schools = School::query()
            ->withCount('distributions')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('level'), fn ($query) => $query->where('level', $request->level))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('schools.index', [
            'schools' => $schools,
            'levels' => School::LEVELS,
        ]);
    }

    public function create(): View
    {
        return view('schools.form', [
            'school' => new School,
            'levels' => School::LEVELS,
        ]);
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        School::create($request->validated());

        return redirect()->route('schools.index')->with('success', 'Sekolah berhasil ditambahkan.');
    }

    public function edit(School $school): View
    {
        return view('schools.form', [
            'school' => $school,
            'levels' => School::LEVELS,
        ]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        return redirect()->route('schools.index')->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(School $school): RedirectResponse
    {
        // Dicek di aplikasi agar pengguna mendapat pesan yang jelas; FK restrict
        // di database tetap menjadi pengaman terakhir.
        if ($school->distributions()->exists()) {
            return back()->with('error', 'Sekolah tidak dapat dihapus karena masih memiliki data distribusi.');
        }

        $school->delete();

        return redirect()->route('schools.index')->with('success', 'Sekolah berhasil dihapus.');
    }
}
