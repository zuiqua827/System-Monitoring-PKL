<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== JURUSAN ===\n";
foreach (DB::table('jurusan')->get() as $j) {
    echo "#{$j->id} [{$j->kode}] {$j->nama}\n";
}

echo "\n=== KELAS ===\n";
foreach (DB::table('kelas')->orderBy('id')->get() as $k) {
    echo "#{$k->id} jurusan_id={$k->jurusan_id} [{$k->nama}] tingkat={$k->tingkat} ta={$k->tahun_ajaran}\n";
}

echo "\n=== SISWA class_id distribution (non-deleted, id>=17 real) ===\n";
$dist = DB::table('siswa')->whereNull('deleted_at')->where('id', '>=', 17)
    ->select('class_id', DB::raw('count(*) as c'))
    ->groupBy('class_id')->orderBy('class_id')->get();
foreach ($dist as $d) {
    echo "class_id={$d->class_id} count={$d->c}\n";
}

echo "\n=== Total siswa (non-deleted, id>=17): ".DB::table('siswa')->whereNull('deleted_at')->where('id','>=',17)->count()." ===\n";
echo "=== Siswa with null class_id (id>=17): ".DB::table('siswa')->whereNull('deleted_at')->where('id','>=',17)->whereNull('class_id')->count()." ===\n";
echo "=== Siswa with null class_id (all): ".DB::table('siswa')->whereNull('deleted_at')->whereNull('class_id')->count()." ===\n";

echo "\n=== Local kelas==jurusan seeder (11), API classrooms (33 distinct) ===\n";
echo "Local kelas count: ".DB::table('kelas')->count()."\n";
