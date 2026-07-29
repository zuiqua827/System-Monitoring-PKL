<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for Check Out (Siswa).
 */
class CheckOutRequest extends FormRequest
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
            'jam_pulang' => ['nullable', 'date_format:H:i:s'],
            'lokasi_pulang' => ['nullable', 'string', 'max:500'],
            'foto_pulang' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'latitude_keluar' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_keluar' => ['nullable', 'numeric', 'between:-180,180'],
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
            'jam_pulang' => 'Jam Pulang',
            'lokasi_pulang' => 'Lokasi Pulang',
            'foto_pulang' => 'Foto Pulang',
            'latitude_keluar' => 'Latitude Keluar',
            'longitude_keluar' => 'Longitude Keluar',
        ];
    }
}

