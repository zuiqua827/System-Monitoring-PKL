<?php

namespace Tests\Unit;

use App\Services\PenilaianService;
use PHPUnit\Framework\TestCase;

class PenilaianServiceTest extends TestCase
{
    private PenilaianService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $absensiRepo = $this->createMock(\App\Repositories\AbsensiRepository::class);
        $penilaianRepo = $this->createMock(\App\Repositories\PenilaianRepository::class);
        $this->service = new PenilaianService($penilaianRepo, $absensiRepo);
    }

    public function test_calculate_nilai_akhir(): void
    {
        // Formula: (4*100 + 2*80 + 2*80 + 2*80 + 2*80 + 2*80) / 14 = (400 + 800) / 14 = 1200 / 14 = 85.71
        $score = $this->service->calculateNilaiAkhir(100, 80, 80, 80, 80, 80);
        $this->assertEquals(85.71, $score);
    }

    public function test_calculate_predikat(): void
    {
        $this->assertEquals('A+', $this->service->calculatePredikat(95.0));
        $this->assertEquals('A', $this->service->calculatePredikat(90.0));
        $this->assertEquals('B', $this->service->calculatePredikat(85.71));
        $this->assertEquals('C', $this->service->calculatePredikat(73.43));
        $this->assertEquals('D', $this->service->calculatePredikat(60.0));
    }
}
