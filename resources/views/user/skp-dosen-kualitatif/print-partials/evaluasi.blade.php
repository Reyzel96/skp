{{-- SECTION 3: EVALUASI PEGAWAI — dipakai khusus halaman print.
     Rating/predikat dihitung di controller (PHP), bukan Alpine.
     Realisasi & label diambil dari data tersimpan ($realisasis). --}}

@php
    // Helper label singkat kotak realisasi (sama pola dengan show.blade.php)
    $utamaLabel = function ($text, $urutan) {
        $text = trim((string) $text);
        if ($text === '') return 'Sub-Indikator ' . $urutan;
        $t = strtolower($text);
        $map = [
            'penelitian'  => 'Penelitian',
            'riset'      => 'Penelitian',
            'pengabdian' => 'Pengabdian',
            'pengajaran' => 'Pengajaran',
            'pembelajaran'=> 'Pengajaran',
            'pendidikan' => 'Pengajaran',
        ];
        foreach ($map as $kw => $label) {
            if (str_contains($t, $kw)) return $label;
        }
        return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
    };
@endphp

<div class="doc-header">
    <p>EVALUASI KINERJA</p>
    <p>PENDEKATAN HASIL KERJA KUALITATIF</p>
    <p>BAGI PEJABAT ADMINISTRASI DAN PEJABAT PENILAI KINERJA FUNGSIONAL</p>
</div>

<div style="display:flex; justify-content:space-between; align-items:baseline; gap:12px; font-weight:bold; font-size:11px; text-transform:uppercase; margin-bottom:8px;">
    <div style="white-space:nowrap;">UNIVERSITAS MUHAMMADIYAH SURABAYA</div>
    <div style="white-space:nowrap;">PERIODE: {{ $skpKualitatif->skpHeader->periode->triwulan_label ?? '-' }}</div>
    <div style="white-space:nowrap;">PERIODE PENILAIAN: {{ $skpKualitatif->skpHeader->periode->periode_penilaian_label ?? '-' }}</div>
</div>

