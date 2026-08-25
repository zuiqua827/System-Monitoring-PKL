<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$patterns = [
    'Static grid-cols' => '/(?<!sm:|md:|lg:|xl:)\bgrid-cols-[2-9]\b/',
    'Static padding/gap 8' => '/(?<!sm:|md:|lg:|xl:)\b(p-8|px-8|py-8|gap-8)\b/',
    'Static large width' => '/(?<!sm:|md:|lg:|xl:)\b(w-64|w-72|w-80|w-96|w-\[[0-9]+px\])\b/',
    'Static min width' => '/(?<!sm:|md:|lg:|xl:)\b(min-w-64|min-w-72|min-w-80|min-w-96|min-w-\[[0-9]+px\])\b/',
];

$results = [];
foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    foreach ($patterns as $name => $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach($matches[0] as $match) {
                $results[$name][] = $path . ' : ' . $match;
            }
        }
    }
}

foreach ($results as $name => $matches) {
    echo strtoupper($name) . "\n";
    $matches = array_unique($matches);
    foreach (array_slice($matches, 0, 20) as $match) {
        echo "- $match\n";
    }
    if (count($matches) > 20) echo "... and " . (count($matches) - 20) . " more\n";
    echo "\n";
}
