<?php
$files = glob(__DIR__ . '/resources/views/*/*/*.blade.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (!str_contains($content, '@endsection') && !str_contains($content, '@endforeach') && !str_contains($content, '</div>')) {
        echo "Truncated: " . $file . "\n";
    }
}
echo "Done checking views.\n";
