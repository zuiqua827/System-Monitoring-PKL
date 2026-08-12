<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a new Penilaian.
 */
class StorePenilaianRequest extends FormRequest
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
            'penempatan_pkl_id' => ['required', 'integer', 'exists:penempatan_pkl,id', 'unique:penilaian,penempatan_pkl_id'],
            'nilai_kehadiran' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_kerjasama' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_komunikasi' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_problem_solving' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_teknis' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_inisiatif' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string'],
            'catatan_guru' => ['nullable', 'string'],
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
            'nilai_kehadiran' => 'Nilai Kehadiran',
            'nilai_kerjasama' => 'Nilai Kerja Sama',
            'nilai_komunikasi' => 'Nilai Komunikasi',
            'nilai_problem_solving' => 'Nilai Problem Solving',
            'nilai_teknis' => 'Nilai Teknis',
            'nilai_inisiatif' => 'Nilai Inisiatif',
            'catatan' => 'Catatan',
            'catatan_guru' => 'Catatan Guru',
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
            'penempatan_pkl_id.unique' => 'Siswa pada Penempatan PKL ini sudah memiliki penilaian.',
        ];
    }
}
