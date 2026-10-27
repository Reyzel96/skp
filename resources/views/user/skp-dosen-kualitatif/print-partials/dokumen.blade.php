{{-- SECTION 4: DOKUMEN EVALUASI KINERJA — dipakai khusus halaman print --}}
<div class="doc-header">
    <img src="{{ asset('images/umsuraicon.png') }}" alt="Logo UMSurabaya" style="height: 96px; margin-bottom: 8px;">
    <p>DOKUMEN EVALUASI KINERJA PEGAWAI</p>
    <p style="font-weight:normal;">PERIODE: {{ $skpKualitatif->skpHeader->periode->triwulan_label ?? '-' }}</p>
</div>

<div style="display:flex; justify-content:space-between; font-weight:bold; font-size:12px; text-transform:uppercase; margin-bottom:8px;">
    <div>
        <p style="margin:0;">PERIODE: {{ $skpKualitatif->skpHeader->periode->triwulan_label ?? '-' }}</p>
        <p style="margin:0;">{{ $skpKualitatif->skpHeader->user->fakultas_nama ?? config('institusi.nama_fakultas') }}</p>
    </div>
    <div style="text-align:right;">
        <p style="margin:0;">PERIODE PENILAIAN:</p>
        <p style="margin:0;">{{ $skpKualitatif->skpHeader->periode->periode_penilaian_label ?? '-' }}</p>
    </div>
</div>

<table>
    <tbody>
        <tr class="section-title">
            <td class="w-12 text-center">1</td>
            <td colspan="3">PEGAWAI YANG DINILAI</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold" style="width:240px;">NAMA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $user->profile->nama_lengkap ?? $user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">NIP</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $user->profile->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">PANGKAT/GOL. RUANG</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $user->profile->pangkat_golongan ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">JABATAN</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $user->roles->pluck('name')->implode(', ') ?: 'Dosen' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">UNIT KERJA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>Universitas Muhammadiyah Surabaya</td>
        </tr>

        <tr class="section-title">
            <td class="w-12 text-center">2</td>
            <td colspan="3">PEJABAT PENILAI KINERJA</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">NAMA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $penilai->profile->nama_lengkap ?? $penilai->name ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">NIP</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $penilai->profile->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">PANGKAT/GOL. RUANG</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $penilai->profile->pangkat_golongan ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">JABATAN</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $penilai->roles->pluck('name')->implode(', ') ?: 'Dekan' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">UNIT KERJA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>Universitas Muhammadiyah Surabaya</td>
        </tr>

        <tr class="section-title">
            <td class="w-12 text-center">3</td>
            <td colspan="3">ATASAN PEJABAT PENILAI KINERJA</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">NAMA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $atasan->profile->nama_lengkap ?? $atasan->name ?? 'Prof. Dr. Mundakir, S.Kep., Ns., M.Kep.' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">NIP</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $atasan->profile->nip ?? '1974.0323.2005.01.1.002' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">PANGKAT/GOL. RUANG</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $atasan->profile->pangkat_golongan ?? 'Pembina IV.a' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">JABATAN</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $atasan ? ($atasan->roles->pluck('name')->implode(', ') ?: 'Rektor') : 'Rektor' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">UNIT KERJA</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>Universitas Muhammadiyah Surabaya</td>
        </tr>

        <tr class="section-title">
            <td class="w-12 text-center">4</td>
            <td colspan="3">EVALUASI KINERJA</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">CAPAIAN KINERJA ORGANISASI (KATEGORI DISTRIBUSI)</td>
            <td class="text-center" style="width:20px;">:</td>
            <td>{{ $skpKualitatif->skpHeader->capaian_kinerja_organisasi ? strtoupper($skpKualitatif->skpHeader->capaian_kinerja_organisasi) : '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="font-bold">PREDIKAT KINERJA PEGAWAI</td>
            <td class="text-center" style="width:20px;">:</td>
            <td class="font-bold">{{ $predikatKinerja !== '' ? $predikatKinerja : '-' }}</td>
        </tr>

        <tr class="section-title">
            <td class="w-12 text-center">5</td>
            <td colspan="3">CATATAN/ REKOMENDASI</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3"><div class="pre-wrap">{{ $skpKualitatif->skpHeader->catatan_penilai ?? '-' }}</div></td>
        </tr>
    </tbody>
</table>
