// =========================================================================
// EKSTRAKSI TEKS PDF UNTUK "UPLOAD BUKTI DUKUNG"
// (halaman user/skp-dosen-kualitatif/show.blade.php, tab Evaluasi Pegawai)
//
// Saat pengguna memilih file PDF di input "Upload Bukti", teks di dalam PDF
// dibaca di browser (pdf.js) dan otomatis ditampilkan pada textarea
// "Tuliskan realisasi..." DI KOTAK UPLOAD YANG SAMA — bukan kotak lain.
//
// Prinsip:
// - Hanya file application/pdf (atau berakhiran .pdf) yang diproses.
//   Jenis lain (jpg, docx, xls, dll.) dibiarkan apa adanya: tetap terunggah
//   normal saat submit, textarea tidak disentuh.
// - PDF diproses LOKAL di browser. Tidak ada byte PDF yang dikirim ke
//   server/layanan eksternal untuk ekstraksi.
// - PDF tanpa teks terbaca (hasil scan) atau gagal diproses -> pesan jelas
//   di bawah input file; teks realisasi yang sudah ditulis pengguna TIDAK
//   pernah ditimpa diam-diam.
//
// Dipakai lewat Alpine: <div ... x-init="initPdfBukti($el)"> pada div kotak
// upload di show.blade.php.
// =========================================================================

import * as pdfjsLib from 'pdfjs-dist';
import PdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = PdfWorker;

// Batas aman: jangan menghantui browser dengan PDF raksasa.
const MAX_PDF_PAGES = 50;
const MAX_PDF_BYTES = 20 * 1024 * 1024; // 20 MB

// =========================================================================
// BATAS TEKS REALISASI (SATU TEMPAT UNTUK WEB & PRINT)
// Berbasis KATA (bukan karakter), maksimal REALISASI_MAX_WORDS kata PER
// DOKUMEN bukti dukung. Fungsi limitRealisasiWords() memilih bagian paling
// relevan (Abstrak → RINGKASAN REALISASI form → paragraf pertama berisi
// kalimat utuh, melewati blok judul/penulis/afiliasi/email/DOI) lalu memotong
// rapi di batas kata/akhir kalimat. Logika setara dengan helper server
// App\Support\RealisasiRingkas (app/Support/RealisasiRingkas.php). Fungsi
// diekspos ke window lewat app.js supaya skrip cetak di show.blade.php
// memakai logika pembatasan yang SAMA.
// =========================================================================
export const REALISASI_MAX_WORDS = 30;

/** Regex label abstrak (id/en). */
const RE_ABSTRAK = /(?:^|\s)(?:abstract|abstrak)\s*[:.\-–]?\s*/i;
/** Regex label form "RINGKASAN REALISASI". */
const RE_FORM = /(?:^|\s)ringkasan\s+realisasi\s*[:.\-–]?\s*/i;
/** Indikator baris metadata: DOI, URL, email, label kata kunci. */
const RE_METADATA = /doi\.org|https?:\/\/|[\w.+\-]+@[\w.+\-]+|kata\s*kunci\s*:|keywords?\s*:/i;

/** Hitung kata: token tanpa huruf/angka (mis. "-", "|") diabaikan. */
function countWords(text) {
    let count = 0;
    for (const token of String(text || '').trim().split(/\s+/u)) {
        if (token && /[\p{L}\p{N}]/u.test(token)) count++;
    }
    return count;
}

/** Baris metadata: mengandung DOI/URL/email/label kata kunci. */
function isMetadataLine(para) {
    return RE_METADATA.test(para);
}

/**
 * Heuristik "paragraf berupa kalimat utuh": ada tanda akhir kalimat, atau
 * cukup panjang (≥12 kata). Baris judul/nama pendek tanpa tanda baca tidak lolos.
 */
function looksLikeFullSentence(para) {
    const words = countWords(para);
    if (words >= 12) return true;
    return words >= 3 && /[.!?]/u.test(para);
}

/**
 * Pilih bagian paling relevan dari teks (abstrak > form > paragraf pertama
 * yang berupa kalimat utuh, melewati baris metadata).
 * @param {string} raw Teks mentah (baris baru masih utuh).
 * @returns {string}
 */
