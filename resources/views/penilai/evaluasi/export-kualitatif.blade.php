{{-- Export PDF (DomPDF) — Penilai / Evaluasi Bawahan / SKP Dosen Kualitatif.
     Hanya tab yang diminta (query ?tab=) yang dirender, isi evaluasi saja:
     judul, teks realisasi, umpan balik, nilai — TANPA kontrol form/upload.
     CSS polos (DomPDF tidak mendukung :has(), flexbox, atau JS). --}}
@php
    $hd = $data['skpKualitatif']->skpHeader;
    $bawahan = $data['bawahan'];
    $penilai = $data['penilai'];
    $tambahans = $data['tambahans'];
    $perilakus = $data['perilakus'];
    $realisasis = $data['realisasis'];
    $skpUtamas = $data['skpUtamas'];
    $perilakuAcuan = $data['perilakuAcuan'];
    $hasilKerjaCfg = $data['hasilKerja'];

    // Pemetaan nilai sama dengan x-data hasilKerjaMap di view utama.
    $nilaiMap = [
        'Diatas Ekspektasi'  => $hasilKerjaCfg[0] ?? 3,
        'Sesuai Ekspektasi'  => $hasilKerjaCfg[1] ?? 2,
        'Dibawah Ekspektasi' => $hasilKerjaCfg[2] ?? 1,
    ];

    // Label singkat bukti dukung A. UTAMA (pola sama dengan $utamaLabel di view utama).
    $utamaLabel = function ($text, $urutan) {
        $text = trim((string) $text);
        if ($text === '') return 'Sub-Indikator ' . $urutan;
        $t = strtolower($text);
        $map = [
            'penelitian'   => 'Penelitian',
            'riset'        => 'Penelitian',
            'pengabdian'   => 'Pengabdian',
            'pengajaran'   => 'Pengajaran',
            'pembelajaran' => 'Pengajaran',
            'pendidikan'   => 'Pengajaran',
        ];
        foreach ($map as $kw => $label) {
            if (str_contains($t, $kw)) return $label;
        }
        return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
    };

    // Label singkat bukti dukung Perilaku (pola sama dengan $perilakuLabel di view utama).
    $perilakuLabel = function ($text, $urutan) {
        $text = trim((string) $text);
        if ($text === '') return 'Sub-Indikator ' . $urutan;
        $t = strtolower($text);
        $map = [
            'penelitian'   => 'Penelitian',
            'riset'        => 'Penelitian',
            'pengabdian'   => 'Pengabdian',
            'pengajaran'   => 'Pengajaran',
            'pembelajaran' => 'Pengajaran',
            'pendidikan'   => 'Pengajaran',
        ];
        foreach ($map as $kw => $label) {
            if (str_contains($t, $kw)) return $label;
        }
        return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
    };

    // Kumpulan anak (lampiran wajib) utama = SNAPSHOT, fallback master acuan.
    $utamaRows = [];
    foreach ($skpUtamas as $u) {
        $snapshotItems = $u->lampiran_snapshot;
        if ($snapshotItems === null && $u->acuanParent && $u->acuanParent->children) {
            $children = $u->acuanParent->children->where('butuh_lampiran', true)->values();
        } else {
            $children = collect($snapshotItems ?? [])->map(function ($item) {
                $label = $item['label'] ?? null;
                if (!is_null($item['child_id'] ?? null)) {
                    $child = \App\Models\SkpKualitatifAcuanChild::find($item['child_id']);
                    if ($child) {
                        return (object) ['id' => $child->id, 'indikator_kinerja' => $child->indikator_kinerja, 'label_lampiran' => $label];
                    }
                }
                return (object) ['id' => $item['child_id'] ?? null, 'indikator_kinerja' => '', 'label_lampiran' => $label];
            })->values();
        }
        $rows = [];
        foreach ($children as $ci => $child) {
            $r = $realisasis->where('target_type', 'utama')->where('acuan_child_id', $child->id)->first();
            $rows[] = [
                'label'      => $r->label_realisasi ?? ($child->label_lampiran ?? $utamaLabel($child->indikator_kinerja, $ci + 1)),
                'realisasi'  => trim((string) ($r->realisasi ?? '')),
            ];
        }
        $utamaRows[] = ['utama' => $u, 'rows' => $rows];
    }

    // Anak perilaku per aspek (matching dinormalisasi seperti view utama).
    $perilakuRows = [];
    foreach ($perilakus as $p) {
        $children = collect();
        $acuan = $perilakuAcuan->first(fn($a) => trim(mb_strtolower($a->sasaran_kinerja ?? '')) === trim(mb_strtolower($p->aspek ?? '')));
        if ($acuan && $acuan->children) {
            $children = $acuan->children->where('butuh_lampiran', true)->values();
        }
        $rows = [];
        foreach ($children as $ci => $child) {
            $r = $realisasis->where('target_type', 'perilaku')->where('acuan_child_id', $child->id)->first();
            $rows[] = [
                'label'      => $r->label_realisasi ?? ($child->label_lampiran ?? $perilakuLabel($child->indikator_kinerja, $ci + 1)),
                'realisasi'  => trim((string) ($r->realisasi ?? '')),
            ];
        }
        $perilakuRows[] = ['perilaku' => $p, 'rows' => $rows];
    }

    // Rating hasil kerja & perilaku dihitung server-side (logika sama dgn Alpine).
    $skorHasil = [];
    foreach ($skpUtamas as $u) { if (trim((string)$u->hasil_kerja) !== '') $skorHasil[] = $nilaiMap[$u->hasil_kerja] ?? null; }
    foreach ($tambahans as $t) { if (trim((string)$t->hasil_kerja) !== '') $skorHasil[] = $nilaiMap[$t->hasil_kerja] ?? null; }
    $skorHasil = array_filter($skorHasil, fn($v) => !is_null($v));
    $ratingHasil = count($skorHasil) ? (array_sum($skorHasil) / count($skorHasil) >= 2.5 ? 'DIATAS EKSPEKTASI' : (array_sum($skorHasil) / count($skorHasil) >= 1.5 ? 'SESUAI EKSPEKTASI' : 'DIBAWAH EKSPEKTASI')) : '-';

    $skorPerilaku = [];
    foreach ($perilakus as $p) { if (trim((string)$p->hasil_kerja) !== '') $skorPerilaku[] = $nilaiMap[$p->hasil_kerja] ?? null; }
    $skorPerilaku = array_filter($skorPerilaku, fn($v) => !is_null($v));
    $ratingPerilaku = count($skorPerilaku) ? (array_sum($skorPerilaku) / count($skorPerilaku) >= 2.5 ? 'DIATAS EKSPEKTASI' : (array_sum($skorPerilaku) / count($skorPerilaku) >= 1.5 ? 'SESUAI EKSPEKTASI' : 'DIBAWAH EKSPEKTASI')) : '-';

    // Predikat kinerja (matriks sama dengan JS predikatKinerja).
    $predikat = '-';
    if ($ratingHasil !== '-' && $ratingPerilaku !== '-') {
        if ($ratingHasil === 'DIATAS EKSPEKTASI') {
            $predikat = $ratingPerilaku === 'DIATAS EKSPEKTASI' ? 'SANGAT BAIK' : ($ratingPerilaku === 'SESUAI EKSPEKTASI' ? 'BAIK' : 'KURANG');
        } elseif ($ratingHasil === 'SESUAI EKSPEKTASI') {
            $predikat = $ratingPerilaku === 'DIBAWAH EKSPEKTASI' ? 'BUTUH PERBAIKAN' : 'BAIK';
        } else {
            $predikat = $ratingPerilaku === 'DIATAS EKSPEKTASI' ? 'BUTUH PERBAIKAN' : ($ratingPerilaku === 'SESUAI EKSPEKTASI' ? 'KURANG' : 'SANGAT KURANG');
        }
    }

    $periodeLabel = $hd->periode->periode_penilaian_label ?? '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>SKP Kualitatif</title>
