<?php

namespace App\Support;

/**
 * Ringkas teks realisasi berbasis KATA (bukan karakter), per dokumen bukti.
 *
 * Aturan (setara dengan limitRealisasiWords() di resources/js/pdf-bukti-dukung.js):
 * 1. Pilih bagian paling relevan dari teks:
 *    a. Bila ada bagian "Abstract"/"Abstrak" → ambil dari situ.
 *    b. Bila dokumen berformat form (ada label "RINGKASAN REALISASI") → ambil
 *       isi setelah label itu.
 *    c. Selain itu: lewati blok judul/penulis/afiliasi/email/DOI di awal
 *       dokumen dan ambil dari paragraf pertama yang berupa kalimat utuh.
 * 2. Bersihkan whitespace lalu ambil maksimal 30 kata, potong rapi di akhir
 *    kalimat bila memungkinkan, tambahkan " …" bila terpotong. Teks pendek
 *    (≤30 kata) tidak diubah.
 *
 * Sumber tunggal untuk storeRealisasi() (server) — data yang dikirim manual
 * tetap terbatas walau JS dimatikan/di-bypass. Data lama DB tidak diubah;
 * layer tampilan memakai helper ini juga.
 */
class RealisasiRingkas
{
    /** Batas kata per dokumen bukti dukung. */
    public const MAX_WORDS = 30;

    /** Regex label bagian abstrak (id/en): "Abstract", "Abstrak", "ABSTRACT", dst. */
    private const RE_ABSTRAK = '/(?:^|\s)(?:abstract|abstrak)\s*[:.\-–]?\s*/i';

    /** Regex label form "RINGKASAN REALISASI". */
    private const RE_FORM = '/(?:^|\s)ringkasan\s+realisasi\s*[:.\-–]?\s*/i';

    /** Regex indikator baris metadata (bukan kalimat isi): DOI, URL, email. */
    private const RE_METADATA = '/doi\.org|https?:\/\/|[\w.+\-]+@[\w.+\-]+|kata\s*kunci\s*:|keywords?\s*:/i';

    /**
     * Ringkas teks realisasi menjadi maksimal 30 kata (per dokumen).
     */
    public static function ringkas(?string $text, ?int $maxWords = null): string
    {
        $max = $maxWords ?? self::MAX_WORDS;
        $raw = trim((string) $text);

        if ($raw === '') {
            return '';
        }

        // Bersihkan tab/spasi berlebih dulu; BARIS BARU (\n) DIPERTAHANKAN —
        // struktur paragraf dipakai untuk memilih segmen di aturan (c).
        $raw = trim(preg_replace('/[ \t]+/u', ' ', $raw));

        if (self::hitungKata($raw) <= $max) {
            // Teks pendek: cukup rapikan whitespace (termasuk baris baru → spasi).
            return trim(preg_replace('/\s+/u', ' ', $raw));
        }

        $segment = self::pilihSegmen($raw);

        return self::potongKataRapi($segment, $max);
    }

