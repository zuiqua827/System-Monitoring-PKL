<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== JURUSAN ===\n";
foreach (DB::table('jurusan')->get() as $j) {
    echo "id={$j->id} | kode={$j->kode} | nama={$j->nama} | deleted=".($j->deleted_at ?? 'null')."\n";
}

echo "\n=== KELAS ===\n";
foreach (DB::table('kelas')->get() as $k) {
    echo "id={$k->id} | nama={$k->nama} | jurusan_id={$k->jurusan_id} | tingkat={$k->tingkat} | deleted=".($k->deleted_at ?? 'null')."\n";
}

echo "\n=== SISWA counts ===\n";
echo "non-deleted: ".DB::table('siswa')->whereNull('deleted_at')->count()."\n";
echo "deleted: ".DB::table('siswa')->whereNotNull('deleted_at')->count()."\n";

echo "\n=== SISWA kelas distribution (non-deleted) ===\n";
foreach (DB::table('siswa')->whereNull('deleted_at')->selectRaw('class_id, count(*) as c')->groupBy('class_id')->get() as $s) {
    echo "class_id={$s->class_id} count={$s->c}\n";
}

echo "\n=== SAMPLE SISWA (first 15 non-deleted) ===\n";
foreach (DB::table('siswa')->whereNull('deleted_at')->limit(15)->get() as $s) {
    echo "id={$s->id} | nis={$s->nis} | nama={$s->nama} | class_id={$s->class_id}\n";
}
