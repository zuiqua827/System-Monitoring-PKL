<?php
$files = [
    'resources/views/guru/laporan/absensi.blade.php',
    'resources/views/guru/laporan/penilaian.blade.php',
    'resources/views/guru/laporan/siswa.blade.php',
    'resources/views/guru/laporan/aktivitas.blade.php',
    'resources/views/dudi/laporan/absensi.blade.php',
    'resources/views/dudi/laporan/aktivitas.blade.php',
    'resources/views/dudi/laporan/siswa.blade.php',
    'resources/views/dudi/laporan/penilaian.blade.php',
    'resources/views/admin/laporan/absensi.blade.php',
    'resources/views/admin/laporan/aktivitas.blade.php',
    'resources/views/admin/laporan/siswa.blade.php',
    'resources/views/admin/laporan/penilaian.blade.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace(
            '<div class="mb-8 flex items-center justify-between">',
            '<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">',
            $content
        );
        $content = preg_replace(
            '/<div class="flex items-center gap-3">\s*<a href="/',
            '<div class="flex flex-wrap items-center gap-3">' . "\n                " . '<a href="',
            $content
        );
        file_put_contents($file, $content);
        echo 'Updated ' . $file . PHP_EOL;
    }
}
