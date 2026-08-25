<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    $original = file_get_contents($path);
    $content = $original;

    // 1. Fix global padding
    $content = preg_replace(
        '/\bpx-4 py-8 sm:px-6 lg:px-8\b/',
        'px-4 py-4 sm:px-6 sm:py-8 lg:px-8',
        $content
    );

    // 2. Fix table squishing
    $content = preg_replace(
        '/<table([^>]*class="[^"]*)min-w-full divide-y/is',
        '<table$1w-full min-w-[800px] divide-y',
        $content
    );
    // some tables might have min-w-full and then other classes
    $content = preg_replace(
        '/<table class="min-w-full\b/is',
        '<table class="w-full min-w-[800px]',
        $content
    );

    // 3. Fix grid-cols-2 without responsive prefixes in grids (except in Rincian Aspek inside table)
    $content = preg_replace_callback(
        '/<div([^>]*class="[^"]*grid\s+[^"]*)grid-cols-2\b/is',
        function ($matches) {
            // If it already has sm:grid-cols, ignore
            if (strpos($matches[1], 'sm:grid-cols') !== false) {
                return $matches[0];
            }
            return '<div' . $matches[1] . 'grid-cols-1 sm:grid-cols-2';
        },
        $content
    );

    // 4. Fix flex-1 inside overflow-x-auto (charts)
    $content = preg_replace(
        '/<div class="flex min-w-0 flex-1 flex-col items-center gap-2">/is',
        '<div class="flex min-w-0 shrink-0 flex-col items-center gap-2 w-10 sm:w-12 sm:flex-1">',
        $content
    );

    // 5. Fix filter layouts that use flex but without wrap
    $content = preg_replace(
        '/<form([^>]*class="[^"]*flex\b[^"]*items-center)(?![^"]*flex-wrap)/is',
        '<form$1 flex-wrap',
        $content
    );

    if ($original !== $content) {
        file_put_contents($path, $content);
        $count++;
    }
}

echo "Refactored $count blade files.\n";
