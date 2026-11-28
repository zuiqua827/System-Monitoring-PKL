<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating an existing Penilaian.
 */
class UpdatePenilaianRequest extends FormRequest
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
            'status' => ['sometimes', 'required', 'in:draft,final'],
            'nilai_kehadiran' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_kerjasama' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'nilai_komunikasi' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
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
}
