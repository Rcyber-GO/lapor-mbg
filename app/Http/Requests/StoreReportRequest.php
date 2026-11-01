<?php

namespace App\Http\Requests;

use App\Models\Distribution;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReportRequest extends FormRequest
{
    /**
     * Hak akses sudah dijaga middleware can:create-report pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            // Distribusi harus milik sekolah yang dipilih. Dicek di server agar
            // manipulasi pilihan di browser tidak menghasilkan data yang janggal.
            'distribution_id' => [
                'required',
                'integer',
                Rule::exists('distributions', 'id')->where('school_id', $this->input('school_id')),
            ],
            'reporter_relation' => ['required', Rule::in(array_keys(Report::RELATIONS))],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'affected_count' => ['required', 'integer', 'min:1', 'max:5000'],
            'symptoms' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
        ];
    }

    /**
     * Kejadian setelah konsumsi tidak mungkin terjadi sebelum makanan didistribusikan.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['distribution_id', 'incident_date'])) {
                    return;
                }

                $distribution = Distribution::find($this->input('distribution_id'));

                if ($distribution && $distribution->distribution_date->gt($this->date('incident_date'))) {
                    $validator->errors()->add(
                        'incident_date',
                        'Tanggal kejadian tidak boleh sebelum tanggal distribusi ('.$distribution->distribution_date->translatedFormat('d M Y').').',
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'sekolah',
            'distribution_id' => 'distribusi',
            'reporter_relation' => 'hubungan dengan siswa',
            'incident_date' => 'tanggal kejadian',
            'affected_count' => 'perkiraan jumlah siswa terdampak',
            'symptoms' => 'keluhan',
            'description' => 'kronologi',
        ];
    }

    public function messages(): array
    {
        return [
            'distribution_id.exists' => 'Distribusi yang dipilih tidak sesuai dengan sekolah.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh melebihi hari ini.',
        ];
    }
}
