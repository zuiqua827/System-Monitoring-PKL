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
        $absensiService = $this->createMock(\App\Services\Interfaces\AbsensiServiceInterface::class);
        $penilaianRepo = $this->createMock(\App\Repositories\PenilaianRepository::class);
        $this->service = new PenilaianService($penilaianRepo, $absensiService);
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

    public function test_deskripsi_per_aspek_different_for_same_predikat(): void
    {
        // Kehadiran A+ vs Teknis A+
        $kehadiranAPlus = PenilaianService::getDeskripsiAspek('kehadiran', 95);
        $teknisAPlus = PenilaianService::getDeskripsiAspek('teknis', 95);

        $this->assertNotEquals($kehadiranAPlus, $teknisAPlus);
        $this->assertStringContainsString('kehadiran', strtolower($kehadiranAPlus));
        $this->assertStringContainsString('teknis', strtolower($teknisAPlus));

        // Kerja Sama A vs Komunikasi A
        $kerjasamaA = PenilaianService::getDeskripsiAspek('kerjasama', 90);
        $komunikasiA = PenilaianService::getDeskripsiAspek('komunikasi', 90);

        $this->assertNotEquals($kerjasamaA, $komunikasiA);
        $this->assertStringContainsString('bekerja sama', strtolower($kerjasamaA));
        $this->assertStringContainsString('berkomunikasi', strtolower($komunikasiA));
    }

    public function test_deskripsi_all_predikats_for_all_aspeks(): void
    {
        $aspeks = ['kehadiran', 'kerjasama', 'komunikasi', 'problem_solving', 'teknis', 'inisiatif'];
        $scores = [
            'A+' => 95,
            'A' => 90,
            'B' => 85,
            'C' => 75,
            'D' => 60,
        ];

        foreach ($aspeks as $aspek) {
            foreach ($scores as $predikat => $score) {
                $deskripsi = PenilaianService::getDeskripsiAspek($aspek, $score);
                $this->assertNotEmpty($deskripsi);
                $this->assertNotEquals('-', $deskripsi);
            }
        }
    }
}