    /**
     * Pilih segmen paling relevan dari teks (abstrak > form > paragraf pertama).
     * Input: teks mentah yang baris barunya masih utuh.
     */
    private static function pilihSegmen(string $raw): string
    {
        // a) Bagian "Abstract"/"Abstrak" — ambil isi setelah label.
        if (preg_match(self::RE_ABSTRAK, $raw, $m, PREG_OFFSET_CAPTURE)) {
            return substr($raw, $m[0][1] + strlen($m[0][0]));
        }

        // b) Form: ambil isi setelah label "RINGKASAN REALISASI".
        if (preg_match(self::RE_FORM, $raw, $m, PREG_OFFSET_CAPTURE)) {
            return substr($raw, $m[0][1] + strlen($m[0][0]));
        }

        // b2) Formulir kinerja SKP.pdf: lewati seluruh header formulir (judul
        //     "SASARAN KINERJA PEGAWAI…", periode, nama, NIP, pangkat, jabatan,
        //     unit kerja, URL, tanggal cetak, footer "Halaman X dari Y") —
        //     ambil dari baris setelah baris identitas terakhir. Terdeteksi
        //     dari judul formulir + label identitas.
        if (preg_match('/(?:^|\n)\s*sasaran\s+kinerja\s+pegawai/i', $raw)) {
            $lines = explode("\n", $raw);
            $lastHeader = -1;
            foreach ($lines as $i => $line) {
                if (preg_match('/(?:nama|nip|pangkat|golongan|jabatan|unit\s+kerja|periode|tahun\s+kinerja|tanggal\s+cetak|dari\s+tanggal|sampai\s+dengan|halaman\s+\d+\s+dari\s+\d+)/i', $line)) {
                    $lastHeader = $i;
                }
            }
            // Ambil baris pertama setelah header yang berisi teks berarti
            // (≥4 kata), fallback: setelah baris identitas terakhir.
            for ($i = $lastHeader + 1; $i < count($lines); $i++) {
                $p = trim($lines[$i]);
                if ($p === '' || $this->terlihatMetadata($p)) {
                    continue;
                }
                if ($this->hitungKata($p) >= 4) {
                    return $p;
                }
            }
            if ($lastHeader >= 0 && $lastHeader + 1 < count($lines)) {
                return implode("\n", array_slice($lines, $lastHeader + 1));
            }
        }

        // c) Lewati blok metadata awal (judul/penulis/afiliasi/email/DOI):
        //    ambil dari paragraf pertama yang berupa kalimat utuh.
        foreach (preg_split('/\n+/u', $raw) as $para) {
            $para = trim($para);
            if ($para === '' || self::terlihatMetadata($para)) {
                continue;
            }
            if (self::terlihatKalimatUtuh($para)) {
                return $para;
            }
        }

        // Fallback: seluruh teks (pemotong kata yang membatasi).
        return $raw;
    }

    /** Baris metadata: mengandung DOI/URL/email/label kata kunci. */
    private static function terlihatMetadata(string $para): bool
    {
        return preg_match(self::RE_METADATA, $para) === 1;
    }

    /**
     * Heuristik "paragraf berupa kalimat utuh": ada tanda akhir kalimat, atau
     * cukup panjang (≥12 kata). Baris judul/nama pendek tanpa tanda baca
     * tidak lolos.
     */
    private static function terlihatKalimatUtuh(string $para): bool
    {
        if (self::hitungKata($para) >= 12) {
            return true;
        }
        return self::hitungKata($para) >= 3 && preg_match('/[.!?]/u', $para) === 1;
    }

    /**
     * Potong di batas kata, mundur ke akhir kalimat (. ! ?) bila memungkinkan.
     */
    private static function potongKataRapi(string $segment, int $max): string
    {
        // Normalisasi whitespace segmen (termasuk \n → spasi) sebelum memotong.
        $segment = trim(preg_replace('/\s+/u', ' ', $segment));
        $words = preg_split('/\s+/u', $segment, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= $max) {
            return $segment;
        }

        $slice = implode(' ', array_slice($words, 0, $max));
        // Mundur ke akhir kalimat terakhir (. ! ?) selama ≥60% batas kata.
        if (preg_match('/^(.*[.!?])\s/u', $slice . ' ', $mm)) {
            $tail = trim($mm[1]);
            if (self::hitungKata($tail) >= (int) floor($max * 0.6)) {
                return $tail . ' …';
            }
        }

        return $slice . ' …';
    }

    /**
     * Hitung kata: pemisah spasi; token tanpa huruf/angka (mis. "-", "|") diabaikan.
     */
    private static function hitungKata(string $text): int
    {
        $count = 0;
        foreach (preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (preg_match('/[\p{L}\p{N}]/u', $token)) {
                $count++;
            }
        }
        return $count;
    }
}
