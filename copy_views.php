<?php

$src = 'resources/views/admin/periode-pkl/';
$dstJ = 'resources/views/admin/jurusan/';
$dstK = 'resources/views/admin/kelas/';

if (!is_dir($dstJ)) mkdir($dstJ, 0777, true);
if (!is_dir($dstK)) mkdir($dstK, 0777, true);

foreach (glob($src . '*.blade.php') as $file) {
    $name = basename($file);
    
    // Copy for Jurusan
    $contentJ = file_get_contents($file);
    $contentJ = str_replace(['PeriodePKL', 'periodePKL', 'periode_pkl', 'Periode PKL'], ['Jurusan', 'jurusan', 'jurusan', 'Jurusan'], $contentJ);
    file_put_contents($dstJ . $name, $contentJ);
    
    // Copy for Kelas
    $contentK = file_get_contents($file);
    $contentK = str_replace(['PeriodePKL', 'periodePKL', 'periode_pkl', 'Periode PKL'], ['Kelas', 'kelas', 'kelas', 'Kelas'], $contentK);
    file_put_contents($dstK . $name, $contentK);
}
echo 'Views copied and replaced.';
