<?php

namespace App\Http\Requests;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150', Rule::unique('schools', 'name')->ignore($this->route('school'))],
            'level' => ['required', Rule::in(School::LEVELS)],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama sekolah',
            'level' => 'jenjang',
            'address' => 'alamat',
            'district' => 'kecamatan',
        ];
    }
}
