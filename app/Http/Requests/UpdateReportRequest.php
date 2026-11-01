<?php

namespace App\Http\Requests;

/**
 * Sama dengan pembuatan laporan, kecuali bukti pendukung boleh dikosongkan
 * (bukti lama tetap dipakai).
 */
class UpdateReportRequest extends StoreReportRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['evidence'][0] = 'nullable';

        return $rules;
    }
}