function pickRelevantSegment(raw) {
    // a) Bagian "Abstract"/"Abstrak" — ambil isi setelah label.
    const mAbs = raw.match(RE_ABSTRAK);
    if (mAbs && mAbs.index !== undefined) {
        return raw.slice(mAbs.index + mAbs[0].length);
    }

    // b) Form: ambil isi setelah label "RINGKASAN REALISASI".
    const mForm = raw.match(RE_FORM);
    if (mForm && mForm.index !== undefined) {
        return raw.slice(mForm.index + mForm[0].length);
    }

    // c) Lewati blok metadata awal (judul/penulis/afiliasi/email/DOI): ambil
    //    dari paragraf pertama yang berupa kalimat utuh.
    for (const para of raw.split(/\n+/u)) {
        const p = para.trim();
        if (p === '' || isMetadataLine(p)) continue;
        if (looksLikeFullSentence(p)) return p;
    }

    // Fallback: seluruh teks (pemotong kata yang membatasi).
    return raw;
}

/**
 * Ringkas teks realisasi berbasis KATA (per dokumen bukti).
 * @param {string} text
 * @param {number} maxWords Default 30 (REALISASI_MAX_WORDS).
 * @returns {string}
 */
export function limitRealisasiWords(text, maxWords = REALISASI_MAX_WORDS) {
    let raw = String(text || '').trim();
    if (raw === '') return '';

    // Rapikan tab/spasi berlebih; BARIS BARU DIPERTAHANKAN untuk pemilihan
    // segmen (struktur paragraf).
    raw = raw.replace(/[ \t]+/gu, ' ').trim();

    if (countWords(raw) <= maxWords) {
        // Teks pendek: cukup rapikan whitespace (baris baru → spasi).
        return raw.replace(/\s+/gu, ' ').trim();
    }

    const segment = pickRelevantSegment(raw);

    // Normalisasi whitespace segmen (termasuk \n → spasi) lalu potong kata.
    const clean = segment.replace(/\s+/gu, ' ').trim();
    const words = clean.split(/\s+/u).filter(Boolean);
    if (words.length <= maxWords) return clean;

    const slice = words.slice(0, maxWords).join(' ');
    // Mundur ke akhir kalimat terakhir (. ! ?) selama ≥60% batas kata.
    const mEnd = (slice + ' ').match(/^(.*[.!?])\s/u);
    if (mEnd && countWords(mEnd[1]) >= Math.floor(maxWords * 0.6)) {
        return mEnd[1].trim() + ' …';
    }
    return slice + ' …';
}

/**
 * Ekstrak teks dari File PDF di browser.
 * @param {File} file
 * @returns {Promise<string>} Teks hasil ekstraksi (bisa kosong untuk PDF scan).
 * @throws {Error} Pesan ramah pengguna dalam bahasa Indonesia.
 */
export async function extractPdfText(file) {
    if (!(file instanceof File) || file.size === 0) {
        throw new Error('File tidak dapat dibaca. Silakan pilih ulang file PDF.');
    }
    if (file.size > MAX_PDF_BYTES) {
        throw new Error('Ukuran PDF melebihi 20 MB sehingga tidak bisa dibaca otomatis. Isi realisasi secara manual.');
    }

    let buffer;
    try {
        buffer = await file.arrayBuffer();
    } catch (e) {
        throw new Error('File PDF tidak dapat dibaca. Isi realisasi secara manual.');
    }

    let pdf;
    try {
        pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
    } catch (e) {
        throw new Error('File ini bukan PDF yang valid atau rusak. Isi realisasi secara manual.');
    }

    try {
        if (pdf.numPages > MAX_PDF_PAGES) {
            throw new Error(`PDF memiliki ${pdf.numPages} halaman (maksimal ${MAX_PDF_PAGES}) sehingga tidak bisa dibaca otomatis. Isi realisasi secara manual.`);
        }

        const chunks = [];
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const content = await page.getTextContent();
            const pageText = content.items
                .map((it) => (typeof it.str === 'string' ? it.str : ''))
                .join(' ');
            if (pageText.trim() !== '') {
                chunks.push(pageText);
            }
            page.cleanup();
        }

        const text = chunks.join('\n\n').replace(/[ \t]+/g, ' ').trim();
        if (text === '') {
            throw new Error('PDF tidak memiliki teks yang bisa dibaca (kemungkinan hasil scan). Isi realisasi secara manual.');
        }
        return text;
    } finally {
        try { pdf.destroy(); } catch (e) { /* abaikan */ }
    }
}

/**
 * Ekstrak dengan pelaporan progres (opsional dipakai UI).
 * @param {File} file
 * @param {(msg: string) => void} onProgress
 */
