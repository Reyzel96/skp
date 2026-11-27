<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak SKP Dosen Kualitatif</title>
    <link rel="icon" type="image/png" href="{{ asset('images/umsura.png') }}">
    <style>
        @php
            // O4: Batas kata realisasi HANYA untuk kolom realisasi saat print.
            // Indikator/target/ekspektasi TIDAK dibatasi (mengikuti PDF referensi).
            $realisasiMaxWords = 30;
        @endphp
        /* ====== KERTAS & MARGIN ====== */
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ====== WARNA BACKGROUND TETAP TERCETAK (biru muda seperti PDF referensi) ====== */
        thead tr, tr.section-title {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ====== TABEL DASAR ====== */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 14px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.4;
            text-align: left;
            color: #000;
        }
        thead {
            /* Header kolom diulang di setiap halaman cetak */
            display: table-header-group;
        }
        thead th {
            background: #dce6f1;
            font-weight: bold;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ====== BARIS TIDAK TERPOTONG DI TENGAH ====== */
        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        /* Baris yang lebih tinggi dari 1 halaman boleh dipecah (ditandai class .tr-tall oleh server/JS bila perlu).
           Sebagai fallback, tr tinggi diberi pengecualian lewat selector ini. */
        tr.tr-tall {
            page-break-inside: auto;
            break-inside: auto;
        }

        /* ====== JUDUL SEKSI JANGAN YATIM ====== */
        tr.section-title {
            page-break-after: avoid;
            break-after: avoid;
            background: #dce6f1;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        tr.section-title td { font-weight: bold; }

        /* ====== TEKS PANJANG TIDAK KELUAR KERTAS ====== */
        td, th {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .pre-wrap {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* ====== UTILITAS ====== */
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
        .w-12 { width: 36px; }
        .no-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }

        /* Identitas: label & nilai agar kolom rapi antar baris */
        .ident-label {
            display: inline-block;
            width: 42%;
            min-width: 110px;
            vertical-align: top;
            font-weight: bold;
        }
        .ident-value {
            display: inline-block;
            width: 56%;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Proporsi kolom tabel evaluasi */
        .col-target   { width: 36%; }
        .col-realisasi{ width: 24%; }
        .col-feedback { width: 26%; }
        .col-hasil    { width: 14%; }

        .hidden-col { display: none; }

        /* Judul dokumen di atas tiap section */
        .doc-header { text-align: center; margin: 0 0 12px 0; }
        .doc-header p { margin: 2px 0; font-weight: bold; }

        /* Kartu realisasi di dalam sel (dipisah garis) */
        .realisasi-card { padding: 0; }
        .realisasi-card + .realisasi-card {
            border-top: 1px solid #666;
            margin-top: 6px;
            padding-top: 6px;
        }
        .realisasi-label { font-weight: bold; font-size: 11px; margin-bottom: 2px; }

        /* ===== TAMPILAN LAYAR SAJA (tidak mempengaruhi hasil cetak) =====
           Halaman ini memakai satu CSS untuk layar + print. Blok di bawah
           hanya aktif saat dibuka di browser, sehingga tampilan layar lebih
           lega; saat dialog print (media print), blok ini otomatis nonaktif
           dan hasil cetak tetap persis seperti sebelumnya. */
        @media screen {
            body {
                padding: 24px 32px;
                max-width: 1400px;
                margin: 0 auto;
                box-sizing: border-box;
                background: #f3f4f6;
            }
            table {
                margin-bottom: 22px;
                background: #fff;
            }
            th, td { padding: 9px 12px; line-height: 1.55; }
            .doc-header { margin-bottom: 20px; }
            .mb-1 { margin-bottom: 6px; }
            .mb-2 { margin-bottom: 12px; }
            .realisasi-card + .realisasi-card {
                margin-top: 10px;
                padding-top: 10px;
            }
            /* Baris info atas (inline flex gap 12px) diberi jarak tanpa mengubah inline style */
            body > div[style*="display:flex"] {
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
    @php
        // Hanya tab aktif yang dicetak. Parameter ?tab= kosong/tidak valid → default 'skp'.
        $printTab = request()->query('tab', 'skp');
        $validPrintTabs = ['skp', 'lampiran', 'evaluasi', 'dokumen'];
        if (! in_array($printTab, $validPrintTabs, true)) {
            $printTab = 'skp';
        }

        // O4: Ringkas teks realisasi saat print maksimal 30 kata + '…'
        // (mengikuti aturan layar; indikator/target/ekspektasi tidak dibatasi).
        $ringkasRealisasi = function (?string $text) use ($realisasiMaxWords) {
            $text = trim((string) $text);
            if ($text === '') return '-';
            $kata = preg_split('/\s+/u', $text);
            if (count($kata) <= $realisasiMaxWords) return $text;
            return implode(' ', array_slice($kata, 0, $realisasiMaxWords)) . ' …';
        };
    @endphp

    {{-- ================= 1. SKP ================= --}}
    @if($printTab === 'skp')
    @include('user.skp-dosen-kualitatif.print-partials.skp')
    @endif

    {{-- ================= 2. LAMPIRAN ================= --}}
    @if($printTab === 'lampiran')
    @include('user.skp-dosen-kualitatif.print-partials.lampiran')
    @endif

    {{-- ================= 3. EVALUASI ================= --}}
    @if($printTab === 'evaluasi')
    @include('user.skp-dosen-kualitatif.print-partials.evaluasi')
    @endif

    {{-- ================= 4. DOKUMEN EVALUASI ================= --}}
    @if($printTab === 'dokumen')
    @include('user.skp-dosen-kualitatif.print-partials.dokumen')
    @endif

</body>
</html>
