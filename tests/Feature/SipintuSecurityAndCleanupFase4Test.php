<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Services\Interfaces\DudiServiceInterface;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::DUDI->value);
});

it('B2: DudiService::store never uses hardcoded password default and forces must_change_password', function (): void {
    /** @var DudiServiceInterface $dudiService */
    $dudiService = app(DudiServiceInterface::class);

    $dudi = $dudiService->store([
        'email' => 'dudi.randompass@company.test',
        'nama_perusahaan' => 'PT Random Password Test',
        'penanggung_jawab' => 'Ibu Rahma',
        'no_telepon' => '081234567800',
        'alamat' => 'Jepara',
        'status_aktif' => true,
    ]);

    expect($dudi->user)->not->toBeNull()
        ->and($dudi->user->must_change_password)->toBeTrue()
        ->and(Hash::check('password', (string) $dudi->user->password))->toBeFalse();
});

it('B3: API_INTEGRATION_GUIDE.md uses safe placeholders instead of realistic credentials', function (): void {
    $guide = file_get_contents(base_path('API_INTEGRATION_GUIDE.md'));

    expect($guide)->toBeString()
        ->not->toContain('app_mecmvhpduc8e')
        ->not->toContain('sec_uEr8wGucp1jda8Ls6qOBsW03HrYVj6UK')
        ->not->toContain('app_sijuna_dev')
        ->not->toContain('sec_xyz1234567890')
        ->toContain('<CLIENT_ID_ANDA>')
        ->toContain('<CLIENT_SECRET_ANDA>');
});

it('B4: SipintuSsoController aborts cleanly in production when api_url is missing or points to localhost', function (): void {
    $originalEnv = app()->environment();

    try {
        app()->detectEnvironment(static fn (): string => 'production');

        // 1. Empty api_url in production
        config([
            'services.sipintu.api_url' => '',
            'services.sipintu.base_url' => null,
            'services.sipintu.client_id' => 'prod_client',
            'services.sipintu.client_secret' => 'prod_secret',
        ]);

        $this->get(route('auth.sipintu.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        expect(session('error'))->toContain('Konfigurasi SSO SiPintu belum lengkap');

        $this->get('/oauth/callback?code=some_code')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        expect(session('error'))->toContain('Konfigurasi SSO SiPintu belum lengkap');

        // 2. Localhost fallback accidentally present in production
        config([
            'services.sipintu.api_url' => 'http://localhost:8000',
        ]);

        $this->get(route('auth.sipintu.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        expect(session('error'))->toContain('Konfigurasi SSO SiPintu belum lengkap');
    } finally {
        app()->detectEnvironment(static fn () => $originalEnv);
    }
});

it('B6 & B7: SiPintuService docblock matches smkn1bangsri.sch.id and unused LoginRequest is removed', function (): void {
    $serviceSource = file_get_contents(app_path('Services/SiPintuService.php'));

    expect($serviceSource)->toBeString()
        ->not->toContain('smk1bangsri.sch.id')
        ->toContain('smkn1bangsri.sch.id')
        ->and(file_exists(app_path('Http/Requests/Auth/LoginRequest.php')))->toBeFalse();
});

it('K1 & K2: .gitignore ignores nested node_modules and root repo is clean from one-off scripts/artifacts', function (): void {
    $gitignore = file_get_contents(base_path('.gitignore'));

    expect($gitignore)->toBeString()
        ->toContain('node_modules/')
        ->not->toContain("\n/node_modules\n");

    $leftover = array_filter(
        scandir(base_path()) ?: [],
        static fn (string $name): bool => $name !== 'phpstan.neon' && (bool) preg_match('/^(_|fix_|check_|copy_|inspect_|scan_|test_export|update_|verify_|phpstan|routes\.json$|blades\.txt$)/', $name),
    );

    expect($leftover)->toBeEmpty()
        ->and(is_dir(base_path('scripts')))->toBeTrue()
        ->and(file_exists(base_path('scripts/_probe_api.php')))->toBeTrue();
});
