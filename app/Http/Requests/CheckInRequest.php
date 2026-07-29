<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for Check In (Siswa).
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jam_masuk' => ['nullable', 'date_format:H:i:s'],
            'lokasi_masuk' => ['nullable', 'string', 'max:500'],
            'foto_masuk' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'latitude_masuk' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_masuk' => ['nullable', 'numeric', 'between:-180,180'],
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
            'jam_masuk' => 'Jam Masuk',
            'lokasi_masuk' => 'Lokasi Masuk',
            'foto_masuk' => 'Foto Masuk',
            'latitude_masuk' => 'Latitude Masuk',
            'longitude_masuk' => 'Longitude Masuk',
        ];
    }
}

