<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates updates to a PenempatanPKL work mode (WFO/WFH) and geofence policy.
 */
class UpdatePenempatanLocationRequest extends FormRequest
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
            'mode_kerja' => ['required', 'string', 'in:WFO,WFH'],
            'validasi_lokasi' => ['required', 'boolean'],
            'wfh_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'wfh_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'wfh_radius' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'wfh_alamat' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'mode_kerja' => 'Mode Kerja',
            'validasi_lokasi' => 'Validasi Lokasi (Geofence)',
            'wfh_latitude' => 'Latitude WFH',
            'wfh_longitude' => 'Longitude WFH',
            'wfh_radius' => 'Radius WFH',
            'wfh_alamat' => 'Alamat/Catatan Lokasi WFH',
            'reason' => 'Alasan Perubahan',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $modeKerja = strtoupper((string) $this->input('mode_kerja', 'WFO'));
            $validasiLokasi = filter_var($this->input('validasi_lokasi'), FILTER_VALIDATE_BOOLEAN);

            if ($modeKerja === 'WFH' && $validasiLokasi) {
                if ($this->input('wfh_latitude') === null || $this->input('wfh_latitude') === '') {
                    $validator->errors()->add('wfh_latitude', 'Latitude WFH wajib diisi saat mode WFH dengan validasi lokasi aktif.');
                }
                if ($this->input('wfh_longitude') === null || $this->input('wfh_longitude') === '') {
                    $validator->errors()->add('wfh_longitude', 'Longitude WFH wajib diisi saat mode WFH dengan validasi lokasi aktif.');
                }
                if ($this->input('wfh_radius') === null || $this->input('wfh_radius') === '') {
                    $validator->errors()->add('wfh_radius', 'Radius WFH wajib diisi saat mode WFH dengan validasi lokasi aktif.');
                }
            }
        });
    }
}
