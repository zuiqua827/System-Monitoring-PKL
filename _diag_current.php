<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ROLES ===\n";
foreach (DB::table('roles')->get(['id', 'name', 'guard_name']) as $r) {
    echo "id={$r->id} name={$r->name} guard={$r->guard_name}\n";
}

echo "\n=== USERS count ===\n";
echo "total: ".DB::table('users')->count()."\n";
echo "deleted: ".DB::table('users')->whereNotNull('deleted_at')->count()."\n";

echo "\n=== USERS by role (spatie model_has_roles) ===\n";
foreach (DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->select('roles.name', DB::raw('count(*) as c'))
    ->groupBy('roles.name')->get() as $r) {
    echo "{$r->name}: {$r->c}\n";
}

echo "\n=== GURU ===\n";
echo "total (non-deleted): ".DB::table('guru')->whereNull('deleted_at')->count()."\n";
echo "deleted: ".DB::table('guru')->whereNotNull('deleted_at')->count()."\n";
foreach (DB::table('guru')->whereNull('deleted_at')->limit(10)->get(['id', 'nip', 'nama']) as $g) {
    echo "id={$g->id} nip={$g->nip} nama={$g->nama}\n";
}

echo "\n=== DUDI ===\n";
echo "total (non-deleted): ".DB::table('dudi')->whereNull('deleted_at')->count()."\n";

echo "\n=== KELAS ===\n";
echo "total (non-deleted): ".DB::table('kelas')->whereNull('deleted_at')->count()."\n";
echo "deleted: ".DB::table('kelas')->whereNotNull('deleted_at')->count()."\n";

echo "\n=== JURUSAN ===\n";
echo "total (non-deleted): ".DB::table('jurusan')->whereNull('deleted_at')->count()."\n";
echo "deleted: ".DB::table('jurusan')->whereNotNull('deleted_at')->count()."\n";

echo "\n=== PERIODE_PKL ===\n";
echo "total: ".DB::table('periode_pkl')->count()."\n";

echo "\n=== SyiPintu sync logs ===\n";
echo "total: ".DB::table('sipintu_sync_logs')->count()."\n";

echo "\n=== SISWA first 16 (dummy check) ===\n";
foreach (DB::table('siswa')->orderBy('id')->limit(16)->get(['id', 'nis', 'nama', 'class_id']) as $s) {
    $real = DB::table('siswa')->where('id', $s->id)->whereNull('deleted_at')->exists();
    echo "id={$s->id} nis={$s->nis} nama={$s->nama} class_id=".($s->class_id ?? 'null')." nondeleted=".($real?'Y':'N')."\n";
}

echo "\n=== transactional refs to dummy siswa (id<=16) ===\n";
$dummyIds = DB::table('siswa')->where('id', '<=', 16)->pluck('id');
foreach (['penempatan_pkl', 'absensi', 'aktivitas', 'penilaian', 'komentar', 'laporan', 'notifikasi'] as $t) {
    if (!Schema::hasTable($t)) continue;
    $cols = Schema::getColumnListing($t);
    $siswaCol = in_array('siswa_id', $cols) ? 'siswa_id' : null;
    if ($siswaCol) {
        $c = DB::table($t)->whereIn($siswaCol, $dummyIds)->count();
        echo "  {$t}.{$siswaCol}: dummy refs={$c}\n";
    }
}
