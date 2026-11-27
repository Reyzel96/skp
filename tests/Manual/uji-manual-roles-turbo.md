# Uji Manual Browser — Halaman Roles & Navigasi Turbo

Halaman: `/superadmin/roles` (login sebagai admin@gmail.com / Super Admin).

Prasyarat: `npm run dev` berjalan (Vite) + `php artisan serve`, cache browser di-clear sekali
agar Turbo terbaru termuat. Uji di Chrome/Edge/Firefox terbaru.

---

## Bagian 1 — Navigasi Turbo: Roles → menu lain → Back/Forward → Roles

Tujuan: membuktikan JS checkbox bulk tetap hidup setelah Turbo Drive memulihkan halaman
dari cache (`turbo:before-cache` → `cleanupRolesPage` menghapus flag `dataset.rolesInit`,
`turbo:load` → `initRolesPage` memasang ulang listener).

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 1.1 | Buka `/superadmin/roles` | Tabel 53 role tampil; kolom checkbox terisi untuk role non-bawaan yang tak terpakai |
| 1.2 | Klik menu lain di sidebar (mis. *Periode SKP*) | Halaman pindah tanpa reload penuh (Turbo) |
| 1.3 | Tekan tombol **Back** browser | Kembali ke roles, dirender dari Turbo cache |
| 1.4 | Jalankan `window.__ujiRoles()` (snippet di bawah) | Semua cek `PASS` |
| 1.5 | Tekan **Forward** → kembali → jalankan `window.__ujiRoles()` lagi | Semua cek `PASS` |
| 1.6 | Klik 2 checkbox role uji → tombol "Hapus Terpilih" aktif dan labelnya benar, lalu klik menu lain → Back → centang berubah → tombol tetap mengikuti jumlah centang | Tombol update real-time, TIDAK dobel-bind (1 klik = 1 perubahan status) |
| 1.7 | Klik "Pilih Semua" 2× cepat | Semua checkbox centang lalu kosong; tidak ada error di console |

### Snippet konsol (tempel sekali per halaman roles)

```js
window.__ujiRoles = () => {
  const r = [];
  const table = document.querySelector('table');
  r.push(['tabel ada', !!table]);
  r.push(['flag init terpasang', table?.dataset.rolesInit === '1']);
  const cbs = document.querySelectorAll('.role-checkbox');
  r.push(['checkbox bulk ada', cbs.length > 0]);
  // Deteksi dobel-bind: klik checkbox pertama, hitung perubahan event
  const cb = cbs[0];
  if (cb) {
    let fires = 0;
    cb.addEventListener('change', () => fires++);
    cb.click();
    r.push(['1 klik = 1 event (tidak dobel-bind)', fires === 1]);
    cb.click(); // kembalikan
  }
  const btn = document.querySelector('[data-bulk-delete-btn], #bulkDeleteBtn, form[action*="bulk-destroy"] button');
  r.push(['tombol bulk delete ada', !!btn]);
  r.forEach(([n, ok]) => console.log((ok ? 'PASS' : 'FAIL') + ' - ' + n));
  return r.every(([, ok]) => ok);
};
```

### Yang dicari di DevTools Console

- **Tidak boleh** muncul `TypeError: ... null` saat Back/Forward.
- **Tidak boleh** ada duplikasi listener (tanda: 1 klik mengubah tombol 2×/checkbox melompat 2 state).

## Bagian 2 — Checkbox bulk delete & proteksi

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 2.1 | Cari baris "Super Admin", "Dekan", role dengan badge "Sedang Digunakan" | TIDAK ada checkbox di ketiganya |
| 2.2 | Cari role dengan badge "Bawaan" | Tombol Hapus tidak ada, ada label "Terlindungi" |
| 2.3 | Buat role uji "Staf Uji" (dashboard Pegawai) → centang → Hapus Terpilih → konfirmasi | Role terhapus, muncul pesan sukses |
| 2.4 | Centang role uji + coba submit form bulk dengan DevTools: hapus atribut disabled, sisipkan `<input name="ids[]" value="<id Super Admin>">` lalu submit | Ditolak server: pesan role bawaan tidak dapat dihapus (proteksi server-side, bukan cuma UI) |
| 2.5 | Edit role bawaan (mis. Dekan) via tombol Edit | Modal terbuka; input nama readonly abu-abu + teks "Role bawaan sistem tidak dapat di-rename..." |
| 2.6 | Edit Super Admin via konsol: buka modal dengan `openEdit` dari Alpine devtools atau set `$data.editData` | Simpan → server balas **403** (lapisan terakhir; UI memang menyembunyikan, server mengunci) |

