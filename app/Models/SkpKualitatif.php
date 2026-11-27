<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SkpKualitatif extends Model
{
    protected $table = 'skp_kualitatifs';
    protected $fillable = [
        'skp_header_id',
        'jenis',
        'rencana_hasil_kerja_pimpinan',
        'rencana_hasil_kerja',
        'indikator_target',
        'realisasi',
        'umpan_balik',
        'hasil_kerja',
        'acuan_parent_id',
        'lampiran_snapshot',
    ];
    protected $casts = [
        'lampiran_snapshot' => 'array',
    ];
    public function skpHeader()
    {
        return $this->belongsTo(SkpHeader::class, 'skp_header_id');
    }
    public function acuanParent()
    {
        return $this->belongsTo(SkpKualitatifAcuanParent::class, 'acuan_parent_id');
    }

    /**
     * Daftar kotak upload bukti utk 1 baris utama: children master yang dicentang
     * "Lampiran" saat ini UNION children yang sudah punya realisasi tersimpan
     * (kotak berisi bukti dosen tidak boleh hilang walau master di-uncheck).
     * Return array [{child_id, label}] — disimpan via cast JSON.
     */
    public static function buildLampiranSnapshot(int $headerId, int $parentId): array
    {
        $master = SkpKualitatifAcuanChild::where('parent_id', $parentId)
            ->where('butuh_lampiran', true)->get();

        $withRealisasiIds = SkpKualitatifRealisasi::where('skp_header_id', $headerId)
            ->where('target_type', 'utama')->whereNotNull('acuan_child_id')
            ->pluck('acuan_child_id');

        $union = collect();
        if ($withRealisasiIds->isNotEmpty()) {
            $union = SkpKualitatifAcuanChild::where('parent_id', $parentId)
                ->whereIn('id', $withRealisasiIds)->get();
        }

        return $master->merge($union)->unique('id')->sortBy('urutan')->values()
            ->map(fn ($c) => ['child_id' => $c->id, 'label' => $c->label_lampiran])
            ->all();
    }

    /**
     * Diff snapshot tersimpan vs target union, utk fitur Sinkron Ulang Persyaratan
     * Lampiran. SATU-SATUNYA sumber diff — dipakai preview & applySync (recompute).
     * add    = union - snapshot
     * remove = snapshot - union dan TIDAK punya realisasi (aman dihapus)
     * skip_has_realisasi = item yang master-nya di-UNCHECK tapi masih punya
     *          realisasi tersimpan → union melindungi dari REMOVE; ditampilkan
     *          agar SuperAdmin paham kenapa kotak itu tidak ikut dihapus.
     */
    public static function computeSyncPreview(int $headerId): array
    {
        $rows = SkpKualitatif::where('skp_header_id', $headerId)
            ->where('jenis', 'utama')
            ->whereNotNull('acuan_parent_id')
            ->get();

        $realisasiChildIds = SkpKualitatifRealisasi::where('skp_header_id', $headerId)
            ->where('target_type', 'utama')->whereNotNull('acuan_child_id')
            ->pluck('acuan_child_id')->all();

        $add = [];
        $remove = [];
        $skip = [];

        foreach ($rows as $row) {
            $parent = SkpKualitatifAcuanParent::find($row->acuan_parent_id);
            $target = self::buildLampiranSnapshot($headerId, (int) $row->acuan_parent_id);
            $current = collect($row->lampiran_snapshot ?? []);

            // ADD: ada di target (union) tapi belum ada di snapshot
            foreach ($target as $item) {
                if (!$current->contains('child_id', $item['child_id'])) {
                    $add[] = [
                        'skp_kualitatif_id' => $row->id,
                        'acuan_parent_id' => (int) $row->acuan_parent_id,
                        'child_id' => $item['child_id'],
                        'label' => $item['label'],
                    ];
                }
            }

            // Klasifikasi item snapshot yang tidak/berubah di master:
            foreach ($current as $item) {
                if ($item['child_id'] === null) continue;
                $childId = (int) $item['child_id'];
                $masterChild = SkpKualitatifAcuanChild::find($childId);
                $masterChecked = $masterChild && $masterChild->butuh_lampiran;
                $hasRealisasi = in_array($childId, $realisasiChildIds);
                $inTarget = collect($target)->contains('child_id', $item['child_id']);

                if (!$inTarget && !$hasRealisasi) {
                    // uncheck di master tanpa realisasi → aman dihapus
                    $remove[] = [
                        'skp_kualitatif_id' => $row->id,
                        'acuan_parent_id' => (int) $row->acuan_parent_id,
                        'child_id' => $childId,
                        'label' => $item['label'],
                    ];
                } elseif ($inTarget && $masterChild && !$masterChecked && $hasRealisasi) {
                    // master uncheck TAPI punya realisasi → union melindungi; tampilkan kenapa
                    $skip[] = [
                        'skp_kualitatif_id' => $row->id,
                        'acuan_parent_id' => (int) $row->acuan_parent_id,
                        'child_id' => $childId,
                        'label' => $item['label'],
                        'has_realisasi' => true,
                    ];
                }
                // $inTarget && $masterChecked → sinkron正常, tak ada diff
            }
        }

        return [
            'add' => $add,
            'remove' => $remove,
            'skip_has_realisasi' => $skip,
        ];
    }
}