<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating an existing Jurusan.
 */
class UpdateJurusanRequest extends FormRequest
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
        $jurusan = $this->route('jurusan');
        $jurusanId = $jurusan instanceof \App\Models\Jurusan ? $jurusan->id : $jurusan;

        return [
            'kode' => ['required', 'string', 'max:20', Rule::unique('jurusan', 'kode')->ignore($jurusanId)],
            'nama' => ['required', 'string', 'max:100', Rule::unique('jurusan', 'nama')->ignore($jurusanId)],
            'deskripsi' => ['nullable', 'string'],
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
            'kode' => 'Kode Jurusan',
            'nama' => 'Nama Jurusan',
            'deskripsi' => 'Deskripsi',
        ];
    }
}
