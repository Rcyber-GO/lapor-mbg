<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Dilempar ReportWorkflow bila suatu tindakan melanggar business rule.
 * Pengguna dikembalikan ke halaman sebelumnya dengan pesan; karena dilempar di
 * dalam transaksi, tidak ada perubahan data yang tersimpan.
 */
class ReportWorkflowException extends RuntimeException
{
    public function render(): RedirectResponse
    {
        return back()->with('error', $this->getMessage());
    }
}
