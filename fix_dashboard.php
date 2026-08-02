<?php
$file = 'app/Repositories/DashboardRepository.php';
$content = file_get_contents($file);

// Replace array with correct phpdoc
$content = str_replace('public function getKehadiran7Hari(): array', "/** @return list<array<string, int|string>> */\n    public function getKehadiran7Hari(): array", $content);
$content = str_replace('public function getStatusAbsensi(): array', "/** @return array<string, int> */\n    public function getStatusAbsensi(): array", $content);
$content = str_replace('public function getPenempatanPerDudi(): array', "/** @return list<array<string, int|string>> */\n    public function getPenempatanPerDudi(): array", $content);
$content = str_replace('public function getPredikatPenilaian(): array', "/** @return array<string, int> */\n    public function getPredikatPenilaian(): array", $content);
$content = str_replace('public function getAktivitasMingguan(): array', "/** @return list<array<string, int|string>> */\n    public function getAktivitasMingguan(): array", $content);
$content = str_replace('public function getRecentActivity(int $limit = 10): array', "/** @return list<array<string, string>> */\n    public function getRecentActivity(int $limit = 10): array", $content);
$content = str_replace('public function getKehadiran7HariByGuru(int $guruId): array', "/** @return list<array<string, int|string>> */\n    public function getKehadiran7HariByGuru(int $guruId): array", $content);
$content = str_replace('public function getStatusAktivitasByGuru(int $guruId): array', "/** @return array<string, int> */\n    public function getStatusAktivitasByGuru(int $guruId): array", $content);
$content = str_replace('public function getNilaiSiswaByGuru(int $guruId): array', "/** @return list<array<string, float|string>> */\n    public function getNilaiSiswaByGuru(int $guruId): array", $content);
$content = str_replace('public function getAbsensi7HariByDudi(int $dudiId): array', "/** @return list<array<string, int|string>> */\n    public function getAbsensi7HariByDudi(int $dudiId): array", $content);
$content = str_replace('public function getAktivitas7HariByDudi(int $dudiId): array', "/** @return list<array<string, int|string>> */\n    public function getAktivitas7HariByDudi(int $dudiId): array", $content);
$content = str_replace('public function getSiswaDashboardData(int $siswaId): array', "/** @return array<string, mixed> */\n    public function getSiswaDashboardData(int $siswaId): array", $content);

// Remove nullsafe and ?? dead code
$content = preg_replace('/\$item->dudi\?->nama_perusahaan \?\? \'Unknown\'/', '$item->dudi->nama_perusahaan', $content);
$content = preg_replace('/\$a->jam_masuk\?->format\(\'H:i\'\) \?\? \'-\'/', '$a->jam_masuk->format(\'H:i\')', $content);
$content = preg_replace('/\$a->jam_keluar\?->format\(\'H:i\'\) \?\? \'-\'/', '$a->jam_keluar->format(\'H:i\')', $content);
$content = preg_replace('/\$a->penempatanPKL\?->siswa\?->nama \?\? \'-\'/', '$a->penempatanPKL->siswa->nama', $content);
$content = preg_replace('/\$a->penempatanPKL\?->siswa\?->nama \?\? \'Siswa\'/', '$a->penempatanPKL->siswa->nama', $content);
$content = preg_replace('/\$p->penempatanPKL\?->siswa\?->nama \?\? \'Unknown\'/', '$p->penempatanPKL->siswa->nama', $content);
$content = preg_replace('/\$p->penempatanPKL\?->siswa\?->nama \?\? \'Siswa\'/', '$p->penempatanPKL->siswa->nama', $content);
$content = preg_replace('/\$p->dinilaiOleh\?->name \?\? \'Guru\'/', '$p->dinilaiOleh->name', $content);

file_put_contents($file, $content);
echo 'Dashboard fixed.';
