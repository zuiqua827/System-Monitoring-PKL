<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for Check In.
 *
 * Supports:
 * - Camera capture (foto_base64) or file upload (foto_masuk)
 * - GPS coordinates (latitude, longitude, accuracy)
 */
class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    // ============================================================
    // VALIDASI FORM PRESENSI CHECK-IN SISWA
    // ============================================================
    // Mendukung dua input utama:
    // 1. Foto Selfie Kamera: Dikirim dalam format string Base64 dari canvas browser.
    // 2. Koordinat Geolocation: Latitude (-90 s.d 90) dan Longitude (-180 s.d 180)
    //    yang diperoleh dari GPS perangkat siswa untuk dihitung jarak radiusnya.
    // ============================================================

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'foto_masuk' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'foto_base64' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'lokasi_masuk' => ['nullable', 'string', 'max:500'],
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
            'foto_masuk' => 'Foto Masuk',
            'foto_base64' => 'Foto Kamera',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'accuracy' => 'Akurasi GPS',
            'lokasi_masuk' => 'Lokasi Masuk',
        ];
    }

    /**
     * Get the validated data with proper handling of base64 photo.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        // If base64 photo is provided, remove foto_masuk (file upload) validation
        if (!empty($data['foto_base64'])) {
            $data['foto_masuk'] = null;
        }

        return $data;
    }
}
