<?php
$dirs = ['admin/jurusan', 'admin/kelas'];
foreach($dirs as $dir) {
    $files = glob('resources/views/' . $dir . '/*.blade.php');
    foreach($files as $file) {
        $content = file_get_contents($file);
        
        // Handle Plurals first to avoid substring matching issues
        $searchPlural = ['periodePkls'];
        $replacePlural = $dir === 'admin/jurusan' ? ['jurusans'] : ['kelases'];
        $content = str_replace($searchPlural, $replacePlural, $content);
        
        // Handle singulars
        $search = ['periodePkl', 'periode-pkl', 'Periode PKL', 'PeriodePkl'];
        $replace = $dir === 'admin/jurusan' ? ['jurusan', 'jurusan', 'Jurusan', 'Jurusan'] : ['kelas', 'kelas', 'Kelas', 'Kelas'];
        $content = str_replace($search, $replace, $content);
        
        file_put_contents($file, $content);
    }
}
echo 'Views fixed.';
