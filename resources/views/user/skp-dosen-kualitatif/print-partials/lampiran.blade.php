{{-- SECTION 2: LAMPIRAN SKP — dipakai khusus halaman print --}}
<div class="doc-header">
    <p>LAMPIRAN SASARAN KINERJA PEGAWAI</p>
</div>

<table>
    <tbody>
        <tr class="section-title">
            <td colspan="2">DUKUNGAN SUMBER DAYA (pegawai, sarpras, anggaran, metode)</td>
        </tr>
        @php $dukungans = $lampirans->where('jenis', 'dukungan_sumber_daya')->values(); @endphp
        @forelse($dukungans as $index => $item)
            <tr>
                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                <td><div class="pre-wrap">{{ $item->deskripsi }}</div></td>
            </tr>
        @empty
            <tr>
                <td class="text-center font-bold w-12">1</td>
                <td class="italic">Belum ada dukungan sumber daya yang diinput.</td>
            </tr>
        @endforelse

        <tr class="section-title">
            <td colspan="2">SKEMA PERTANGGUNGJAWABAN</td>
        </tr>
        @php $skemas = $lampirans->where('jenis', 'skema_pertanggungjawaban')->values(); @endphp
        @forelse($skemas as $index => $item)
            <tr>
                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                <td><div class="pre-wrap">{{ $item->deskripsi }}</div></td>
            </tr>
        @empty
            <tr>
                <td class="text-center font-bold w-12">1</td>
                <td class="italic">Belum ada skema pertanggungjawaban yang diinput.</td>
            </tr>
        @endforelse

        <tr class="section-title">
            <td colspan="2">KONSEKUENSI</td>
        </tr>
        @php $konsekuensis = $lampirans->where('jenis', 'konsekuensi')->values(); @endphp
        @forelse($konsekuensis as $index => $item)
            <tr>
                <td class="text-center font-bold w-12">{{ $index + 1 }}</td>
                <td><div class="pre-wrap">{{ $item->deskripsi }}</div></td>
            </tr>
        @empty
            <tr>
                <td class="text-center font-bold w-12">1</td>
                <td class="italic">Belum ada konsekuensi yang diinput.</td>
            </tr>
        @endforelse
    </tbody>
</table>
