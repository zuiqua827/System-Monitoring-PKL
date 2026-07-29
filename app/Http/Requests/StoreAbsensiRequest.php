<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AbsensiStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for creating a new Absensi.
 */
class StoreAbsensiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'penempatan_pkl_id' => ['required', 'integer', 'exists:penempatan_pkl,id'],
            'tanggal' => ['required', 'date'],
            'jam_masuk' => ['nullable', 'date_format:H:i:s'],
            'jam_pulang' => ['nullable', 'date_format:H:i:s', 'after:jam_masuk'],
            'status' => ['required', 'string', Rule::in(AbsensiStatus::values())],
            'lokasi_masuk' => ['nullable', 'string', 'max:500'],
            'lokasi_pulang' => ['nullable', 'string', 'max:500'],
            'foto_masuk' => ['nullable', 'string', 'max:255'],
            'foto_pulang' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'latitude_masuk' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_masuk' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude_keluar' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_keluar' => ['nullable', 'numeric', 'between:-180,180'],
            'device' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'penempatan_pkl_id' => 'Penempatan PKL',
            'tanggal' => 'Tanggal',
            'jam_masuk' => 'Jam Masuk',
            'jam_pulang' => 'Jam Pulang',
            'status' => 'Status',
            'lokasi_masuk' => 'Lokasi Masuk',
            'lokasi_pulang' => 'Lokasi Pulang',
            'foto_masuk' => 'Foto Masuk',
            'foto_pulang' => 'Foto Pulang',
            'keterangan' => 'Keterangan',
            'latitude_masuk' => 'Latitude Masuk',
            'longitude_masuk' => 'Longitude Masuk',
            'latitude_keluar' => 'Latitude Keluar',
            'longitude_keluar' => 'Longitude Keluar',
        ];
    }
}

