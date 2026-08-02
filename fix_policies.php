<?php
$files = ['app/Policies/AbsensiPolicy.php', 'app/Policies/AktivitasPolicy.php'];
foreach($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('->penempatanPKL?->', '->penempatanPKL->', $content);
    file_put_contents($file, $content);
}
echo 'Policies fixed.';
