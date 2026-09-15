<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\DudiServiceInterface;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('dudi can authenticate using dudi tab with email and default password', function () {
    $dudi = Dudi::factory()->create([
        'status_aktif' => true,
    ]);
    $user = $dudi->user;

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dudi/dashboard');
});

test('dudi cannot authenticate with invalid password', function () {
    $dudi = Dudi::factory()->create([
        'status_aktif' => true,
    ]);
    $user = $dudi->user;

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('dudi cannot authenticate if dudi account is inactive', function () {
    $dudi = Dudi::factory()->create([
        'status_aktif' => false,
    ]);
    $user = $dudi->user;

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('user with guru role cannot authenticate via dudi tab', function () {
    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('guru can authenticate using guru tab', function () {
    $guru = Guru::factory()->create();
    $user = $guru->user;

    $response = $this->post('/login', [
        'role' => 'guru',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/guru/dashboard');
});

test('siswa can authenticate using siswa tab', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->password = Hash::make('password');
    $user->save();

    $response = $this->post('/login', [
        'role' => 'siswa',
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/siswa/dashboard');
});

test('new dudi created via dudi service can authenticate using default password', function () {
    /** @var DudiServiceInterface $service */
    $service = app(DudiServiceInterface::class);

    $email = 'mitra_' . uniqid() . '@example.com';
    $dudi = $service->store([
        'email' => $email,
        'nama_perusahaan' => 'PT Mitra Baru',
        'penanggung_jawab' => 'Bapak Mitra',
        'no_telepon' => '081234567890',
        'alamat' => 'Jl. Industri No. 10',
        'status_aktif' => true,
    ]);

    $this->assertNotNull($dudi->user);
    $this->assertTrue($dudi->user->hasRole(UserRole::DUDI->value));
    $this->assertTrue(Hash::check('password', $dudi->user->password));

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => $email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($dudi->user);
    $response->assertRedirect('/dudi/dashboard');
});

test('tefa studio account can authenticate with tefa email and password', function () {
    $dudi = Dudi::factory()->create([
        'nama_perusahaan' => 'Tefa Studio',
        'status_aktif' => true,
    ]);
    $user = $dudi->user;
    $user->email = 'tefa@smkn1bangsri.sch.id';
    $user->password = Hash::make('password');
    $user->save();

    $response = $this->post('/login', [
        'role' => 'dudi',
        'email' => 'tefa@smkn1bangsri.sch.id',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dudi/dashboard');
});

test('users can logout', function () {
    $dudi = Dudi::factory()->create();
    $user = $dudi->user;

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
