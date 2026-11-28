<?php

// Scan all PHP files for references to the singular ->role relation,
// ->roles (Spatie), ::role( static calls, and role_id column.
// Excludes vendor/, storage/, node_modules/.

$root = __DIR__;
$excludeDirs = ['vendor', 'storage', 'node_modules', 'public', 'bootstrap/cache'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function ($file) use ($excludeDirs) {
            if ($file->isDir()) {
                return ! in_array($file->getFilename(), $excludeDirs, true);
            }
            return strtolower($file->getExtension()) === 'php';
        }
    )
);

$patterns = [
    '->role('          => 'INSTANCE relation call ->role(',
    '::role('          => 'STATIC call ::role(',
    '->roles'          => 'Spatie plural ->roles',
    '->role'           => 'ARROW ->role (singular, any)',
    'role_id'          => 'role_id column',
    'primaryRole'      => 'primaryRole (already renamed?)',
];

$results = [];
foreach ($iterator as $file) {
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    $lines = file($path);
    foreach ($lines as $i => $line) {
        $ln = $i + 1;
        foreach ($patterns as $needle => $label) {
            if (strpos($line, $needle) !== false) {
                $results[] = sprintf("%-42s %-6s %s | %s", $rel, $ln, $label, trim($line));
            }
        }
    }
}

echo "=== SCAN: role relation/column references (excluding vendor/storage/node_modules) ===\n\n";
echo count($results)." total matches\n\n";
foreach ($results as $r) {
    echo $r."\n";
}

