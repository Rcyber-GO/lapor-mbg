<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistributionRequest extends FormRequest
{
    /**
     * Hak akses sudah dijaga middleware can:manage-master-data pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'distribution_date' => [
                'required',
                'date',
                // Satu sekolah hanya menerima satu distribusi per tanggal.
                Rule::unique('distributions', 'distribution_date')
                    ->where('school_id', $this->input('school_id'))
                    ->ignore($this->route('distribution')),
            ],
            'menu' => ['required', 'string', 'max:255'],
            'provider_name' => ['required', 'string', 'max:150'],
            'portion_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_id' => 'sekolah',
            'distribution_date' => 'tanggal distribusi',
            'menu' => 'menu',
            'provider_name' => 'nama SPPG',
            'portion_count' => 'jumlah porsi',
        ];
    }

    public function messages(): array
    {
        return [
            'distribution_date.unique' => 'Sekolah ini sudah memiliki distribusi pada tanggal tersebut.',
        ];
    }
}
