<?php

declare(strict_types=1);

use App\Exceptions\SiPintuApiException;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.sipintu.api_url' => 'https://sipintu.test',
        'services.sipintu.client_id' => 'test_client_id_123',
        'services.sipintu.client_secret' => 'test_client_secret_xyz',
        'services.sipintu.timeout' => 5,
        'services.sipintu.per_page' => null, // Default: do not force arbitrary per_page
    ]);
});

it('1. handles single page response with pagination metadata', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => '1001', 'nama' => 'Siswa 1'],
                ['nis' => '1002', 'nama' => 'Siswa 2'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 2,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toHaveCount(2)
        ->and($students[0]['nis'])->toBe('1001')
        ->and($students[1]['nis'])->toBe('1002');

    Http::assertSentCount(1);
});

it('2. fetches multiple pages sequentially and combines all records', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*page=1*' => Http::response([
            'data' => [
                ['nis' => '1001', 'nama' => 'Siswa 1'],
                ['nis' => '1002', 'nama' => 'Siswa 2'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 6,
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/students*page=2*' => Http::response([
            'data' => [
                ['nis' => '1003', 'nama' => 'Siswa 3'],
                ['nis' => '1004', 'nama' => 'Siswa 4'],
            ],
            'meta' => [
                'current_page' => 2,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 6,
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/students*page=3*' => Http::response([
            'data' => [
                ['nis' => '1005', 'nama' => 'Siswa 5'],
                ['nis' => '1006', 'nama' => 'Siswa 6'],
            ],
            'meta' => [
                'current_page' => 3,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 6,
            ],
        ], 200),
        // Fallback for initial request without page param
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => '1001', 'nama' => 'Siswa 1'],
                ['nis' => '1002', 'nama' => 'Siswa 2'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 6,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toHaveCount(6)
        ->and(array_column($students, 'nis'))->toBe(['1001', '1002', '1003', '1004', '1005', '1006']);

    // Page 1 initial, Page 2, Page 3
    Http::assertSentCount(3);
});

it('3. carries search and filter parameters across all pagination requests', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/teachers*page=2*' => Http::response([
            'data' => [
                ['nip' => 'GURU-02', 'nama' => 'Guru Dua'],
            ],
            'meta' => [
                'current_page' => 2,
                'last_page' => 2,
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
            'data' => [
                ['nip' => 'GURU-01', 'nama' => 'Guru Satu'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 2,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $teachers = $repo->fetchTeachers(nip: null, search: 'Budi');

    expect($teachers)->toHaveCount(2);

    Http::assertSent(function (Request $request) {
        if (str_contains($request->url(), 'page=2')) {
            return $request['search'] === 'Budi' && (int) $request['page'] === 2;
        }

        return $request['search'] === 'Budi';
    });
});

it('4. throws exception when subsequent page request fails instead of reporting false success', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*page=2*' => Http::response('Internal Server Error', 500),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => '1001', 'nama' => 'Siswa 1'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 2,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);

    expect(fn () => $repo->fetchStudents())
        ->toThrow(SiPintuApiException::class);
});

it('5. handles empty data response safely', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toBeArray()->toBeEmpty();
});

it('6. handles response without pagination metadata as a single page', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            ['nis' => 'BARE-01', 'nama' => 'Bare Student 1'],
            ['nis' => 'BARE-02', 'nama' => 'Bare Student 2'],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toHaveCount(2)
        ->and($students[0]['nis'])->toBe('BARE-01');

    Http::assertSentCount(1);
});

it('7. safely handles invalid, non-numeric, or negative last_page metadata without looping', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => 'INV-01', 'nama' => 'Siswa Valid'],
            ],
            'meta' => [
                'last_page' => -5,
                'current_page' => 'invalid_string',
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toHaveCount(1)
        ->and($students[0]['nis'])->toBe('INV-01');

    Http::assertSentCount(1);
});

it('8. respects configured per_page without forcing hardcoded limits', function (): void {
    config(['services.sipintu.per_page' => 75]);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => 'P-01', 'nama' => 'Configured PerPage Student'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
            ],
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $repo->fetchStudents();

    Http::assertSent(function (Request $request) {
        return (int) $request['per_page'] === 75;
    });
});

it('9. supports next_page_url cursor/simplePaginate pagination when last_page is omitted', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*page=2*' => Http::response([
            'data' => [
                ['nis' => 'SP-02', 'nama' => 'SimplePage 2'],
            ],
            'links' => [
                'next' => null,
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => 'SP-01', 'nama' => 'SimplePage 1'],
            ],
            'next_page_url' => 'https://sipintu.test/api/v1/sijuna/students?page=2',
        ], 200),
    ]);

    $repo = app(SiPintuRepositoryInterface::class);
    $students = $repo->fetchStudents();

    expect($students)->toHaveCount(2)
        ->and($students[0]['nis'])->toBe('SP-01')
        ->and($students[1]['nis'])->toBe('SP-02');

    Http::assertSentCount(2);
});
