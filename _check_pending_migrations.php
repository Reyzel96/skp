<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$ran = DB::table('migrations')->pluck('migration')->toArray();
$files = glob(__DIR__ . '/database/migrations/*.php');

foreach ($files as $file) {
    $name = basename($file, '.php');
    if (in_array($name, $ran)) continue; // skip yang sudah Ran

    $content = file_get_contents($file);

    // Cari nama tabel dari Schema::create atau Schema::table
    preg_match("/Schema::(create|table)\(['\"]([a-zA-Z0-9_]+)['\"]/", $content, $m);
    if (!$m) {
        echo "[?] $name -> tidak bisa deteksi tabel target (cek manual)\n";
        continue;
    }
    $action = $m[1]; // create atau table
    $table = $m[2];

    if ($action === 'create') {
        $exists = Schema::hasTable($table);
        echo ($exists ? "[SUDAH ADA] " : "[BELUM ADA] ") . "$name -> table `$table`\n";
        continue;
    }

    // action = table (nambah/ubah kolom) -> cek kolom yang disebut
    preg_match_all("/\\\$table->[a-zA-Z]+\(['\"]([a-zA-Z0-9_]+)['\"]/", $content, $cols);
    $colNames = array_unique($cols[1] ?? []);
    if (!Schema::hasTable($table)) {
        echo "[TABEL HILANG] $name -> table `$table` tidak ditemukan sama sekali\n";
        continue;
    }
    $missing = [];
    $existing = [];
    foreach ($colNames as $col) {
        if (Schema::hasColumn($table, $col)) $existing[] = $col;
        else $missing[] = $col;
    }
    if (empty($missing)) {
        echo "[SUDAH DITERAPKAN] $name -> semua kolom (" . implode(',', $existing) . ") sudah ada di `$table`\n";
    } elseif (empty($existing)) {
        echo "[BELUM DITERAPKAN] $name -> kolom (" . implode(',', $missing) . ") belum ada di `$table`\n";
    } else {
        echo "[CAMPURAN - CEK MANUAL] $name -> ada (" . implode(',', $existing) . ") belum ada (" . implode(',', $missing) . ")\n";
    }
}
