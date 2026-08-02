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
        
        // Also fix the route names explicitly if needed (though periode-pkl is handled by $dash)
        file_put_contents($file, $content);
    }
}

fixViews('admin/jurusan', 'jurusan', 'jurusans', 'Jurusan', 'jurusan');
fixViews('admin/kelas', 'kelas', 'kelases', 'Kelas', 'kelas'); // kelases because index uses $kelases->links() if we used it

echo "Views fixed properly.\n";
