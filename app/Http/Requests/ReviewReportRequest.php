<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewReportRequest extends FormRequest
{
    /**
     * Hak akses sudah dijaga middleware can:review pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Hanya memastikan nilainya status yang dikenal; sah atau tidaknya
            // peralihan dari status saat ini diperiksa ReportWorkflow (BR-02).
            'to_status' => ['required', Rule::in(array_keys(Report::STATUS_LABELS))],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'to_status' => 'status tujuan',
            'note' => 'catatan',
        ];
    }
}
