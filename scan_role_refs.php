<?php

$dirs = ['app', 'resources', 'routes', 'database', 'config', 'tests'];
$patterns = [
    'arrow_role'   => '->role\b(?!s)',
    'role_id'      => 'role_id',
    'static_role(' => '::role\(|->role\(',
    'roles_plural' => '->roles\b',
];

$results = [];
foreach ($dirs as $dir) {
    $base = __DIR__.'/'.$dir;
    if (! is_dir($base)) {
        continue;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $ext = $file->getExtension();
        if ($ext !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $content = file_get_contents($path);
        foreach ($patterns as $key => $regex) {
            if (preg_match_all('/'.$regex.'/m', $content, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as $match) {
                    $offset = $match[1];
                    $lineNo = substr_count(substr($content, 0, $offset), "\n") + 1;
                    $line = explode("\n", $content)[$lineNo - 1];
                    $results[$key][] = str_replace(__DIR__, '.', $path).':'.$lineNo.':'.trim($line);
                }
            }
        }
    }
}

foreach ($results as $key => $lines) {
    $count = count($lines);
    echo "===== {$key} ({$count}) =====\n";
    $seen = [];
    foreach ($lines as $line) {
        if (! in_array($line, $seen, true)) {
            $seen[] = $line;
            echo $line."\n";
        }
    }
    echo "\n";
}

