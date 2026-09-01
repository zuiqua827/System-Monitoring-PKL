<?php

$file = 'app/Services/SiPintuService.php';
$content = file_get_contents($file);

// 1. Add class properties
$properties = "    private ?\Illuminate\Database\Eloquent\Collection \$localStudentsCache = null;
    private ?\Illuminate\Database\Eloquent\Collection \$localKelasCache = null;

    /**
     * {@inheritDoc}
     */
";
$content = preg_replace('/    \/\*\*\n     \* \{@inheritDoc\}\n     \*\/\n    public function fetchStudents/', ltrim($properties), $content);

// 2. Add preload method
$preload = "    private function preloadCaches(): void
    {
        if (\$this->localStudentsCache === null) {
            \$this->localStudentsCache = \App\Models\Siswa::query()->withTrashed()->get();
        }
        if (\$this->localKelasCache === null) {
            \$this->localKelasCache = \App\Models\Kelas::query()->get();
        }
    }

    /**
     * {@inheritDoc}
";
$content = preg_replace('/    \/\*\*\n     \* \{@inheritDoc\}\n     \*\/\n    public function syncStudents/', ltrim($preload), $content);

// 3. Add $this->preloadCaches(); to syncStudents()
$content = str_replace(
    "    public function syncStudents(): array\n    {\n        \$students = \$this->fetchStudents();",
    "    public function syncStudents(): array\n    {\n        \$this->preloadCaches();\n        \$students = \$this->fetchStudents();",
    $content
);

// 4. Modify findSiswaByNis
$findSiswaByNis = '    private function findSiswaByNis(string $nis): ?Siswa
    {
        if ($this->localStudentsCache !== null) {
            return $this->localStudentsCache->firstWhere(\'nis\', $nis);
        }

        /** @var Siswa|null $siswa */
        $siswa = Siswa::query()->withTrashed()->where(\'nis\', $nis)->first();

        return $siswa;
    }';
$content = preg_replace('/    private function findSiswaByNis\(string \$nis\): \?Siswa\n    \{\n.*return \$siswa;\n    \}/s', $findSiswaByNis, $content);

// 5. Modify resolveExisting to use cache for NISN
$resolveExisting = '    private function resolveExisting(array $remote): array
    {
        $nis = (string) $remote[\'nis\'];
        $nisn = (string) ($remote[\'nisn\'] ?? \'\');

        $byNis = $this->findSiswaByNis($nis);

        if ($nisn === \'\') {
            return [\'status\' => $byNis === null ? \'new\' : \'matched\', \'siswa\' => $byNis];
        }

        if ($this->localStudentsCache !== null) {
            /** @var Siswa|null $byNisn */
            $byNisn = $this->localStudentsCache->first(function ($s) use ($nisn, $nis) {
                return (string) $s->nisn === $nisn && (string) $s->nis !== $nis;
            });
        } else {
            /** @var Siswa|null $byNisn */
            $byNisn = Siswa::query()
                ->withTrashed()
                ->where(\'nisn\', $nisn)
                ->where(\'nis\', \'!=\', $nis)
                ->first();
        }

        // Ambiguous: NIS and NISN point to two different local records.
        if ($byNis !== null && $byNisn !== null && (int) $byNisn->id !== (int) $byNis->id) {
            return [\'status\' => \'conflict\', \'siswa\' => null];
        }

        if ($byNis !== null) {
            return [\'status\' => \'matched\', \'siswa\' => $byNis];
        }

        if ($byNisn !== null) {
            return [\'status\' => \'matched\', \'siswa\' => $byNisn];
        }

        return [\'status\' => \'new\', \'siswa\' => null];
    }';
$content = preg_replace('/    private function resolveExisting\(array \$remote\): array\n    \{.*?return \[\'status\' => \'new\', \'siswa\' => null\];\n    \}/s', $resolveExisting, $content);

// 6. Modify resolveKelas
$resolveKelas = '    private function resolveKelas(array $remote): ?Kelas
    {
        $classroomName = (string) ($remote[\'classroom\'][\'name\'] ?? \'\');
        if ($classroomName !== \'\') {
            if ($this->localKelasCache !== null) {
                $kelas = $this->localKelasCache->first(function($k) use ($classroomName) {
                    return strtolower((string)$k->nama) === strtolower($classroomName);
                });
            } else {
                $kelas = Kelas::query()->whereRaw(\'LOWER(nama) = ?\', [strtolower($classroomName)])->first();
            }

            if ($kelas !== null) {
                return $kelas;
            }
        }

        $classroomId = (int) ($remote[\'classroom_id\'] ?? 0);

        if ($classroomId <= 0) {
            return null;
        }

        $kelasId = $this->classroomMappingService->resolveKelasId($classroomId);

        if ($kelasId === null) {
            return null;
        }

        if ($this->localKelasCache !== null) {
            return $this->localKelasCache->firstWhere(\'id\', $kelasId);
        }

        /** @var Kelas|null $kelas */
        $kelas = Kelas::query()->find($kelasId);

        return $kelas;
    }';
$content = preg_replace('/    private function resolveKelas\(array \$remote\): \?Kelas\n    \{.*?\n        return \$kelas;\n    \}/s', $resolveKelas, $content);

file_put_contents($file, $content);
echo "Optimization script applied.\n";
