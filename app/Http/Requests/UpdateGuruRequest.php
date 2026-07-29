<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating an existing Guru.
 *
 * Validates both User account fields (email) and Guru entity fields.
 * Ignores the current record for unique constraints.
 */
class UpdateGuruRequest extends FormRequest
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
        /** @var \App\Models\Guru|string|null $guru */
        $guru = $this->route('guru');

        $guruId = $guru instanceof \App\Models\Guru ? $guru->id : $guru;
        $userId = $guru instanceof \App\Models\Guru ? $guru->user_id : null;

        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'nip' => ['required', 'string', 'max:30', Rule::unique('guru', 'nip')->ignore($guruId)],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'string', 'in:L,P'],
            'no_hp' => ['nullable', 'string', 'max:20'],
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
            'email' => 'Email Login',
            'nip' => 'NIP',
            'nama' => 'Nama Guru',
            'jenis_kelamin' => 'Jenis Kelamin',
            'no_hp' => 'No. HP',
            'alamat' => 'Alamat',
        ];
    }
}
