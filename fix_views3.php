<?php

function fixViews($dir, $singular, $plural, $pascal, $dash) {
    $files = glob(__DIR__ . '/resources/views/' . $dir . '/*.blade.php');
    foreach($files as $file) {
        $content = file_get_contents($file);
        
        $content = str_replace(
            ['$periodePkls', '$periodePkl', 'periode-pkl', 'Periode PKL', 'PeriodePkl'],
            ['$' . $plural, '$' . $singular, $dash, $pascal, $pascal],
            $content
        );
        
        file_put_contents($file, $content);
    }
}

fixViews('admin/jurusan', 'jurusan', 'jurusans', 'Jurusan', 'jurusan');
fixViews('admin/kelas', 'kela', 'kelass', 'Kelas', 'kelas');

echo "Views fixed properly.\n";
