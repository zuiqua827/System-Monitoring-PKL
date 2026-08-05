<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating an existing Siswa.
 *
 * Validates both User account fields (email) and Siswa entity fields.
 * Ignores the current record for unique constraints.
 */
class UpdateSiswaRequest extends FormRequest
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
        /** @var Siswa|string|null $siswa */
        $siswa = $this->route('siswa');

        $siswaId = $siswa instanceof Siswa ? $siswa->id : $siswa;

        return [
            'class_id' => ['required', 'integer', 'exists:kelas,id'],
            'nis' => ['required', 'string', 'max:30', Rule::unique('siswa', 'nis')->ignore($siswaId)],
            'nisn' => ['nullable', 'string', 'max:30', Rule::unique('siswa', 'nisn')->ignore($siswaId)],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'string', 'in:L,P'],
            'tanggal_lahir' => ['nullable', 'date'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
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
            'class_id' => 'Kelas',
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'nama' => 'Nama Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tanggal_lahir' => 'Tanggal Lahir',
            'no_telepon' => 'No. Telepon',
            'alamat' => 'Alamat',
        ];
    }
}
