<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'foto_pulang' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'foto_base64' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'lokasi_pulang' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'foto_pulang' => 'Foto Pulang',
            'foto_base64' => 'Foto Kamera',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'accuracy' => 'Akurasi GPS',
            'lokasi_pulang' => 'Lokasi Pulang',
        ];
    }

    /**
     * @param string|null $key
     * @param mixed $default
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        // If base64 photo is provided, remove foto_pulang (file upload) validation
        if (!empty($data['foto_base64'])) {
            $data['foto_pulang'] = null;
        }

        return $data;
    }
}