{{-- Identitas --}}
<table>
    <thead>
        <tr>
            <th class="w-12">NO</th>
            <th style="width:41%">PEGAWAI YANG DINILAI</th>
            <th class="w-12">NO</th>
            <th style="width:41%">PEJABAT PENILAI KINERJA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-center font-bold">1</td>
            <td><span class="ident-label">NAMA</span> <span class="ident-value">{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</span></td>
            <td class="text-center font-bold">1</td>
            <td><span class="ident-label">NAMA</span> <span class="ident-value">{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="text-center font-bold">2</td>
            <td><span class="ident-label">NIP</span> <span class="ident-value">{{ $user->profile->nip ?? '-' }}</span></td>
            <td class="text-center font-bold">2</td>
            <td><span class="ident-label">NIP</span> <span class="ident-value">{{ $penilai->profile->nip ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="text-center font-bold">3</td>
            <td><span class="ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $user->profile->pangkat_golongan ?? '-' }}</span></td>
            <td class="text-center font-bold">3</td>
            <td><span class="ident-label">PANGKAT/GOL. RUANG</span> <span class="ident-value">{{ $penilai->profile->pangkat_golongan ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="text-center font-bold">4</td>
            <td><span class="ident-label">JABATAN</span> <span class="ident-value">{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
            <td class="text-center font-bold">4</td>
            <td><span class="ident-label">JABATAN</span> <span class="ident-value">{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</span></td>
        </tr>
        <tr>
            <td class="text-center font-bold">5</td>
            <td><span class="ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
            <td class="text-center font-bold">5</td>
            <td><span class="ident-label">UNIT KERJA</span> <span class="ident-value">Universitas Muhammadiyah Surabaya</span></td>
        </tr>
    </tbody>
</table>

{{-- Baris CAPAIAN KINERJA ORGANISASI + POLA DISTRIBUSI (chart image) --}}
<table class="no-break">
    <tbody>
        <tr class="section-title">
            <td colspan="2">CAPAIAN KINERJA ORGANISASI*</td>
        </tr>
        <tr>
            <td style="width:50%">{{ $skpKualitatif->skpHeader->capaian_kinerja_organisasi ? strtoupper($skpKualitatif->skpHeader->capaian_kinerja_organisasi) : '-' }}</td>
            <td style="width:50%"></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="font-bold mb-1">POLA DISTRIBUSI:</div>
                @if(($distribusiRiil['state'] ?? 1) === 3)
                    {{-- Keadaan 3: periode ditutup, data cukup → chart dari hasil beku --}}
                    <div style="text-align:center; padding: 6px 0;">
                        <img id="chart-print-img" alt="Grafik Pola Distribusi Kinerja"
                             style="max-width: 100%; display: none;">
                    </div>
                    <div id="chart-print-fallback" class="italic" style="display:none; font-size:11px;">
                        Grafik tidak dapat ditampilkan.
                    </div>
                @elseif(($distribusiRiil['state'] ?? 1) === 2)
                    {{-- Keadaan 2: periode ditutup, data belum mencukupi --}}
                    <div class="italic" style="font-size:11px; padding: 6px 0; text-align:center;">
                        Data distribusi belum mencukupi ({{ $distribusiRiil['total'] }} dari minimal {{ $distribusiRiil['min'] }} pegawai).
                    </div>
                @else
                    {{-- Keadaan 1: periode belum ditutup --}}
                    <div class="italic" style="font-size:11px; padding: 6px 0; text-align:center;">
                        Distribusi pegawai akan tersedia setelah periode ditutup.
                    </div>
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- Tabel evaluasi hasil kerja --}}
<table>
    <thead>
        <tr>
            <th class="col-target">HASIL KERJA</th>
            <th class="col-realisasi">REALISASI BERDASARKAN BUKTI DUKUNG</th>
            <th class="col-feedback">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
            <th class="col-hasil">HASIL KERJA</th>
        </tr>
    </thead>
    <tbody>
        <tr class="section-title">
            <td colspan="4">A. UTAMA</td>
        </tr>
        @forelse($skpUtamas as $uIndex => $skpUtama)
        @php
            $snapshotItems = $skpUtama->lampiran_snapshot;
            if ($snapshotItems === null && $skpUtama->acuanParent && $skpUtama->acuanParent->children) {
                $utamaChildren = $skpUtama->acuanParent->children->where('butuh_lampiran', true)->values();
            } else {
                $utamaChildren = collect($snapshotItems ?? [])->map(function ($item) {
                    $label = $item['label'] ?? null;
                    if (!is_null($item['child_id'] ?? null)) {
                        $child = \App\Models\SkpKualitatifAcuanChild::find($item['child_id']);
                        if ($child) {
                            return (object) [
                                'id' => $child->id,
                                'indikator_kinerja' => $child->indikator_kinerja,
                                'label_lampiran' => $label,
                            ];
                        }
                    }
                    return (object) ['id' => $item['child_id'] ?? null, 'indikator_kinerja' => '', 'label_lampiran' => $label];
                })->values();
            }
        @endphp
        <tr{{ $skpUtama->rencana_hasil_kerja && mb_strlen($skpUtama->rencana_hasil_kerja) + mb_strlen($skpUtama->indikator_target ?? '') + collect($utamaChildren)->sum(fn($c) => mb_strlen($realisasis->where('target_type', 'utama')->where('acuan_child_id', $c->id)->first()->realisasi ?? '')) > 900 ? ' class="tr-tall"' : '' }}>
            <td class="col-target">
                <div class="font-bold" style="display:inline;">{{ $uIndex + 1 }}.</div>
                {{ $skpUtama->rencana_hasil_kerja }}
                <div class="font-bold mb-1" style="margin-top:8px;">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                <div class="pre-wrap">{{ $skpUtama->indikator_target }}</div>
            </td>
            <td class="col-realisasi">
                @if($utamaChildren->isNotEmpty())
                    @foreach($utamaChildren as $cIdx => $child)
                        @php
                            $rChild = $realisasis->where('target_type', 'utama')->where('acuan_child_id', $child->id)->values();
                            $childRow = $rChild->first();
                            $cLabel = $childRow->label_realisasi
                                ?? ($child->label_lampiran ?? $utamaLabel($child->indikator_kinerja, $cIdx + 1));
                        @endphp
                        <div class="realisasi-card">
                            <div class="realisasi-label">{{ $cLabel }}</div>
                            <div class="pre-wrap">{{ $ringkasRealisasi($childRow->realisasi ?? null) }}</div>
                        </div>
                    @endforeach
                @else
                    <span class="italic">Tidak wajib lampiran</span>
                @endif
            </td>
            <td class="col-feedback"><div class="pre-wrap">{{ $skpUtama->umpan_balik ?? '-' }}</div></td>
            <td class="col-hasil text-center font-bold">{{ $skpUtama->hasil_kerja ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center italic">Belum ada data Kinerja Utama.</td>
        </tr>
        @endforelse

        <tr class="section-title">
            <td colspan="4">B. TAMBAHAN</td>
        </tr>
        @forelse($tambahans as $index => $tambahan)
        @php
            $rTambahan = $realisasis->where('target_type', 'tambahan')->where('target_id', $tambahan->id)->values();
        @endphp
        <tr>
            <td class="col-target">
                <div class="font-bold" style="display:inline;">{{ $index + 1 }}.</div>
                <span class="pre-wrap">{{ $tambahan->rencana_hasil_kerja }}</span>
            </td>
            <td class="col-realisasi">
                @if($rTambahan->count() > 0)
                    @foreach($rTambahan as $ri => $r)
                        <div class="realisasi-card">
                            <div class="realisasi-label">{{ $ri + 1 }}.</div>
                            <div class="pre-wrap">{{ $ringkasRealisasi($r->realisasi ?? null) }}</div>
                        </div>
                    @endforeach
                @else
                    <span class="italic">-</span>
                @endif
            </td>
            <td class="col-feedback"><div class="pre-wrap">{{ $tambahan->umpan_balik ?? '-' }}</div></td>
            <td class="col-hasil text-center font-bold">-</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center italic">Tidak ada target tambahan.</td>
        </tr>
        @endforelse
        <tr>
            <td colspan="4" class="font-bold">RATING HASIL KERJA*: {{ $rating !== '' ? $rating : '-' }}</td>
        </tr>
    </tbody>
</table>

{{-- Tabel perilaku kerja (O2: 4 kolom, kolom realisasi dihapus sesuai PDF referensi) --}}
<table>
    <thead>
        <tr>
            <th style="width:8%">NO</th>
            <th style="width:57%">PERILAKU KERJA</th>
            <th style="width:25%">UMPAN BALIK BERKELANJUTAN BERDASARKAN BUKTI DUKUNG</th>
            <th style="width:10%">HASIL KERJA</th>
        </tr>
    </thead>
    <tbody>
        @foreach($perilakus as $index => $perilaku)
        @php
            // Baris sangat panjang boleh terpotong antar halaman (pola sama
            // dengan baris utama >900 char) agar tidak ada ruang kosong besar.
            $perilakuPanjang = mb_strlen($perilaku->aspek)
                + mb_strlen($perilaku->indikator ?? '')
                + mb_strlen($perilaku->ekspektasi_pimpinan ?? '')
                + mb_strlen($perilaku->umpan_balik ?? '') > 900;
        @endphp
        <tr{{ $perilakuPanjang ? ' class="tr-tall"' : '' }}>
            <td class="text-center font-bold" style="width:8%">{{ $index + 1 }}</td>
            <td style="width:57%">
                <div class="font-bold {{ strtolower($perilaku->aspek) == 'kepemimpinan' ? 'italic' : '' }}">{{ $perilaku->aspek }}</div>
                <div class="pre-wrap" style="padding-left: 12px;">{{ $perilaku->indikator }}</div>
                <div class="font-bold mb-1" style="margin-top:8px;">Ekspektasi Khusus Pimpinan:</div>
                <div class="pre-wrap italic">{{ $perilaku->ekspektasi_pimpinan ?? '-' }}</div>
            </td>
            <td style="width:25%"><div class="pre-wrap">{{ $perilaku->umpan_balik ?? '-' }}</div></td>
            <td class="text-center font-bold" style="width:10%">{{ $perilaku->hasil_kerja ?? '-' }}</td>
        </tr>
        @endforeach

        <tr>
            <td colspan="4" class="font-bold">RATING PERILAKU KERJA*: {{ $ratingPerilaku !== '' ? $ratingPerilaku : '-' }}</td>
        </tr>
        <tr>
            <td colspan="4" class="font-bold">PREDIKAT KINERJA PEGAWAI*: {{ $predikatKinerja !== '' ? $predikatKinerja : '-' }}</td>
        </tr>
    </tbody>
</table>

{{-- O3: Render grafik POLA DISTRIBUSI sebagai IMAGE (canvas → toDataURL)
     sebelum dialog print dibuka. Konfigurasi identik dengan renderChart()
     di show.blade.php. Image sudah siap saat halaman selesai dimuat.
     Hanya dimuat pada keadaan 3 (periode ditutup, data cukup); data = hasil beku riil. --}}
@if(($distribusiRiil['state'] ?? 1) === 3)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var img = document.getElementById('chart-print-img');
    var fallback = document.getElementById('chart-print-fallback');
    if (!img || typeof Chart === 'undefined') {
        if (fallback) fallback.style.display = 'block';
        return;
    }

    var data = @json($distribusiRiil['data']);
    var labels = ['Sangat Kurang', 'Kurang/Misconduct', 'Butuh Perbaikan', 'Baik', 'Sangat Baik'];

    var canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 320;
    document.body.appendChild(canvas);
    canvas.style.display = 'none';

    var chart = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Frekuensi Pegawai',
                data: data,
                borderColor: 'rgba(99, 102, 241, 1)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: false,
            animation: false,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'FREKUENSI PEGAWAI' }, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });

    // Konversi canvas → PNG data URL untuk <img> yang dicetak.
    img.src = chart.toBase64Image();
    img.style.display = 'inline-block';
    if (fallback) fallback.style.display = 'none';
    chart.destroy();
    canvas.remove();
})();
</script>
@endif
