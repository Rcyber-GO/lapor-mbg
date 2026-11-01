<?php

namespace App\Http\Requests;

use App\Models\Forwarding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreForwardingRequest extends FormRequest
{
    /**
     * Hak akses sudah dijaga middleware can:forward pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_type' => ['required', Rule::in(array_keys(Forwarding::RECIPIENT_TYPES))],
            'recipient_name' => ['required', 'string', 'max:150'],
            // Penerusan tidak mungkin terjadi sebelum laporan dikirim atau di masa depan.
            'forwarded_at' => [
                'required',
                'date',
                'after_or_equal:'.$this->route('report')->created_at->toDateString(),
                'before_or_equal:today',
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient_type' => 'jenis pihak',
            'recipient_name' => 'nama pihak',
            'forwarded_at' => 'tanggal diteruskan',
            'note' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'forwarded_at.after_or_equal' => 'Tanggal diteruskan tidak boleh sebelum laporan dikirim.',
            'forwarded_at.before_or_equal' => 'Tanggal diteruskan tidak boleh melebihi hari ini.',
        ];
    }
}
