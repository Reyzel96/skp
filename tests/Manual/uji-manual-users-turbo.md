# Uji Manual — Kelola User (superadmin/users) setelah perbaikan Tugas 24

Halaman: `/superadmin/users` (login sebagai admin@gmail.com / Super Admin).
Prasyarat: `npm run dev` (Vite) + server berjalan; cache browser di-clear sekali agar Turbo termuat.

Perbaikan yang diverifikasi: (1) tidak ada lagi nested form — form bulk `#bulk-delete-form`
membungkus hanya tabel + checkbox; form hapus per-baris `#hapus-user-{id}` terpisah di luar
bulk, tombol Hapus menunjuknya via atribut `form=`; (2) JS checkbox terpasang ulang di
navigasi Turbo (pola init sama dengan halaman roles).

| # | Langkah | Hasil diharapkan |
|---|---------|------------------|
| 1 | Buka `/superadmin/users` | Tabel user tampil; checkbox hanya untuk user yang boleh dihapus (bukan diri sendiri / user online) |
| 2 | Klik **Edit** pada user mana pun | Langsung ke halaman edit — **TIDAK** ada submit misterius, tidak berpindah dengan pesan error bulk |
| 3 | Klik **Hapus** pada user uji | Dialog konfirmasi "Yakin hapus user ini?" → Batal = tidak terhapus; OK = terhapus. Di Network tab: request DELETE ke `superadmin/users/{id}` (bukan bulk-destroy) |
| 4 | Centang 2 user uji → tombol "Hapus Terpilih (2)" muncul, angka mengikuti centang | Klik → konfirmasi → Network tab: request DELETE `superadmin/users/bulk-destroy` **dengan payload `ids[]` berisi 2 id** (sebelum perbaikan payload kosong) |
| 5 | Klik "Pilih Semua" 2× cepat | Semua checkbox tercentang lalu kosong; tombol bulk mengikuti; tidak ada error console |
| 6 | Klik menu lain → **Back** → jalankan `window.__ujiUsers()` (snippet di bawah) | Semua cek PASS; centang berubah → tombol bulk tetap update (JS terpasang ulang oleh turbo:load) |
| 7 | **Forward** lalu **Back** lagi → ulangi `window.__ujiUsers()` | Tetap PASS (tidak ada dobel-bind: 1 klik = 1 perubahan) |
| 8 | Import User → Buka modal → Batal | Modal tutup; tidak ada submit; (import sendiri tidak diubah) |

### Snippet konsol (tempel sekali per halaman users)

```js
window.__ujiUsers = () => {
  const r = [];
  r.push(['form bulk ada', !!document.getElementById('bulk-delete-form')]);
  const cbs = document.querySelectorAll('.user-checkbox');
  r.push(['checkbox bulk ada', cbs.length > 0]);
  const cb = cbs[0];
  if (cb) {
    let fires = 0;
    cb.addEventListener('change', () => fires++);
    cb.click();
    r.push(['1 klik = 1 event (tidak dobel-bind)', fires === 1]);
    cb.click();
  }
  // ids[] harus di dalam form bulk (terasosiasi)
  const idsInBulk = document.querySelectorAll('#bulk-delete-form input[name="ids[]"]');
  const idsAll = document.querySelectorAll('input[name="ids[]"]');
  r.push(['ids[] terasosiasi ke form bulk', idsAll.length === 0 || idsInBulk.length === idsAll.length]);
  // tombol hapus per-baris menunjuk form yang ada
  const hapusOk = [...document.querySelectorAll('button[form^="hapus-user-"]')]
    .every(b => document.getElementById(b.getAttribute('form')));
  r.push(['tombol hapus menunjuk form yang ada', hapusOk]);
  const btn = document.getElementById('btn-bulk-delete');
  r.push(['tombol bulk delete ada', !!btn]);
  r.forEach(([n, ok]) => console.log((ok ? 'PASS' : 'FAIL') + ' - ' + n));
  return r.every(([, ok]) => ok);
};
```

### Yang dicari di DevTools

- Console: **tidak boleh** ada `TypeError ... null` saat Back/Forward.
- Network: langkah 3 = DELETE per-baris; langkah 4 = DELETE bulk **dengan** `ids[]`.
