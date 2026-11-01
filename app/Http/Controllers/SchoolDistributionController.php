<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\School;
use Illuminate\Http\JsonResponse;

class SchoolDistributionController extends Controller
{
    /**
     * Daftar distribusi satu sekolah untuk form laporan (dipanggil via fetch()).
     * Hanya distribusi yang sudah terjadi, dan hanya field yang dibutuhkan form.
     */
    public function __invoke(School $school): JsonResponse
    {
        $distributions = $school->distributions()
            ->whereDate('distribution_date', '<=', today())
            ->orderByDesc('distribution_date')
            ->get(['id', 'distribution_date', 'menu'])
            ->map(fn (Distribution $distribution): array => [
                'id' => $distribution->id,
                'date' => $distribution->distribution_date->toDateString(),
                'label' => $distribution->distribution_date->translatedFormat('d M Y').' · '.$distribution->menu,
            ]);

        return response()->json($distributions);
    }
}
