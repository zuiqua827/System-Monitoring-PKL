<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a new Aktivitas.
 */
class StoreAktivitasRequest extends FormRequest
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
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i', 'after_or_equal:jam_mulai'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'hasil' => ['nullable', 'string'],
            'kendala' => ['nullable', 'string'],
            'solusi' => ['nullable', 'string'],
            'foto_kegiatan' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
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
            'jam_mulai' => 'Jam Mulai',
            'jam_selesai' => 'Jam Selesai',
            'judul' => 'Judul Aktivitas',
            'deskripsi' => 'Deskripsi',
            'hasil' => 'Hasil',
            'kendala' => 'Kendala',
            'solusi' => 'Solusi',
            'foto_kegiatan' => 'Foto Kegiatan',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'foto_kegiatan.image' => 'Foto kegiatan harus berupa gambar.',
            'foto_kegiatan.mimes' => 'Foto kegiatan harus berformat jpeg, jpg, atau png.',
            'foto_kegiatan.max' => 'Foto kegiatan maksimal 2 MB.',
        ];
    }
}

