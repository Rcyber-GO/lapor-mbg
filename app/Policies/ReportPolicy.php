<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Hak akses terhadap laporan. Pelapor hanya boleh mengakses laporan miliknya;
 * Pemantau dan Koordinator Komunitas boleh mengakses semua laporan untuk peninjauan.
 */
class ReportPolicy
{
    /**
     * Daftar seluruh laporan masuk (bukan hanya milik sendiri).
     */
    public function viewAny(User $user): bool
    {
        return $user->isPemantau() || $user->isKoordinator();
    }

    public function view(User $user, Report $report): Response
    {
        return $this->ownerOrStaff($user, $report);
    }

    public function viewEvidence(User $user, Report $report): Response
    {
        return $this->ownerOrStaff($user, $report);
    }

    /**
     * Memperbaiki isi laporan hanya oleh pemiliknya. Kapan masih boleh
     * (BR-01) ditentukan ReportWorkflow.
     */
    public function update(User $user, Report $report): Response
    {
        if ($report->user_id === $user->id) {
            return Response::allow();
        }

        // Staf boleh melihat laporan tetapi tidak mengubah isinya.
        return $this->viewAny($user) ? Response::deny() : Response::denyAsNotFound();
    }

    /**
     * Mengubah status dan memberi catatan peninjauan. Kapan boleh dilakukan
     * (status yang sah) ditentukan ReportWorkflow.
     */
    public function review(User $user, Report $report): bool
    {
        return $user->isPemantau();
    }

    /**
     * Mencatat penerusan informasi kepada pihak terkait. Kapan boleh (BR-04)
     * ditentukan ReportWorkflow.
     */
    public function forward(User $user, Report $report): bool
    {
        return $user->isKoordinator();
    }

    /**
     * Laporan orang lain dijawab 404, bukan 403, agar keberadaannya tidak bocor.
     */
    private function ownerOrStaff(User $user, Report $report): Response
    {
        if ($this->viewAny($user) || $report->user_id === $user->id) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
