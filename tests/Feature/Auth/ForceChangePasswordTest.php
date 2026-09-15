<?php

declare(strict_types=1);

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('force change password screen can be rendered for user who must change password', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = true;
    $user->password = Hash::make('old-password');
    $user->save();

    $response = $this->actingAs($user)->get('/force-change-password');

    $response->assertStatus(200);
    $response->assertViewIs('auth.force-change-password');
});

test('force change password screen redirects if user does not need to change password', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = false;
    $user->save();

    $response = $this->actingAs($user)->get('/force-change-password');

    $response->assertRedirect('/siswa/dashboard');
});

test('user can change password with minimum 8 characters without complexity requirements', function ($newPassword) {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = true;
    $user->password = Hash::make('old-password');
    $user->save();

    $response = $this->actingAs($user)->post('/force-change-password', [
        'current_password' => 'old-password',
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertRedirect('/siswa/dashboard');
    $response->assertSessionHasNoErrors();

    $user->refresh();
    
    $this->assertFalse((bool) $user->must_change_password);
    $this->assertTrue(Hash::check($newPassword, $user->password));
})->with([
    ['password'], // 8 chars, only lowercase letters
    ['12345678'], // 8 chars, only numbers
    ['abcdefgh'], // 8 chars, only lowercase letters
    ['simongan'], // 8 chars
    ['longpassword123'], // more than 8 characters
]);

test('password cannot be less than 8 characters', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = true;
    $user->password = Hash::make('old-password');
    $user->save();

    $response = $this->actingAs($user)->post('/force-change-password', [
        'current_password' => 'old-password',
        'password' => '1234567', // 7 chars
        'password_confirmation' => '1234567',
    ]);

    $response->assertSessionHasErrors('password');
    
    $user->refresh();
    $this->assertTrue((bool) $user->must_change_password);
});

test('password confirmation must match', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = true;
    $user->password = Hash::make('old-password');
    $user->save();

    $response = $this->actingAs($user)->post('/force-change-password', [
        'current_password' => 'old-password',
        'password' => 'password123',
        'password_confirmation' => 'password456',
    ]);

    $response->assertSessionHasErrors('password');
    
    $user->refresh();
    $this->assertTrue((bool) $user->must_change_password);
});

test('current password must be valid', function () {
    $siswa = Siswa::factory()->create();
    $user = $siswa->user;
    $user->must_change_password = true;
    $user->password = Hash::make('old-password');
    $user->save();

    $response = $this->actingAs($user)->post('/force-change-password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('current_password');
    
    $user->refresh();
    $this->assertTrue((bool) $user->must_change_password);
});
