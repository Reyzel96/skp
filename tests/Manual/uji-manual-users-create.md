# Uji Manual — Tambah pegawai (`/superadmin/users/create`) setelah redesain Tugas 25

Halaman: `/superadmin/users/create` (login sebagai admin@gmail.com / Super Admin).
Prasyarat: `npm run build` sudah dijalankan (kelas Tailwind baru: `max-w-[1280px]`, `text-[13px]`); Turbo termuat (cache browser di-clear sekali).

Perubahan yang diverifikasi:
1. Layout 2 kolom ≥1024px (kiri: Data pegawai + Akun; kanan: Jabatan dan peran), max-w 1280px, header judul kiri + Batal/Simpan kanan.
2. Form `data-turbo="false"`, TANPA `onsubmit="confirm(...)"`, `novalidate` + handler `invalid` (fokus ke field pertama bermasalah).
3. JS pola T16: pemicu ganda (DOMContentLoaded + eksekusi langsung saat navigasi Turbo), guard `dataset.userCreateFormInited`, cleanup `turbo:before-cache`.
4. Pangkat/Golongan: dropdown disabled sampai Jenjang dipilih; opsi dari endpoint DB `/lookup/pangkat-golongan/by-jenjang/{id}`; old() dipulihkan server-side.
5. Peran tendik: chip checkbox grid, lencana "N dipilih", mutual exclusivity dengan Jabatan utama (dipertahankan persis).
6. Kartu "Ringkasan" (strip horizontal) murni tampilan — TIDAK punya atribut `name`, tidak terkirim ke server.

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 1 | Buka via **sidebar**: Kelola User → tombol "Tambah" | Halaman create tampil, judul header browser "Tambah pegawai", layout 2 kolom di ≥1024px |
| 2 | Buka via **URL langsung**: ketik `/superadmin/users/create` | Sama dengan langkah 1 (JS tetap terpasang — pilih Jenjang → Pangkat terisi) |
| 3 | Klik menu lain (mis. Kelola User) lalu **Back**; pilih Jenjang Jabatan | Pangkat/Golongan langsung aktif (bukan disabled) dan terisi opsi dari DB → **JS terpasang ulang oleh Turbo** |
| 4 | **Forward** lalu **Back** lagi; ulangi langkah 3 | Tetap berfungsi; tidak ada error console `TypeError ... null`; pilih Jenjang lain → Pangkat di-reset (bukan dobel-opsi) |
| 5 | Isi semua field valid (email unik, password ≥8 + konfirmasi, NIP unik, pilih role utama Dosen) → Simpan | Konfirmasi TIDAK muncul (dihapus); langsung submit; redirect ke `/superadmin/users` dengan pesan sukses; user + profil + role tersimpan |
| 6 | Kosongkan email lalu Simpan | Banner error merah di atas + ring merah di field; **field email pertama yang invalid di-fokus & di-scroll ke tengah**; tombol Simpan TIDAK terkunci; semua nilai isian lain masih ada (old()) |
| 7 | Isi email tapi kosongkan password konfirmasi → Simpan | Error "password confirmation" tampil di bawah field; old() semua field utuh |
| 8 | Pilih Jenjang Jabatan → lihat Pangkat; ganti Jenjang | Pangkat terisi sesuai jenjang; ganti jenjang → Pangkat di-reset ke placeholder, tetap disabled bila jenjang dikosongkan |
| 9 | Pilih Jabatan utama (mis. Dosen) | Chip peran tendik ter-disable + lencana menyesuaikan; kosongkan jabatan utama → chip aktif lagi; centang 1 chip → Jabatan utama ter-disable |
| 10 | Isi data lalu **refresh (F5)** setelah validasi gagal di server (mis. NIP duplikat) | NIP, jenjang, pangkat (termasuk terpilih), input manual pangkat, nama, unit kerja — semua terpulihkan dari old() |
| 11 | Cek resolusi **1366×768** | Layout 2 kolom; seluruh kartu terlihat nyaman, scroll vertikal kecil; tidak ada scroll horizontal |
| 12 | Cek **1680×1050** dan **1920×1080** | Container maks 1280px di tengah; tanpa ruang mati mencolok |
| 13 | Cek **mobile 390px** (DevTools) | 1 kolom; bar aksi Batal/Simpan menempel di bawah (sticky) dengan padding; tanpa scroll horizontal |
| 14 | Setelah semua uji: hapus user uji dari halaman Kelola User | Terhapus; DB bersih dari data uji |

### Snippet konsol (tempel di halaman create)

```js
window.__ujiCreate = () => {
  const r = [];
  const form = document.getElementById('form-user-create');
  r.push(['form ada & action store', !!form && form.action.includes('/superadmin/users')]);
  r.push(['form data-turbo=false', form.getAttribute('data-turbo') === 'false']);
  r.push(['tanpa onsubmit confirm', !form.getAttribute('onsubmit')]);
  // Semua elemen bernama di dalam form
  const luar = [...form.querySelectorAll('[name]')].filter(el => el.closest('form') !== form);
  r.push(['semua name di dalam form', luar.length === 0]);
  // Ringkasan tanpa name (tidak terkirim)
  const ring = [...document.querySelectorAll('[id^="ringkasan-"]')];
  r.push(['ringkasan tanpa name', ring.length > 0 && ring.every(el => !el.hasAttribute('name'))]);
  // Pangkat disabled sebelum jenjang dipilih
  const jenjang = document.getElementById('jenjang_jabatan_id');
  const pangkat = document.getElementById('pangkat_golongan_id');
  r.push(['pangkat disabled saat jenjang kosong', jenjang.value === '' ? pangkat.disabled : true]);
  // Label terhubung
  const labelOk = [...document.querySelectorAll('label[for]')].every(l => document.getElementById(l.htmlFor));
  r.push(['label for terhubung', labelOk]);
  r.forEach(([n, ok]) => console.log((ok ? 'PASS' : 'FAIL') + ' - ' + n));
  return r.every(([, ok]) => ok);
};
```

Jalankan `window.__ujiCreate()` setelah langkah 3 (Back/Forward) — semua cek harus PASS.

### Yang dicari di DevTools

- Console: **tidak boleh** ada `TypeError ... null` saat Back/Forward Turbo.
- Network: Simpan = POST ke `superadmin/users` (302 redirect ke index saat sukses); payload berisi semua `name` kontrak (`email`, `password`, `password_confirmation`, `role_utama`, `roles_tendik[]`, `nama`, `nip`, `jenjang_jabatan_id`, `pangkat_golongan_id`, `pangkat_golongan` bila manual, `jabatan_fungsional`, `unit_kerja`) — **tanpa** field ringkasan.
