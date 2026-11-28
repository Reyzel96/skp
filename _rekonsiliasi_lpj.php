<?php
// Rekonsiliasi READ-ONLY saldo sisa_anggaran vs LPJ (tanpa koreksi data).
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('program_kegiatan_prodi as p')
    ->select('p.id', 'p.nama_kegiatan_prodi', 'p.sisa_anggaran', 'p.anggaran_disetujui_prodi', 'p.status_persetujuan',
        DB::raw("COALESCE(SUM(CASE WHEN l.status_verifikasi <> 'ditolak' THEN l.nominal_penggunaan ELSE 0 END),0) as terpakai_valid"),
        DB::raw('COUNT(l.id) as jml_lpj'),
        DB::raw("COALESCE(SUM(CASE WHEN l.status_verifikasi = 'ditolak' THEN l.nominal_penggunaan ELSE 0 END),0) as nominal_ditolak"))
    ->leftJoin('lpj as l', 'l.program_kegiatan_prodi_id', '=', 'p.id')
    ->groupBy('p.id', 'p.nama_kegiatan_prodi', 'p.sisa_anggaran', 'p.anggaran_disetujui_prodi', 'p.status_persetujuan')
    ->orderBy('p.id')
    ->get();

$anomali = 0;
foreach ($rows as $r) {
    $harusnya = floatval($r->anggaran_disetujui_prodi) - floatval($r->terpakai_valid);
    $sisa = floatval($r->sisa_anggaran);
    $selisih = round($sisa - $harusnya, 2);
    if (abs($selisih) > 0.009) {
        $anomali++;
        printf(
            "ANOMALI pengajuan#%d '%s' status=%s | sisa=%s harusnya=%s selisih=%+d (jml LPJ=%d, nominal ditolak=%s)\n",
            $r->id,
            $r->nama_kegiatan_prodi,
            $r->status_persetujuan,
            number_format($sisa, 2),
            number_format($harusnya, 2),
            $selisih,
            $r->jml_lpj,
            number_format(floatval($r->nominal_ditolak), 2)
        );
    }
}
echo "Total pengajuan dicek: " . $rows->count() . ", anomali: $anomali\n";