## Bagian 3 — Permission saat ganti dashboard (sync berbasis selisih)

Sejak Tugas 20, ganti dashboard memakai **sync berbasis selisih**: cabut permission peta
dashboard LAMA yang tidak ada di peta BARU, tambahkan permission peta BARU, permission
di luar kedua peta (mis. `akses-manajemen-anggaran`, `approve-pagu-anggaran`) tidak
disentuh. Edit tanpa mengubah dashboard tidak menyentuh permission sama sekali.
`pegawai.dashboard` = himpunan kosong biasa (peta lama tetap dicabut).

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 3.1 | Edit "Dekan" TANPA mengubah apa pun → simpan | Permission Dekan tetap lengkap (tidak disentuh); TIDAK ada dialog konfirmasi (dashboard tidak berubah); kotak ringkasan tidak muncul |
| 3.2 | Edit "Dekan" → dashboard tetap, catat permission via tinker `Role::findByName('Dekan')->permissions->pluck('name')` | Identik sebelum & sesudah |
| 3.3 | (Role uji, bukan role asli) Dekan uji (pimpinan + anggaran) → kaprodi | `pimpinan` dicabut, `prodi` ditambah, `anggaran` tetap |
| 3.4 | (Role uji) Dekan uji → pegawai.dashboard | `pimpinan` dicabut, `anggaran` tetap (tidak pernah kosong total) |
| 3.5 | (Role uji) pegawai (punya anggaran) → rektorat | `pimpinan` + `rektorat` ditambah, `anggaran` tetap |

Catatan: gunakan role uji untuk 3.3-3.5, bukan role bawaan (role bawaan terkunci
rename/hapus; perubahan dashboard role bawaan memengaruhi banyak user asli).

## Bagian 4 — Pratinjau selisih & konfirmasi di modal edit (Tugas 21A)

Pratinjau dihitung client-side dari peta yang dikirim server
(`window.__dashboardPermissions` = `SuperAdminController::DASHBOARD_PERMISSIONS`);
server tetap sumber kebenaran. Konfirmasi muncul HANYA bila dashboard diubah.

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 4.1 | Edit role uji "Staf Uji" (dashboard Pegawai, tanpa permission) → ganti dashboard ke *Kaprodi* | Kotak biru muncul di bawah dropdown: "Ditambah: akses-dashboard-prodi" + "Permission lainnya tidak berubah." |
| 4.2 | (lanjutan 4.1) Klik "Simpan Perubahan" | Dialog konfirmasi muncul berisi dashboard lama→baru + Dicabut/Ditambah; pilih **Batal** → TIDAK tersimpan (modal tetap terbuka) |
| 4.3 | (lanjutan 4.2) Klik "Simpan Perubahan" lagi → pilih **OK** | Tersimpan; role uji kini punya `akses-dashboard-prodi` |
| 4.4 | Edit role uji lain dengan permission non-peta (mis. `akses-manajemen-anggaran`) → ganti dashboard ke *Pegawai* | Ringkasan menampilkan Dicabut sesuai peta; TIDAK ada peringatan amber (masih punya anggaran) |
| 4.5 | Edit role uji tanpa permission lain → ganti dashboard ke *Pegawai* (dari dashboard berpeta) | Peringatan amber muncul: "⚠ Role ini tidak akan punya permission apa pun." |
| 4.6 | Edit role uji → ganti dashboard → ganti KEMBALI ke nilai awal | Ringkasan hilang (tidak berubah); simpan tanpa dialog |
| 4.7 | Buka modal edit role uji → jalankan navigasi Turbo (menu lain → Back) → buka modal lagi, ganti dashboard | Ringkasan tetap muncul dengan benar (peta & listener hidup setelah Turbo restore) |
| 4.8 | Matikan JS (DevTools → disable JavaScript) → edit role uji, ganti dashboard → submit | Form tetap submit HTML murni (tanpa ringkasan/konfirmasi), server tetap memproses dengan benar |

Catatan: semua uji Bagian 4 hanya pada role uji "Staf Uji" / role uji lain,
bukan role bawaan DB nyata.
