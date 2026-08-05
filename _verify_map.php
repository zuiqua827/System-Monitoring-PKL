<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check which local siswa are real (in API) vs dummy
echo "=== Total local siswa (non-deleted): ".DB::table('siswa')->whereNull('deleted_at')->count()." ===\n";
echo "=== Total local siswa (deleted): ".DB::table('siswa')->whereNotNull('deleted_at')->count()." ===\n\n";

// The dummy students are the first 16 by id (factory-generated). Let's confirm by checking names
echo "=== Students with suspicious/foreign names (first 16 ids) ===\n";
$dummy = DB::table('siswa')->orderBy('id')->limit(16)->get();
foreach ($dummy as $s) {
    echo "id={$s->id} nis={$s->nis} nama={$s->nama}\n";
}

// Check real students count
echo "\n=== Real students (id >= 17) count: ".DB::table('siswa')->where('id', '>=', 17)->whereNull('deleted_at')->count()." ===\n";

// Check if any real students have null class_id
echo "=== Real students with null class_id: ".DB::table('siswa')->where('id', '>=', 17)->whereNull('class_id')->count()." ===\n";

// Check FK references from other tables to siswa (to ensure we don't break relations)
echo "\n=== Referential safety check: which tables reference siswa.user_id ===\n";
foreach (['penempatan_pkl', 'absensi', 'aktivitas', 'penilaian', 'komentar', 'laporan', 'notifikasi'] as $t) {
    if (Schema::hasTable($t)) {
        echo "  {$t}: rows=".DB::table($t)->count()."\n";
    }
}

// Check what dummy siswa are referenced in transactional tables
echo "\n=== Dummy siswa (id<=16) referenced in transactional tables ===\n";
$dummyIds = DB::table('siswa')->where('id', '<=', 16)->pluck('id');
foreach (['penempatan_pkl as p' => 'p.siswa_id', 'absensi as a' => 'a.siswa_id', 'aktivitas as x' => 'x.siswa_id', 'penilaian as pn' => 'pn.siswa_id'] as $alias => $col) {
    $table = explode(' as ', $alias)[0];
    $count = DB::table($table)->whereIn($col, $dummyIds)->count();
    echo "  {$table}: dummy refs={$count}\n";
}
