<?php

// Boot Laravel app manually
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "=== ROLE ARCHITECTURE VERIFICATION ===\n\n";

// 1. Check the exact error
echo "--- 1. Calling User::role('Guru') ---\n";
try {
    $result = User::role('Guru')->get();
    echo 'SUCCESS: '.count($result)." users found\n";
} catch (\Throwable $e) {
    echo 'ERROR: '.get_class($e)."\n";
    echo 'MESSAGE: '.$e->getMessage()."\n";
}
echo "\n";

// 2. Check method resolution
echo "--- 2. Method existence on User model ---\n";
$u = new User();
echo 'scopeRole (Spatie query scope): '.(method_exists($u, 'scopeRole') ? 'EXISTS' : 'MISSING')."\n";
echo 'role (instance method): '.(method_exists($u, 'role') ? 'EXISTS' : 'MISSING')."\n";
echo 'roles (relation): '.(method_exists($u, 'roles') ? 'EXISTS' : 'MISSING')."\n";
echo 'hasRole: '.(method_exists($u, 'hasRole') ? 'EXISTS' : 'MISSING')."\n";
echo 'assignRole: '.(method_exists($u, 'assignRole') ? 'EXISTS' : 'MISSING')."\n";
echo "\n";

// 3. Inspect the HasRoles trait for role() and scopeRole()
echo "--- 3. HasRoles trait methods ---\n";
$traitMethods = get_class_methods(\Spatie\Permission\Traits\HasRoles::class);
foreach ($traitMethods as $method) {
    if (stripos($method, 'role') !== false) {
        echo "  trait method: {$method}\n";
    }
}
echo "\n";

// 4. Check roles and users tables
echo "--- 4. Database check ---\n";
echo "Roles table count: ".Role::count()."\n";
foreach (Role::all() as $role) {
    echo "  - {$role->name} (id={$role->id}, guard={$role->guard_name})\n";
}
echo "Users table count: ".User::withTrashed()->count()."\n";
foreach (User::with('roles')->withTrashed()->get() as $user) {
    $roleNames = $user->roles->pluck('name')->implode(', ');
    echo "  - #{$user->id} {$user->name} ({$user->email}) roles=[{$roleNames}] role_id={$user->role_id}\n";
}
echo "\n";

// 5. Check model_has_roles
echo "--- 5. model_has_roles pivot ---\n";
$pivotRows = DB::table('model_has_roles')->get();
foreach ($pivotRows as $row) {
    echo "  - role_id={$row->role_id} model_type={$row->model_type} model_id={$row->model_id}\n";
}
echo "\n";

// 6. Verify Spatie scope works via full query with whereHas
echo "--- 6. Correct Spatie usage patterns ---\n";
echo "User::role('Guru')->count() (if works): ";
try {
    echo User::role('Guru')->count()."\n";
} catch (\Throwable $e) {
    echo "FAILED: ".$e->getMessage()."\n";
}
echo "whereHas('roles', name=Guru): ".User::whereHas('roles', function ($q) { $q->where('name', 'Guru'); })->count()."\n";

$kernel->terminate(null, null);

