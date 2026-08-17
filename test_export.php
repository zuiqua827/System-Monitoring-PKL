<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/admin/laporan/siswa/export/excel', 'GET');
$controller = app()->make(App\Http\Controllers\Admin\Laporan\LaporanController::class);
$response = $controller->exportSiswaExcel($request);

if ($response instanceof Symfony\Component\HttpFoundation\StreamedResponse) {
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();
    file_put_contents('test_export.xlsx', $content);
    echo "Excel Exported, size: " . strlen($content) . " bytes\n";
} else {
    echo "Not a StreamedResponse (maybe empty data redirects back). Class: " . get_class($response) . "\n";
}
