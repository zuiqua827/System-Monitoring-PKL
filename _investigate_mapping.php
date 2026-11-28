<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ALL TABLES ===\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array) $t)[0];
    echo "  {$name}\n";
}

echo "\n=== TABLES WITH classroom/class/rombel/mapping keywords ===\n";
foreach ($tables as $t) {
    $name = strtolower(array_values((array) $t)[0] ?? '');
    if (str_contains($name, 'class') || str_contains($name, 'rombel') || str_contains($name, 'mapping') || str_contains($name, 'sipintu') || str_contains($name, 'sync') || str_contains($name, 'cache')) {
        echo "  >>> {$name}\n";
    }
}

echo "\n=== SIPINTU SYNC LOGS (sample) ===\n";
if (Schema::hasTable('sipintu_sync_logs')) {
    foreach (DB::table('sipintu_sync_logs')->latest()->limit(10)->get() as $log) {
        echo "#{$log->id} status={$log->status} message=".substr((string) $log->message, 0, 200)."\n";
    }
}

echo "\n=== CACHE KEYS (laravel cache) ===\n";
try {
    $store = cache()->getStore();
    echo "Store: ".get_class($store)."\n";
    if (method_exists($store, 'keys') || method_exists($store, 'get') && $store instanceof \Illuminate\Cache\ArrayStore) {
        // not enumerable
    }
} catch (\Throwable $e) {
    echo "  cache error: {$e->getMessage()}\n";
}

echo "\n=== SETTINGS TABLE ===\n";
if (Schema::hasTable('settings')) {
    foreach (DB::table('settings')->get() as $s) {
        echo "  key={$s->key} value=".substr((string) $s->value, 0, 150)."\n";
    }
}

echo "\n=== SISWA with classroom info? Check columns ===\n";
echo "siswa columns: ".implode(', ', Schema::getColumnListing('siswa'))."\n";

echo "\n=== Check if any siswa has non-null class info beyond class_id ===\n";
echo "distinct class_id in siswa: ".implode(', ', DB::table('siswa')->distinct()->pluck('class_id')->map(fn($x)=>(string)$x)->all())."\n";