<style>
    @page { margin: 36px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    table td, table th { border: 1px solid #000; padding: 5px 7px; vertical-align: top; font-size: 12px; }
    th { background-color: #f3f4f6; }
    .judul { text-align: center; font-weight: bold; margin: 0 0 8px 0; }
    .mb { margin-bottom: 10px; }
    .nowrap-group td { font-weight: bold; background-color: #f9fafb; }
    .center { text-align: center; }
    .tbl-evaluasi { table-layout: fixed; }
    .tbl-evaluasi col.c1 { width: 36%; } .tbl-evaluasi col.c2 { width: 24%; }
    .tbl-evaluasi col.c3 { width: 26%; } .tbl-evaluasi col.c4 { width: 14%; }
    .tbl-perilaku { table-layout: fixed; }
    .tbl-perilaku col.c1 { width: 45%; } .tbl-perilaku col.c2 { width: 25%; }
    .tbl-perilaku col.c3 { width: 20%; } .tbl-perilaku col.c4 { width: 10%; }
    .card-label { font-weight: bold; font-size: 11px; margin: 0 0 2px 0; }
    .card-isi { margin: 0 0 6px 0; }
    .kartu { margin-bottom: 6px; }
    /* R4 — pemisah antar dokumen: kartu ke-2 dst diberi garis atas tipis +
       jarak; dokumen pertama tanpa garis atas. Semua kartu tetap di dalam
       SATU td tersusun ke bawah (tidak ada baris tabel tambahan). */
    .kartu + .kartu { border-top: 1px solid #666; padding-top: 6px; margin-top: 6px; }
    .redaksi { margin: 0; }
    .flex-no { width: 100%; }
</style>
</head>
<body>

@if($exportTab === 'skp')
    <p class="judul">SASARAN KINERJA PEGAWAI<br>PENDEKATAN HASIL KERJA KUALITATIF<br>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>

    <table class="mb">
        <tr>
            <th class="center" style="width:5%">NO</th>
            <th style="width:45%">PEGAWAI YANG DINILAI</th>
            <th class="center" style="width:5%">NO</th>
            <th style="width:45%">PEJABAT PENILAI KINERJA</th>
        </tr>
        <tr><td class="center">1</td><td><b>NAMA</b> {{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</td><td class="center">1</td><td><b>NAMA</b> {{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</td></tr>
        <tr><td class="center">2</td><td><b>NIP</b> {{ $bawahan->profile->nip ?? '-' }}</td><td class="center">2</td><td><b>NIP</b> {{ $penilai->profile->nip ?? '-' }}</td></tr>
        <tr><td class="center">3</td><td><b>PANGKAT/GOL. RUANG</b> {{ $bawahan->profile->pangkat_golongan ?? '-' }}</td><td class="center">3</td><td><b>PANGKAT/ GOL. RUANG</b> {{ $penilai->profile->pangkat_golongan ?? '-' }}</td></tr>
        <tr><td class="center">4</td><td><b>JABATAN</b> {{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td><td class="center">4</td><td><b>JABATAN</b> {{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td></tr>
        <tr><td class="center">5</td><td><b>UNIT KERJA</b> Universitas Muhammadiyah Surabaya</td><td class="center">5</td><td><b>UNIT KERJA</b> Universitas Muhammadiyah Surabaya</td></tr>
    </table>

    <table class="mb">
        <tr class="nowrap-group"><td colspan="4">HASIL KERJA</td></tr>
        <tr class="nowrap-group"><td colspan="4">A. UTAMA</td></tr>
        <tr>
            <td class="center" style="width:5%"><b>1</b></td>
            <td colspan="3"><i>{{ $data['skpKualitatif']->rencana_hasil_kerja ?? 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan...' }}</i>
                <p class="redaksi"><b>Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</b></p>
                {!! nl2br(e($data['skpKualitatif']->indikator_target ?? '')) !!}
            </td>
        </tr>
        <tr>
            <td class="center"><b>2</b></td>
            <td colspan="3"><i>Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)</i>
                <p class="redaksi"><b>Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</b></p>
                - Terlaksananya tugas kepemimpinan dengan baik<br>- Terlaksananya pembinaan dosen, mahasiswa dan tenaga kependidikan dengan baik<br>- Meningkatnya persentase prodi yang unggul<br>- Meningkatnya mutu pembelajaran, penelitian dan pengabdian masyarakat
            </td>
        </tr>
        @if(count($tambahans))
            <tr class="nowrap-group"><td colspan="4">B. TAMBAHAN</td></tr>
            @foreach($tambahans as $i => $t)
                <tr><td class="center">{{ $i + 1 }}</td><td colspan="3">{!! nl2br(e($t->rencana_hasil_kerja)) !!}</td></tr>
            @endforeach
        @else
            <tr class="nowrap-group"><td colspan="4">B. TAMBAHAN</td></tr>
            <tr><td class="center">1</td><td colspan="3">Tidak ada target tambahan.</td></tr>
        @endif
    </table>

    <table class="mb">
        <tr class="nowrap-group"><td colspan="4">PERILAKU KERJA*</td></tr>
        @forelse($perilakus as $i => $p)
            <tr>
                <td class="center" style="width:5%">{{ $i + 1 }}</td>
                <td colspan="2" style="width:60%"><b>{{ $p->aspek }}</b><br>{!! nl2br(e($p->indikator)) !!}</td>
                <td style="width:35%"><b>Ekspektasi Khusus Pimpinan:</b><br>{{ $p->ekspektasi_pimpinan ?? '-' }}</td>
            </tr>
        @empty
            <tr><td class="center">1</td><td colspan="3">-</td></tr>
        @endforelse
    </table>
@endif

@if($exportTab === 'lampiran')
    <p class="judul">LAMPIRAN SASARAN KINERJA PEGAWAI</p>
    <table>
        @php $grupLampiran = ['dukungan_sumber_daya' => 'DUKUNGAN SUMBER DAYA (pegawai, sarpras, anggaran, metode)', 'skema_pertanggungjawaban' => 'SKEMA PERTANGGUNGJAWABAN', 'konsekuensi' => 'KONSEKUENSI']; @endphp
        @foreach($grupLampiran as $jenis => $judulGrup)
            @php $items = $data['lampirans']->where('jenis', $jenis)->values(); @endphp
            <tr class="nowrap-group"><td colspan="2">{{ $judulGrup }}</td></tr>
            @forelse($items as $i => $item)
                <tr><td class="center" style="width:5%">{{ $i + 1 }}</td><td>{!! nl2br(e($item->deskripsi)) !!}</td></tr>
            @empty
                <tr><td class="center" style="width:5%">1</td><td>Belum ada data yang diinput.</td></tr>
            @endforelse
        @endforeach
    </table>
@endif

@if($exportTab === 'evaluasi')
    <p class="judul">SASARAN KINERJA PEGAWAI<br>PENDEKATAN HASIL KERJA KUALITATIF<br>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>
    <div style="overflow:hidden;" class="mb">
        <span style="float:left; font-weight:bold;">UNIVERSITAS MUHAMMADIYAH SURABAYA</span>
        <span style="float:right; font-weight:bold;">PERIODE PENILAIAN: {{ $periodeLabel }}</span>
    </div>

    <table class="mb">
        <tr>
            <th class="center" style="width:5%">NO</th>
            <th style="width:45%">PEGAWAI YANG DINILAI</th>
            <th class="center" style="width:5%">NO</th>
            <th style="width:45%">PEJABAT PENILAI KINERJA</th>
        </tr>
        <tr><td class="center">1</td><td><b>NAMA</b> {{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</td><td class="center">1</td><td><b>NAMA</b> {{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</td></tr>
        <tr><td class="center">2</td><td><b>NIP</b> {{ $bawahan->profile->nip ?? '-' }}</td><td class="center">2</td><td><b>NIP</b> {{ $penilai->profile->nip ?? '-' }}</td></tr>
        <tr><td class="center">3</td><td><b>PANGKAT/GOL. RUANG</b> {{ $bawahan->profile->pangkat_golongan ?? '-' }}</td><td class="center">3</td><td><b>PANGKAT/ GOL. RUANG</b> {{ $penilai->profile->pangkat_golongan ?? '-' }}</td></tr>
        <tr><td class="center">4</td><td><b>JABATAN</b> {{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td><td class="center">4</td><td><b>JABATAN</b> {{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td></tr>
        <tr><td class="center">5</td><td><b>UNIT KERJA</b> Universitas Muhammadiyah Surabaya</td><td class="center">5</td><td><b>UNIT KERJA</b> Universitas Muhammadiyah Surabaya</td></tr>
    </table>

    <table class="mb">
        <tr>
            <td style="width:50%"><b>PREDIKAT KINERJA PEGAWAI</b><br>{{ $predikat }}</td>
            <td style="width:50%"><b>POLA DISTRIBUSI:</b><br>{{ $hd->capaian_kinerja_organisasi ? 'Capaian Kinerja Organisasi: ' . $hd->capaian_kinerja_organisasi : 'Belum dinilai oleh Penilai' }}</td>
        </tr>
    </table>

    <table class="tbl-evaluasi mb">
        <colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"></colgroup>
        <tr>
            <th class="center">HASIL KERJA</th>
            <th class="center">REALISASI BERDASARKAN BUKTI DUKUNG</th>
            <th class="center">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
            <th class="center">HASIL KERJA</th>
        </tr>
        <tr class="nowrap-group"><td colspan="4">A. UTAMA</td></tr>
        @forelse($utamaRows as $row)
            @php $u = $row['utama']; @endphp
            <tr>
                <td><b>{{ $loop->iteration }}</b> {{ $u->rencana_hasil_kerja }}
                    <p class="redaksi"><b>Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</b></p>
                    {!! nl2br(e($u->indikator_target)) !!}
                </td>
                <td>
                    @forelse($row['rows'] as $c)
                        @if($c['realisasi'] !== '')
                            <div class="kartu">
                                <div class="card-label">{{ $c['label'] }}</div>
                                <div class="card-isi">{!! nl2br(e(\App\Support\RealisasiRingkas::ringkas($c['realisasi']))) !!}</div>
                            </div>
                        @endif
                    @empty
                        <span style="font-style:italic;">Tidak wajib lampiran</span>
                    @endforelse
                </td>
                <td>{!! nl2br(e($u->umpan_balik ?? '')) !!}</td>
                <td class="center">{{ $u->hasil_kerja ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">Belum ada data Kinerja Utama.</td></tr>
        @endforelse
        <tr class="nowrap-group"><td colspan="4">B. TAMBAHAN</td></tr>
        @forelse($tambahans as $i => $t)
            @php $rt = $realisasis->where('target_type', 'tambahan')->where('target_id', $t->id)->first(); @endphp
            <tr>
                <td><b>{{ $i + 1 }}</b> {!! nl2br(e($t->rencana_hasil_kerja)) !!}</td>
                <td>@if($rt && trim((string)$rt->realisasi) !== ''){!! nl2br(e(\App\Support\RealisasiRingkas::ringkas(trim((string)$rt->realisasi)))) !!}@endif</td>
                <td>{!! nl2br(e($t->umpan_balik ?? '')) !!}</td>
                <td class="center">{{ $t->hasil_kerja ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">Tidak ada target tambahan.</td></tr>
        @endforelse
        <tr class="nowrap-group"><td colspan="4">RATING HASIL KERJA*<br>{{ $ratingHasil }}</td></tr>
    </table>

    <table class="tbl-perilaku mb">
        <colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"></colgroup>
        <tr>
            <th colspan="2" style="text-align:left;">PERILAKU KERJA</th>
            <th class="center">REALISASI PEGAWAI &amp; BUKTI DUKUNG</th>
            <th class="center">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
            <th class="center">HASIL KERJA</th>
        </tr>
        @foreach($perilakuRows as $row)
            @php $p = $row['perilaku']; @endphp
            <tr>
                <td colspan="2">
                    <b>{{ $loop->iteration }}. {{ $p->aspek }}</b><br>{!! nl2br(e($p->indikator)) !!}<br>
                    <b>Ekspektasi Khusus Pimpinan:</b><br>{{ $p->ekspektasi_pimpinan ?? '-' }}
                </td>
                <td>
                    @foreach($row['rows'] as $c)
                        @if($c['realisasi'] !== '')
                            <div class="kartu">
                                <div class="card-label">{{ $c['label'] }}</div>
                                <div class="card-isi">{!! nl2br(e(\App\Support\RealisasiRingkas::ringkas($c['realisasi']))) !!}</div>
                            </div>
                        @endif
                    @endforeach
                </td>
                <td>{!! nl2br(e($p->umpan_balik ?? '')) !!}</td>
                <td class="center">{{ $p->hasil_kerja ?: '-' }}</td>
            </tr>
        @endforeach
        <tr class="nowrap-group"><td colspan="5">RATING PERILAKU KERJA*<br>{{ $ratingPerilaku }}</td></tr>
        <tr class="nowrap-group"><td colspan="5">PREDIKAT KINERJA PEGAWAI*<br>{{ $predikat }}</td></tr>
    </table>
@endif

@if($exportTab === 'dokumen')
    <p class="judul">DOKUMEN EVALUASI KINERJA PEGAWAI</p>
    <div style="overflow:hidden;" class="mb">
        <span style="float:left; font-weight:bold;">PERIODE: {{ $hd->periode->triwulan_label ?? '-' }}<br>{{ $hd->user->fakultas_nama ?? config('institusi.nama_fakultas') }}</span>
        <span style="float:right; font-weight:bold; text-align:right;">PERIODE PENILAIAN:<br>{{ $periodeLabel }}</span>
    </div>

    <table>
        @php $atasan = $hd->atasanPejabatPenilai; @endphp
        <tr class="nowrap-group"><td class="center" style="width:5%">1</td><td colspan="2">PEGAWAI YANG DINILAI</td></tr>
        <tr><td></td><td style="width:30%"><b>NAMA</b></td><td>{{ $bawahan->profile->nama_lengkap ?? $bawahan->name ?? '-' }}</td></tr>
        <tr><td></td><td><b>NIP</b></td><td>{{ $bawahan->profile->nip ?? '-' }}</td></tr>
        <tr><td></td><td><b>PANGKAT/GOL. RUANG</b></td><td>{{ $bawahan->profile->pangkat_golongan ?? '-' }}</td></tr>
        <tr><td></td><td><b>JABATAN</b></td><td>{{ $bawahan->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td></tr>
        <tr><td></td><td><b>UNIT KERJA</b></td><td>Universitas Muhammadiyah Surabaya</td></tr>

        <tr class="nowrap-group"><td class="center">2</td><td colspan="2">PEJABAT PENILAI KINERJA</td></tr>
        <tr><td></td><td><b>NAMA</b></td><td>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</td></tr>
        <tr><td></td><td><b>NIP</b></td><td>{{ $penilai->profile->nip ?? '-' }}</td></tr>
        <tr><td></td><td><b>PANGKAT/GOL. RUANG</b></td><td>{{ $penilai->profile->pangkat_golongan ?? '-' }}</td></tr>
        <tr><td></td><td><b>JABATAN</b></td><td>{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dekan' }}</td></tr>
        <tr><td></td><td><b>UNIT KERJA</b></td><td>Universitas Muhammadiyah Surabaya</td></tr>

        <tr class="nowrap-group"><td class="center">3</td><td colspan="2">ATASAN PEJABAT PENILAI KINERJA</td></tr>
        <tr><td></td><td><b>NAMA</b></td><td>{{ $atasan->profile->nama_lengkap ?? $atasan->name ?? 'Prof. Dr. Mundakir, S.Kep., Ns., M.Kep.' }}</td></tr>
        <tr><td></td><td><b>NIP</b></td><td>{{ $atasan->profile->nip ?? '1974.0323.2005.01.1.002' }}</td></tr>
        <tr><td></td><td><b>PANGKAT/GOL. RUANG</b></td><td>{{ $atasan->profile->pangkat_golongan ?? 'Pembina IV.a' }}</td></tr>
        <tr><td></td><td><b>JABATAN</b></td><td>{{ $atasan ? ($atasan->roles->pluck('name')->implode(', ') ?: 'Rektor') : 'Rektor' }}</td></tr>
        <tr><td></td><td><b>UNIT KERJA</b></td><td>Universitas Muhammadiyah Surabaya</td></tr>

        <tr class="nowrap-group"><td class="center">4</td><td colspan="2">EVALUASI KINERJA</td></tr>
        <tr><td></td><td><b>CAPAIAN KINERJA ORGANISASI (KATEGORI DISTRIBUSI)</b></td><td>{{ strtoupper($hd->capaian_kinerja_organisasi ?? '-') }}</td></tr>
        <tr><td></td><td><b>PREDIKAT KINERJA PEGAWAI</b></td><td>{{ $predikat }}</td></tr>

        <tr class="nowrap-group"><td class="center">5</td><td colspan="2">CATATAN/ REKOMENDASI</td></tr>
        <tr><td></td><td colspan="2">{!! nl2br(e($hd->catatan_penilai ?? '')) !!}</td></tr>
    </table>
@endif

</body>
</html>
