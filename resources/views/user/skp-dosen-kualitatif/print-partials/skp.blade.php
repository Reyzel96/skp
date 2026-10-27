{{-- SECTION 1: SKP (target) — dipakai khusus halaman print --}}
<div class="doc-header">
    <p>SASARAN KINERJA PEGAWAI</p>
    <p>PENDEKATAN HASIL KERJA KUALITATIF</p>
    <p>BAGI PEJABAT ADMINISTRASI/ FUNGSIONAL</p>
</div>

<div style="display:flex; justify-content:space-between; align-items:baseline; gap:12px; font-weight:bold; font-size:11px; text-transform:uppercase; margin-bottom:8px;">
    <div style="white-space:nowrap;">UNIVERSITAS MUHAMMADIYAH SURABAYA</div>
    <div style="white-space:nowrap;">PERIODE PENILAIAN: {{ $skpKualitatif->skpHeader->periode->periode_penilaian_label ?? '-' }}</div>
</div>

{{-- Tabel identitas BERDIRI SENDIRI agar thead identitas tidak ikut
     terulang di halaman berikutnya saat tabel target panjang berlanjut --}}
<table class="no-break">
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

{{-- Tabel target (HASIL KERJA, A. UTAMA, B. TAMBAHAN, PERILAKU KERJA) --}}
<table>
    <tbody>
        <tr class="section-title">
            <td colspan="4">HASIL KERJA</td>
        </tr>
        <tr>
            <td colspan="4" class="font-bold">A. UTAMA</td>
        </tr>
        <tr>
            <td class="text-center font-bold">1</td>
            <td colspan="3" class="italic">
                {{ $skpKualitatif->rencana_hasil_kerja ?? 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan...' }}
            </td>
        </tr>
        <tr>
            <td class="text-center"></td>
            <td colspan="3">
                <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                <div class="pre-wrap">{{ $skpKualitatif->indikator_target }}</div>
            </td>
        </tr>

        <tr>
            <td class="text-center font-bold">2</td>
            <td colspan="3" class="italic">
                Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi<br>
                (Penugasan dari Rektor)
            </td>
        </tr>
        <tr>
            <td class="text-center"></td>
            <td colspan="3">
                <div class="font-bold mb-1">Ukuran keberhasilan/ Indikator Kinerja Individu, Target :</div>
                <div class="pre-wrap">- Terlaksananya  tugas kepemimpinan dengan baik
- Terlaksananya pembinaan dosen, mahasiswa dan tenaga kependidikan dengan baik
- Meningkatnya persentase prodi yang unggul
- Meningkatnya mutu pembelajaran, penelitian dan pengabdian masyarakat</div>
            </td>
        </tr>
    </tbody>

    <tbody>
        <tr class="section-title">
            <td colspan="4">B. TAMBAHAN</td>
        </tr>
        @forelse($tambahans as $index => $tambahan)
            <tr>
                <td class="text-center font-bold">{{ $index + 1 }}</td>
                <td colspan="3"><div class="pre-wrap">{{ $tambahan->rencana_hasil_kerja }}</div></td>
            </tr>
        @empty
            <tr>
                <td class="text-center font-bold">1</td>
                <td colspan="3" class="italic">Tidak ada target tambahan.</td>
            </tr>
        @endforelse

        <tr class="section-title">
            <td colspan="4">PERILAKU KERJA*</td>
        </tr>
        @foreach($perilakus as $index => $perilaku)
            <tr>
                <td class="text-center font-bold">{{ $index + 1 }}</td>
                <td colspan="2">
                    <div class="font-bold mb-1 {{ strtolower($perilaku->aspek) == 'kepemimpinan' ? 'italic' : '' }}">{{ $perilaku->aspek }}</div>
                    <div class="pre-wrap" style="padding-left: 12px;">{{ $perilaku->indikator }}</div>
                </td>
                <td>
                    <div class="font-bold mb-1">Ekspektasi Khusus Pimpinan:</div>
                    <div class="pre-wrap">{{ $perilaku->ekspektasi_pimpinan ?? '-' }}</div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
