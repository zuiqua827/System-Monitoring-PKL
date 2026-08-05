<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for creating a new Penempatan PKL.
 */
class StorePenempatanPKLRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periode_pkl_id' => ['required', 'integer', 'exists:periode_pkl,id'],
            'guru_id' => ['required', 'integer', 'exists:guru,id'],
            'dudi_id' => ['required', 'integer', 'exists:dudi,id'],
            'siswa_id' => [
                'required', 
                'integer', 
                'exists:siswa,id',
                Rule::unique('penempatan_pkl', 'siswa_id')
                    ->where('periode_pkl_id', $this->periode_pkl_id)
                    ->whereNull('deleted_at')
            ],
            'nomor_surat' => ['nullable', 'string', 'max:100', 'unique:penempatan_pkl,nomor_surat'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'string', 'in:pending,aktif,selesai,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'periode_pkl_id' => 'Periode PKL',
            'guru_id' => 'Guru Pembimbing',
            'dudi_id' => 'Perusahaan/DUDI',
            'siswa_id' => 'Siswa',
            'nomor_surat' => 'Nomor Surat',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
            'status' => 'Status',
            'catatan' => 'Catatan',
        ];
    }
}
