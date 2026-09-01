<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates and authenticates a PKL user login request.
 *
 * This is the shared entry point for Siswa, Guru, and DUDI on the /login page.
 * The role is determined by the selected tab:
 *  - "siswa": email sekolah ([NIS]@smkn1bangsri.sch.id) + password
 *  - "guru":  email + password
 *  - "dudi":  email + password
 *
 * Cross-role login is prevented: a user may only log in via the tab matching
 * their assigned role. Super Admin is not allowed here.
 */
class PklLoginRequest extends FormRequest
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
        $role = $this->input('role', 'siswa');

        $rules = [
            'role' => ['required', 'string', 'in:siswa,guru,dudi'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        if ($role === 'siswa') {
            $rules['email'][] = 'regex:/^[0-9]+@smkn1bangsri\.sch\.id$/';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.regex' => 'Format email siswa harus menggunakan NIS (contoh: 1234@smkn1bangsri.sch.id).',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials based on the selected role.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $role = (string) $this->input('role');

        if ($role === 'siswa') {
            $this->authenticateSiswa();

            return;
        }

        $this->authenticateEmailRole($role);
    }

    /**
     * Authenticate a Siswa using email sekolah + password.
     *
     * @throws ValidationException
     */
    private function authenticateSiswa(): void
    {
        $email = (string) $this->input('email');
        $password = (string) $this->input('password');

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $email)
            ->with('siswa')
            ->first();

        if ($user === null || $user->siswa === null) {
            $this->throwFailedLogin('email');
        }

        if (! $user->hasRole(UserRole::SISWA->value)) {
            $this->throwCrossRole('email');
        }

        $authenticated = Auth::attempt(
            ['email' => $email, 'password' => $password],
            $this->boolean('remember'),
        );

        if (! $authenticated) {
            $this->throwFailedLogin('email');
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Authenticate Guru or DUDI using email + password.
     *
     * @throws ValidationException
     */
    private function authenticateEmailRole(string $role): void
    {
        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->throwFailedLogin('email');
        }

        /** @var User $user */
        $user = Auth::user();

        $expectedRole = $role === 'guru'
            ? UserRole::GURU->value
            : UserRole::DUDI->value;

        if (! $user->hasRole($expectedRole)) {
            Auth::logout();

            $this->session()->regenerate();

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun tidak terdaftar untuk role yang dipilih.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Throw a generic failed-login validation error.
     *
     * @throws ValidationException
     */
    private function throwFailedLogin(string $field): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            $field => trans('auth.failed'),
        ]);
    }

    /**
     * Throw a cross-role validation error.
     *
     * @throws ValidationException
     */
    private function throwCrossRole(string $field): never
    {
        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            $field => 'Akun tidak terdaftar untuk role yang dipilih.',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = (string) $this->input('email');

        return Str::transliterate(Str::lower($identifier).'|'.$this->ip());
    }
}
