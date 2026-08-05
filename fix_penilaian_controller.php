<?php

$file = 'app/Http/Controllers/Siswa/PenilaianController.php';
$content = file_get_contents($file);

echo "Has penempatanPKL(): " . (strpos($content, 'penempatanPKL()') !== false ? 'YES' : 'NO') . "\n";

// Fix: Siswa model uses penempat() HasMany, not penempatanPKL()
$content = str_replace(
    '$siswa->penempatanPKL()->where(\'status\', \'aktif\')->first()',
    '$siswa->penempatan()->where(\'status\', \'aktif\')->first()',
    $content
);

file_put_contents($file, $content);
echo "Fixed. Has penempatan(): " . (strpos($content, 'penempatan()') !== false ? 'YES' : 'NO') . "\n";
echo "Done.\n";
