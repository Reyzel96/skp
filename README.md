# Sistem SKP — Aplikasi Penilaian Kinerja
### Universitas Muhammadiyah Surabaya

> **Update v1.2**

Aplikasi berbasis **Laravel** untuk mengelola siklus **SKP (Sasaran Kinerja Pegawai)** — mulai dari penyusunan target, pengisian realisasi oleh pegawai, penilaian kualitatif oleh atasan (Kaprodi/Dekan/Rektor), hingga rekap evaluasi kinerja.

---

## Daftar Isi

- [Tentang Aplikasi](#tentang-aplikasi)
- [Alur & Role Pengguna](#alur--role-pengguna)
- [Instalasi](#instalasi)
- [Riwayat Perbaikan](#riwayat-perbaikan)
- [Temuan Belum Diperbaiki](#temuan-belum-diperbaiki)
- [Struktur File Terkait](#struktur-file-terkait)
- [Riwayat Versi](#riwayat-versi)
- [Kontribusi](#kontribusi)
- [Pengembang](#pengembang)

---

## Tentang Aplikasi

Sistem ini menangani dua pendekatan penilaian SKP:

- **SKP Kuantitatif** — untuk Tendik/Dosen dengan target terukur berbasis angka.
- **SKP Kualitatif** — pendekatan hasil kerja kualitatif bagi pejabat administrasi/fungsional, mencakup penilaian **Hasil Kerja (Utama & Tambahan)** dan **Perilaku Kerja** (Berorientasi Pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif).

Setiap SKP melewati alur: **penyusunan target → pengisian realisasi & bukti dukung → evaluasi/penilaian atasan → rekap hasil akhir**.

## Alur & Role Pengguna

| Role | Akses Utama |
|---|---|
| **Pegawai / Dosen** | Menyusun target SKP, mengisi realisasi & bukti dukung |
| **Kaprodi / Sekprodi** | Menilai bawahan langsung, approval target tingkat prodi |
| **Dekan / Wakil Dekan** | Menilai bawahan, approval target tingkat fakultas |
| **Rektor / Rektorat** | Menilai bawahan, approval target tingkat universitas, bypass akses monitoring |
| **Super Admin** | Mengelola master data SKP, periode penilaian, dan seluruh user |

---

## Instalasi

```bash
# Clone repository
git clone <url-repo-anda>
cd <nama-folder-project>

# Install dependency PHP
composer install

# Install dependency frontend (jika diperlukan)
npm install && npm run build

# Salin file environment dan generate app key
cp .env.example .env
php artisan key:generate

# Atur koneksi database di file .env, lalu jalankan migrasi
php artisan migrate

# (Opsional) jalankan seeder jika tersedia
php artisan db:seed

# Jalankan server lokal
php artisan serve
```

Aplikasi dapat diakses melalui `http://127.0.0.1:8000`.

---

## Riwayat Perbaikan

### 1. Fix Halaman Penilai (Evaluasi Bawahan)

**File terkait:** `EvaluasiBawahanController.php`, `show-kualitatif.blade.php`

| # | Masalah |
|:-:|---------|
| **FIX 1** | Data `$skpUtamas` & `$perilakuAcuan` belum diambil di controller |
| **FIX 2** | Perhitungan `rating()` hanya bisa menangani 2 nilai hardcode (`utama1`, `utama2`) |
| **FIX 3** | Section A (UTAMA) hardcode selalu 2 baris, tidak mengikuti data asli |
| **FIX 4** | Section D (PERILAKU KERJA): pengecekan `butuh_lampiran` diambil dari field yang salah, sehingga logikanya terbalik |

**Detail perbaikan:**
- **FIX 1** → Ditambahkan 2 query baru, dimasukkan ke `compact()`.
- **FIX 2** → Diubah menjadi dinamis: mengambil rata-rata dari `state.utamaScores[]`, berapa pun jumlah datanya.
- **FIX 3** → Diganti dengan `@forelse($skpUtamas...)`, jumlah baris mengikuti data asli, tiap sub-indikator mendapat kotak bukti sendiri.
- **FIX 4** → Pengecekan diperbaiki dengan mengecek dari `acuanParent->children` yang cocok dengan `sasaran_kinerja`, mengikuti pola yang sama seperti Section A.
- **Tambahan (v1.2):** Section A & D di halaman Penilai juga dibuat versi **read-only** (teks + link file, tanpa input), mirror pola halaman Dosen. Query realisasi diperbaiki dari `target_type='utama1'/'utama2'` → `target_type='utama'` + `acuan_child_id`.

**Status:** ✅ Sudah diverifikasi langsung di browser, render dengan benar.

---

### 2. Fix Keamanan Halaman Pegawai (Isi Realisasi)

**File terkait:** `SkpDosenKualitatifController.php` (User)

| # | Masalah |
|:-:|---------|
| **FIX 5** | `storeRealisasi()` tidak mengunci status — pegawai bisa mengubah realisasi kapan saja, bahkan setelah status SKP mencapai *Review Akhir*/*Selesai* |
| **FIX 6** | `storeRealisasi()` tidak mengecek kepemilikan data — user lain berpotensi mengubah realisasi SKP milik orang lain jika mengetahui `header_id` |

**Detail perbaikan:**
- **FIX 5** → Ditambahkan pengecekan status; permintaan ditolak jika status sudah *Review Akhir*/*Selesai*.
- **FIX 6** → Ditambahkan `abort(403)` jika `$header->user_id !== Auth::id()`, diletakkan di luar `try-catch` agar tidak tertangkap oleh `catch(\Exception)`.

**Status:** ✅ Lock status sudah diverifikasi langsung — pesan error muncul dengan benar dan data tidak jadi tersimpan.

---

### 3. Fix Tampilan Cetak/Print

**Masalah awal:** Saat menekan `Ctrl+P` di halaman Detail SKP Dosen Kualitatif (sisi Penilai maupun Pegawai), hasil cetak menampilkan seluruh halaman apa adanya — sidebar, header, tombol, judul, dan tab navigasi ikut tercetak. Tabel lebar juga sebagian terpotong karena mengikuti area scroll horizontal, bukan lebar penuh kertas.

**File terkait:**
- `resources/views/layouts/app.blade.php` (layout global, berlaku untuk semua role)
- `resources/views/penilai/evaluasi/show-kualitatif.blade.php` (halaman Penilai/Dekan/Rektor)
- `resources/views/user/skp-dosen-kualitatif/show.blade.php` (halaman Pegawai)

**Perbaikan yang dilakukan:**

| # | Lokasi | Perbaikan |
|:-:|--------|-----------|
| **P1** | `layouts/app.blade.php` | `id="print-area"` pada `<main>` + CSS `@media print` global: menyembunyikan semua elemen di luar `#print-area`, memaksa area cetak full-width dengan `overflow: visible`, orientasi kertas **landscape** |
| **P2** | `layouts/app.blade.php` | `#print-area table { table-layout: auto; width: 100%; }` agar lebar kolom tabel otomatis menyesuaikan saat ada kolom yang disembunyikan |
| **P3** | `show-kualitatif.blade.php` (Penilai) | Class `no-print` pada judul + tombol "Kembali", dan tab navigasi (SKP Pegawai/Lampiran/Evaluasi/Dokumen) |
| **P4** | `show-kualitatif.blade.php` (Penilai) | Class `no-print` pada kolom **"Realisasi Pegawai & Bukti Dukung"** (header + isi) khusus section **D. Perilaku Kerja** |
| **P5** | `user/skp-dosen-kualitatif/show.blade.php` (Pegawai) | Patch yang sama seperti P3 & P4, diselaraskan dengan mekanisme print (`class="area-cetak"`) yang sudah ada sebelumnya di file ini |

**Status:** ✅ Sudah diverifikasi via print preview di halaman Penilai dan Pegawai — sidebar/header/judul/tab hilang, kolom Realisasi & Bukti Dukung di section Perilaku Kerja tersembunyi, tabel tercetak rapi tanpa terpotong.

> **Catatan:** Kolom "Realisasi Berdasarkan Bukti Dukung" di section **A. UTAMA** belum disembunyikan saat print — sesuai permintaan yang hanya menyasar section Perilaku Kerja.

---

### 4. Fix Bug "Wajib Lampiran" Terbalik (Section D)

**Akar masalah:** Field `butuh_lampiran` di **parent** salah diisi (harusnya baca dari **child**), sehingga logika di Section D sempat terbalik meski Section A sudah benar.

**Perbaikan:** Mirror logic Section A — baca `acuanParent->children->contains('butuh_lampiran', true)`. Sempat muncul lagi di ronde kedua (banyak grup salah tampil padahal cuma 1 yang dicentang), namun sudah diselesaikan dengan penyesuaian logic per-grup.

**Status:** ✅ Selesai

---

### 5. Kotak Upload Dinamis Per Sub-Indikator

- Jumlah kotak upload mengikuti jumlah child yang dicentang lampiran (bukan 1 kotak generic lagi)
- Setiap kotak punya label otomatis (`utamaLabel()` / `perilakuLabel()`)
- File terikat ke `acuan_child_id` (kolom baru via migration)
- Diterapkan di Section A lalu Section D — test case AKUNTABEL (3 sub-indikator tercentang → 3 kotak terpisah) ✅

**Status:** ✅ Selesai & terverifikasi

---

### 6. Toggle "Wajib Lampiran" untuk Perilaku Kerja

- Migration kolom `butuh_lampiran` di `skp_kualitatif_acuan_parents` & `skp_kualitatif_perilakus`
- Superadmin bisa toggle per aspek di halaman Acuan (auto-save)
- Flag otomatis ter-copy ke SKP baru saat dibuat
- Pegawai: input aktif sesuai flag, terkunci permanen setelah simpan
- Dekan: read-only + link download

**Status:** ✅ Selesai & terverifikasi

---

### 7. Rapikan Tampilan Tabel Perilaku Kerja

Proporsi lebar kolom final:

| Kolom | Lebar |
|---|---|
| PERILAKU KERJA (colspan 2) | 45% |
| REALISASI PEGAWAI & BUKTI DUKUNG | 25% |
| UMPAN BALIK BERKELANJUTAN | 20% |
| HASIL KERJA | 10% |

Kolom "ANGKA" (hidden, tidak dipakai perhitungan) dihapus karena bikin area kosong. Diterapkan konsisten di sisi Pegawai & Penilai.

**Status:** ✅ Selesai

---

## Temuan Belum Diperbaiki

> Bersifat informasional — belum di-fix, dicatat sebagai catatan/risiko.

**#7 — Validasi penilaian "HASIL KERJA" masih manual**
Penilaian *Diatas/Sesuai/Dibawah Ekspektasi* di kedua level penilai (Dekan & Rektor) tidak divalidasi otomatis terhadap bukti dukung. Sistem hanya berfungsi sebagai wadah input — keputusan nilai sepenuhnya manual/subjektif. Ini bukan bug, melainkan desain saat ini.

**#8 — Alur approval target di jalur Kualitatif tidak ditemukan**
Alur "Dekan konfirmasi di awal" (sebelum pegawai mengisi realisasi) tidak ditemukan di jalur Kualitatif — SKP langsung dibuat berstatus *Draft* oleh Super Admin, tanpa tahap approval target seperti pada jalur SKP Tendik/Dosen biasa (`is_target_approved_penilai` / `is_target_approved_atasan`). Kemungkinan gap, atau ada mekanisme lain yang belum teridentifikasi.

**#9 — Pola "hapus semua → insert ulang" pada `storeRealisasi()`**
Jika terjadi submission partial atau error JS, data lama yang seharusnya tetap ada berisiko ikut terhapus. Belum diperbaiki, hanya dicatat sebagai risiko.

**#10 — Dua Sistem SKP Tendik (orphaned code)**
Ditemukan 2 controller berbeda yang sama-sama bisa membuat SKP Tendik: **Sistem A** (`MasterSkpTendikController`, aktif, terhubung sidebar) dan **Sistem B** (`SuperAdminController::skpTendikCreate`, route masih aktif di `web.php` tapi tidak ada link UI ke sana — diperkuat bukti file `SuperAdminController.php.bak`). Kedua controller berbagi 1 view (`super-admin/skp-tendik/index.blade.php`) dengan variabel berbeda → berpotensi error `Undefined variable` jika Sistem B diakses langsung. Belum diputuskan apakah boleh dihapus — perlu konfirmasi ke pemberi kerja dulu.

---

## Struktur File Terkait

```
app/Http/Controllers/
├── Penilai/
│   └── EvaluasiBawahanController.php
└── SkpDosenKualitatifController.php

resources/views/
├── layouts/
│   └── app.blade.php
├── penilai/evaluasi/
│   └── show-kualitatif.blade.php
└── user/skp-dosen-kualitatif/
    └── show.blade.php
```

---

## Riwayat Versi

| Versi | Ringkasan |
|---|---|
| **v1.0** | Fix awal: Halaman Penilai (FIX 1–4), Keamanan Realisasi (FIX 5–6), Tampilan Print (P1–P5), temuan #7–#9 dicatat |
| **v1.1** | Bug "Wajib Lampiran" Section D diperbaiki, kotak upload dinamis per sub-indikator (Section A & D), toggle "Wajib Lampiran" Perilaku Kerja, rapikan tabel Perilaku Kerja |
| **v1.2** | Section A & D halaman Penilai dibuat read-only (mirror Dosen), fix query `target_type`/`acuan_child_id` di sisi Penilai, temuan #10 (Dua Sistem SKP Tendik / orphaned code) ditambahkan |

---

## Kontribusi

Repository ini dikembangkan secara internal. Untuk kontribusi:
1. Buat branch baru dari `main`/`develop`.
2. Pastikan perubahan diuji langsung di lingkungan lokal sebelum merge.
3. Sertakan deskripsi perubahan yang jelas pada setiap pull request.

---

## Pengembang

**エイゼン**
**アシャハブル・カハフィ**