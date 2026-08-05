<?php

$file = 'resources/views/siswa/aktivitas/index.blade.php';
$content = file_get_contents($file);

echo "Filesize: " . strlen($content) . "\n";
echo "First bytes (hex): " . bin2hex(substr($content, 0, 20)) . "\n";

// Check if UTF-16 BOM
$bom = bin2hex(substr($content, 0, 2));
echo "BOM: $bom\n";
if ($bom === 'fffe' || $bom === 'feff') {
    echo "UTF-16 BOM detected\n";
    // Convert from UTF-16 to UTF-8
    $converted = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
    // Find the problematic line
    $lines = explode("\n", $converted);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'firstItem') !== false) {
            echo "Line " . ($i+1) . ": " . $line . "\n";
        }
    }
} else {
    // UTF-8 or ASCII
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'firstItem') !== false) {
            echo "Line " . ($i+1) . ": " . $line . "\n";
        }
    }
}

echo "Done.\n";
