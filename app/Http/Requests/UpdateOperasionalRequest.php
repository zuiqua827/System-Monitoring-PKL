<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating DUDI Operational Settings.
 */
class UpdateOperasionalRequest extends FormRequest
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
            'jam_masuk'                => ['required', 'date_format:H:i'],
            'batas_terlambat'          => ['required', 'date_format:H:i', 'after_or_equal:jam_masuk'],
            'batas_sangat_terlambat'   => ['required', 'date_format:H:i', 'after_or_equal:batas_terlambat'],
            'jam_pulang'               => ['required', 'date_format:H:i', 'after:batas_sangat_terlambat'],
            'hari_operasional'         => ['required', 'array', 'min:1'],
            'hari_operasional.*'       => ['string', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'jam_masuk'              => 'Jam Masuk',
            'batas_terlambat'        => 'Batas Terlambat',
            'batas_sangat_terlambat' => 'Batas Sangat Terlambat',
            'jam_pulang'             => 'Jam Pulang',
            'hari_operasional'       => 'Hari Operasional',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hari_operasional.required' => 'Pilih minimal satu hari operasional.',
            'hari_operasional.min'      => 'Pilih minimal satu hari operasional.',
            'batas_terlambat.after_or_equal' => 'Batas Terlambat harus sama atau setelah Jam Masuk.',
            'batas_sangat_terlambat.after_or_equal' => 'Batas Sangat Terlambat harus sama atau setelah Batas Terlambat.',
            'jam_pulang.after' => 'Jam Pulang harus setelah Batas Sangat Terlambat.',
        ];
    }
}
