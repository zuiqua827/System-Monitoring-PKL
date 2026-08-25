<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$issues = [];
$totalScanned = 0;

foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $totalScanned++;

    $fileIssues = [];

    // 1. Static widths that might cause overflow (excluding small ones like w-4 to w-12, usually icons)
    if (preg_match_all('/(?<!sm:|md:|lg:|xl:|2xl:)\b(w-(?:64|72|80|96|1\d\d+)|w-\[[^\]]+\])/', $content, $matches)) {
        $fileIssues['Static Widths'] = array_unique($matches[0]);
    }
    
    // Static min-width
    if (preg_match_all('/(?<!sm:|md:|lg:|xl:|2xl:)\b(min-w-(?:64|72|80|96|1\d\d+)|min-w-\[[^\]]+\])/', $content, $matches)) {
        $fileIssues['Static Min-Widths'] = array_unique($matches[0]);
    }

    // 2. Unresponsive Grids
    if (preg_match_all('/(?<!sm:|md:|lg:|xl:)\bgrid-cols-[2-9]\b/', $content, $matches)) {
        $fileIssues['Unresponsive Grids'] = array_unique($matches[0]);
    }

    // 3. Flex non-wrap or strict flex rows that might break on mobile
    // Look for flex items-center justify-between without flex-col or flex-wrap
    if (preg_match_all('/class="[^"]*\bflex\b[^"]*\bjustify-between\b[^"]*"/is', $content, $matches)) {
        foreach ($matches[0] as $match) {
            if (strpos($match, 'flex-col') === false && strpos($match, 'flex-wrap') === false && strpos($match, 'sm:flex-row') === false) {
                $fileIssues['Strict Flex Row'][] = 'flex justify-between without fallback';
            }
        }
    }

    // 4. Table without overflow-x-auto
    if (strpos($content, '<table') !== false) {
        if (strpos($content, 'overflow-x-auto') === false) {
            $fileIssues['Table'][] = 'Table missing overflow-x-auto wrapper';
        }
    }

    // 5. Overflow-x-hidden globally
    if (preg_match_all('/<body[^>]*overflow-x-hidden/', $content, $matches)) {
        $fileIssues['Global Overflow'][] = 'overflow-x-hidden on body';
    }

    // 6. Padding too large for mobile (e.g. p-8 or px-8 without sm:p-8)
    if (preg_match_all('/(?<!sm:|md:|lg:|xl:)\b(p-8|px-8|py-8)\b/', $content, $matches)) {
        $fileIssues['Large Padding Mobile'] = array_unique($matches[0]);
    }

    if (!empty($fileIssues)) {
        $issues[$path] = $fileIssues;
    }
}

echo "Total Scanned: $totalScanned\n";
echo "Files with potential issues: " . count($issues) . "\n\n";

foreach ($issues as $file => $fileIssues) {
    echo "FILE: $file\n";
    foreach ($fileIssues as $category => $items) {
        echo "  - $category: " . implode(', ', array_unique($items)) . "\n";
    }
    echo "\n";
}
