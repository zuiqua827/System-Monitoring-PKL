<?php

// Fix Blade syntax errors: $(variableListList) -> variableList
$files = [
    'resources/views/siswa/aktivitas/index.blade.php',
    'resources/views/guru/aktivitas/index.blade.php',
    'resources/views/admin/aktivitas/index.blade.php',
    'resources/views/dudi/aktivitas/index.blade.php',
    'resources/views/dudi/siswa/index.blade.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        echo "ERROR: cannot read $file\n";
        continue;
    }

    $original = $content;

    // Fix $(aktivitasListList) -> aktivitasList
    $content = str_replace('$(aktivitasListList)', 'aktivitasList', $content);

    // Fix $(siswaListList) -> siswaList
    $content = str_replace('$(siswaListList)', 'siswaList', $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "FIXED: $file\n";
    } else {
        echo "NO CHANGE: $file\n";
    }
}

echo "Done.\n";
