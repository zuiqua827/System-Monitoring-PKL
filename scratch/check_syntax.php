<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);
$error = false;
foreach ($files as $file) {
    exec('php -l ' . escapeshellarg($file[0]) . ' 2>&1', $output, $return);
    if ($return !== 0) {
        echo implode("\n", $output) . "\n";
        $error = true;
    }
    $output = [];
}
if (!$error) echo "All blade files passed syntax check.\n";
