<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a new Kelas.
 */
class StoreKelasRequest extends FormRequest
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
            'jurusan_id' => ['required', 'integer', 'exists:jurusan,id'],
            'nama' => [
                'required', 
                'string', 
                'max:100',
                \Illuminate\Validation\Rule::unique('kelas', 'nama')
                    ->where('jurusan_id', $this->jurusan_id)
                    ->where('tahun_ajaran', $this->tahun_ajaran)
                    ->whereNull('deleted_at')
            ],
            'tingkat' => ['required', 'integer', 'in:10,11,12'],
            'tahun_ajaran' => ['required', 'string', 'max:9'],
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
            'jurusan_id' => 'Jurusan',
            'nama' => 'Nama Kelas',
            'tingkat' => 'Tingkat',
            'tahun_ajaran' => 'Tahun Ajaran',
        ];
    }
}
