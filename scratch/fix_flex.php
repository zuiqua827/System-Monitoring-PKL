<?php
$files = [
    'resources/views/account/partials/account-info.blade.php',
    'resources/views/admin/dashboard/index.blade.php',
    'resources/views/admin/laporan/penilaian.blade.php',
    'resources/views/admin/login.blade.php',
    'resources/views/admin/penempatan-pkl/_form.blade.php',
    'resources/views/admin/penilaian/show.blade.php',
    'resources/views/admin/sipintu-classroom-mapping/index.blade.php',
    'resources/views/admin/sipintu-sync/index.blade.php',
    'resources/views/auth/login.blade.php',
    'resources/views/dudi/dashboard/index.blade.php',
    'resources/views/dudi/ketidakhadiran/show.blade.php',
    'resources/views/dudi/laporan/penilaian.blade.php',
    'resources/views/guru/aktivitas/show.blade.php',
    'resources/views/guru/dashboard/index.blade.php',
    'resources/views/guru/laporan/penilaian.blade.php',
    'resources/views/guru/penilaian/show.blade.php',
    'resources/views/pages/settings/⚡security.blade.php',
    'resources/views/siswa/aktivitas/show.blade.php',
    'resources/views/siswa/dashboard/index.blade.php',
    'resources/views/siswa/penilaian/show.blade.php',
];

$count = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $original = file_get_contents($file);
    $content = $original;

    // We want to replace `<div class="... flex items-center justify-between ..."`
    // Or `<div class="... flex justify-between ..."`
    // With `<div class="... flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between ..."`
    // But we need to be careful not to break existing responsive classes.

    $content = preg_replace_callback(
        '/class="([^"]*\bflex\b[^"]*\bjustify-between\b[^"]*)"/is',
        function ($m) {
            $class = $m[1];
            if (strpos($class, 'flex-col') !== false || strpos($class, 'flex-wrap') !== false || strpos($class, 'sm:flex-row') !== false) {
                return $m[0];
            }
            
            // It's a strict flex row.
            $class = str_replace([' flex ', 'items-center', 'justify-between'], [' flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between ', '', ''], ' ' . $class . ' ');
            $class = preg_replace('/\s+/', ' ', trim($class));
            
            return 'class="' . $class . '"';
        },
        $content
    );

    if ($original !== $content) {
        file_put_contents($file, $content);
        $count++;
    }
}

echo "Fixed $count files for strict flex rows.\n";