export async function extractPdfTextWithProgress(file, onProgress) {
    if (typeof onProgress === 'function') onProgress('Membaca PDF…');
    return extractPdfText(file);
}

// Cari textarea realisasi terdekat SEBELUM elemen kotak upload (arah mundur
// di dokumen), dibatasi pada elemen root (td baris tabel) supaya tidak
// melompat ke baris lain. Ini penting karena pada section A. UTAMA & PERILAKU
// satu <td> bisa berisi beberapa kotak upload (per sub-indikator), masing-
// masing dengan textarea-nya sendiri.
function findNearestPrecedingTextarea(el, root) {
    let node = el;
    while (node && node !== root) {
        let sib = node.previousElementSibling;
        while (sib) {
            const t = sib.matches('textarea') ? sib : sib.querySelector('textarea[name^="realisasi"]');
            if (t) return t;
            sib = sib.previousElementSibling;
        }
        node = node.parentElement;
    }
    return null;
}

/**
 * Helper untuk hooking Alpine di blade:
 * - Pasang listener change pada input file.
 * - Hanya PDF yang diproses; textarea realisasi diisi HANYA bila masih
 *   kosong (tidak menimpa tulisan pengguna).
 * - Non-PDF: pesan status dibersihkan, tidak ada efek lain.
 *
 * uploadBox div kotak upload; textarea realisasi dicari sebagai elemen
 * terdekat SEBELUM kotak upload dalam <td> yang sama (section B. TAMBAHAN
 * pun bekerja — textarea ada di sibling sebelumnya), sehingga setiap PDF
 * selalu mengisi textarea kotak upload miliknya sendiri.
 */
export function attachPdfBuktiHandler({ fileInput, textarea, statusEl, uploadBox, onError }) {
    if (!fileInput) return;
    const box = uploadBox || fileInput.parentElement;
    const resolveTextarea = () => {
        if (textarea) return textarea;
        const scope = fileInput.closest('td') || document;
        return findNearestPrecedingTextarea(box, scope);
    };

    const setStatus = (message, isError) => {
        if (!statusEl) {
            if (isError && typeof onError === 'function') onError(message);
            return;
        }
        statusEl.textContent = message || '';
        statusEl.classList.toggle('hidden', !message);
        statusEl.classList.toggle('text-red-600', !!isError);
        statusEl.classList.toggle('text-emerald-700', !isError);
    };

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0];
        setStatus('', false);

        if (!file) return;

        const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
        if (!isPdf) {
            // Bukan PDF: perlakukan seperti sebelumnya (tanpa ekstraksi).
            return;
        }

        const target = resolveTextarea();
        if (target && target.value.trim() !== '') {
            // Jangan menimpa teks yang sudah ditulis pengguna secara diam-diam.
            setStatus('PDF terdeteksi, tetapi kolom realisasi sudah berisi teks — tidak ada yang diubah.', false);
            return;
        }

        if (target) {
            target.readOnly = true;
            target.placeholder = 'Membaca isi PDF…';
        }
        setStatus('Membaca isi PDF…', false);

        try {
            const text = await extractPdfText(file);
            // Batasi hasil ekstraksi PER DOKUMEN: maksimal 30 kata, diambil
            // dari bagian paling relevan (abstrak/form/paragraf pertama).
            const fullWords = countWords(text);
            const limited = limitRealisasiWords(text, REALISASI_MAX_WORDS);
            if (target) {
                target.value = limited;
                // Sinkronkan ke state Alpine (x-model="item.text") bila ada.
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('resize'));
            }
            const words = countWords(limited).toLocaleString('id-ID');
            const totalWords = fullWords.toLocaleString('id-ID');
            setStatus(
                fullWords > REALISASI_MAX_WORDS
                    ? `Teks dibaca dari PDF dan diringkas ${words}/${REALISASI_MAX_WORDS} kata (dokumen asli ${totalWords} kata). Periksa dan lengkapi sebelum menyimpan.`
                    : `Teks berhasil dibaca dari PDF (${words} kata). Periksa dan rapikan sebelum menyimpan.`,
                false,
            );
        } catch (err) {
            // Gagal / PDF scan / invalid: teks realisasi kosong TIDAK diisi apa pun.
            setStatus((err && err.message) || 'Gagal membaca PDF. Isi realisasi secara manual.', true);
        } finally {
            if (target) {
                target.readOnly = false;
                target.placeholder = 'Tuliskan realisasi...';
            }
        }
    });
}
