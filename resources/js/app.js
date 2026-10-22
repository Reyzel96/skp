import './bootstrap';

import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

// Ekstraksi teks PDF untuk "Upload Bukti Dukung" (SKP Dosen Kualitatif).
// Dijalankan sepenuhnya di browser via pdf.js — tidak ada byte PDF yang
// dikirim ke server/layanan eksternal.
import { attachPdfBuktiHandler, limitRealisasiWords, REALISASI_MAX_WORDS } from './pdf-bukti-dukung';

window.Alpine = Alpine;

// Helper global untuk x-init di blade (rute initPdfBukti).
// Pemakaian di show.blade.php: x-init="initPdfBukti($el)" pada div kotak
// upload. Handler mencari input[type=file], textarea realisasi, dan elemen
// status di DALAM kotak itu sendiri — teks PDF selalu masuk ke textarea
// kotak yang sama, tidak pernah kotak lain.
window.initPdfBukti = (uploadBox) => {
    if (!(uploadBox instanceof HTMLElement)) return;
    attachPdfBuktiHandler({
        fileInput: uploadBox.querySelector('input[type="file"]'),
        statusEl: uploadBox.querySelector('[data-pdf-status]'),
        uploadBox: uploadBox,
    });
};

// Helper pembatasan teks realisasi (berbasis KATA, per dokumen) untuk skrip
// cetak di show.blade.php: satu logika dengan ekstraksi (web & print konsisten).
window.limitRealisasiWords = limitRealisasiWords;
window.REALISASI_MAX_WORDS = REALISASI_MAX_WORDS;

// =========================================================================
// KOMPONEN ALPINE YANG BUTUH DATA PER-PAGE DARI BLADE
// Didaftarkan SEKALI di sini (bukan di dalam event 'alpine:init' yang hanya
// fire sekali). Data di-pass langsung lewat argumen x-data di blade, sehingga
// tiap instance mengikat datanya sendiri saat di-init (race-free secara
// arsitektural, tanpa variabel global).
// =========================================================================
// Data di-pass LANGSUNG lewat argumen x-data (lihat blade), bukan global.
// Ini race-free secara arsitektural: tiap komponen mengikat datanya sendiri
// saat di-init, tidak ada variabel global yang ke-render ulang oleh Turbo.
const programManager = (prodis, initialData, periode) => {
    const prodisData = prodis || [];
    const initialDataArr = initialData || [];
    const periodeData = periode || [];

    const normalizeAlokasi = (item) => {
        if (!item.alokasi_custom || Array.isArray(item.alokasi_custom) || typeof item.alokasi_custom !== 'object') {
            const objCustom = {};
            prodisData.forEach((p) => (objCustom[p] = 0));
            item.alokasi_custom = objCustom;
        } else {
            prodisData.forEach((p) => {
                if (item.alokasi_custom[p] === undefined) item.alokasi_custom[p] = 0;
            });
        }
    };

    const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(number);

    const formatCompact = (number) => {
        if (number >= 1000000) return (number / 1000000).toFixed(1) + ' jt';
        if (number >= 1000) return (number / 1000).toFixed(0) + ' rb';
        return number;
    };

    const calculateAverage = function (program) {
        if (program.tipe_alokasi === 'Rata') {
            const total = parseFloat(program.anggaran_per_prodi) || 0;
            const count = this.prodis.length || 1;
            program.per_prodi = Math.round(total / count);
        }
    };

    const getTotalCustom = (program) => {
        let total = 0;
        if (!program.alokasi_custom) return 0;
        for (const key in program.alokasi_custom) {
            total += Math.round(parseFloat(program.alokasi_custom[key]) || 0);
        }
        return total;
    };

    const getFormattedYear = function () {
        const id = this.programs[0] ? this.programs[0].periode_id : this.globalPeriodeId;
        const p = periodeData.find((x) => x.id == id);
        if (!p) return '';
        const year = parseInt(p.tahun_anggaran);
        return `${year}/${year + 1}`;
    };

    const getEmptyProgram = () => {
        const defaultCustom = {};
        prodisData.forEach((p) => (defaultCustom[p] = 0));
        return {
            periode_id: '', unit_kerja: 'Fakultas Teknik', sasaran: '', nama_program: '', jenis_kegiatan: '',
            nama_kegiatan: '', sub_kegiatan: '', indikator: '', target_capaian: '', semester: '', waktu_pelaksanaan: '',
            per_mhs: 0, anggaran_per_prodi: 0, anggaran_pagu: 0, tahun_anggaran: '', tahun_akademik: '',
            tipe_alokasi: 'Rata', per_prodi: 0, alokasi_custom: defaultCustom,
        };
    };

    let parsedPrograms = [];
    if (initialDataArr.length > 0) {
        parsedPrograms = initialDataArr.map((item) => {
            normalizeAlokasi(item);
            if (item.tipe_alokasi === undefined) item.tipe_alokasi = 'Rata';
            if (item.per_mhs === undefined) item.per_mhs = 0;
            if (!item.anggaran_per_prodi || item.anggaran_per_prodi == 0) item.anggaran_per_prodi = item.anggaran_pagu || 0;
            if (!item.anggaran_pagu || item.anggaran_pagu == 0) item.anggaran_pagu = item.anggaran_per_prodi || 0;
            if (item.indikator === undefined) item.indikator = '';
            if (item.target_capaian === undefined) item.target_capaian = '';
            if (item.semester === undefined) item.semester = '';
            if (item.waktu_pelaksanaan === undefined) item.waktu_pelaksanaan = '';
            if (item.tahun_anggaran === undefined) item.tahun_anggaran = '';
            if (item.tahun_akademik === undefined) item.tahun_akademik = '';
            return item;
        });
    } else {
        parsedPrograms = [getEmptyProgram()];
    }

    return {
        prodis: prodisData,
        programs: parsedPrograms,
        globalPeriodeId: parsedPrograms.length > 0 && parsedPrograms[0].periode_id ? parsedPrograms[0].periode_id : '',
        showCustomModal: false,
        activeProgram: null,
        syncGlobalPeriode() {
            const p = periodeData.find((x) => x.id == this.globalPeriodeId);
            if (p) this.programs.forEach((prog) => (prog.periode_id = p.id));
        },
        getFormattedYear,
        getGlobalYear() { return this.getFormattedYear(); },
        getGlobalAcademic() { return this.getFormattedYear(); },
        addProgram() {
            const newProg = getEmptyProgram();
            const p = periodeData.find((x) => x.id == this.globalPeriodeId);
            if (p) newProg.periode_id = p.id;
            this.programs.push(newProg);
        },
        removeProgram(index) {
            if (confirm('Hapus baris kegiatan ini?')) this.programs.splice(index, 1);
        },
        openCustomModal(index) {
            this.activeProgram = this.programs[index];
            this.activeProgram.tipe_alokasi = 'Custom';
            this.showCustomModal = true;
        },
        closeCustomModal() {
            this.showCustomModal = false;
            this.activeProgram = null;
        },
        calculateAverage,
        getTotalCustom,
        getSisa(program) {
            return Math.round(parseFloat(program.anggaran_per_prodi) || 0) - this.getTotalCustom(program);
        },
        formatRupiah,
        formatCompact,
        validateAll() {
            if (this.programs.length === 0) return false;
            for (let i = 0; i < this.programs.length; i++) {
                const p = this.programs[i];
                if (p.tipe_alokasi === 'Custom' && this.getTotalCustom(p) > p.anggaran_per_prodi) return false;
            }
            return true;
        },
    };
};

Alpine.data('programManager', programManager);

// Editor "Acuan SKP Dosen Kualitatif". DIDAFTRARKAN SEKALI di sini (bukan di
// <script> inline halaman yang hook 'alpine:init' — event itu hanya fire sekali,
// sehingga setelah navigasi Turbo komponen mati diam-diam dan autosave gagal).
// Endpoint di-pass lewat argumen x-data di acuan.blade.php. CSRF token TIDAK
// di-cache: dibaca fresh dari <meta name="csrf-token"> tepat sebelum tiap
// request, supaya tidak gagal karena token regenerated saat sesi panjang.
//
// Arsitektur autosave (setelah rombak):
// - Debounce per-field 1.5s setelah user berhenti mengetik (bukan interval).
// - SATU request jaringan aktif per waktu (queue serial); perubahan baru saat
//   request berjalan otomatis terkirim setelah request selesai.
// - Per-field independen: 1 field gagal, field lain tetap tersimpan.
// - Retry maks 2x dengan backoff 3s lalu 8s; setelah itu berhenti + status merah.
// - Badge status jujur mengikuti hasil server (bukan optimis).
// - beforeunload warning selama ada perubahan belum terkonfirmasi tersimpan.
const acuanEditor = ({ urls }) => ({
    urls,

    // Status badge: idle | typing | pending | saving | saved | error
    saveState: 'idle',
    lastSaved: '',          // label jam untuk badge setelah konfirmasi server
    stateDetail: '',        // alasan singkat saat error ("Sesi habis" dst)
    showNotification: false,
    notificationMessage: '',
    savingAll: false,       // lock tombol manual

    _debounceTimer: null,   // timer debounce per-field
    _queue: [],             // antrian {id, field, value, type} menunggu kirim
    _queuedKeys: {},        // key "id|field" -> index di _queue (dedup)
    _networkBusy: false,    // true saat satu request sedang berjalan
    _retryCounts: {},       // key -> jumlah retry berjalan
    _beforeunloadBound: false,
    _destroyed: false,

    nowLabel() {
        return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    },

    // ---------- util kontrol ----------
    controlValue(el) {
        return el.type === 'checkbox' ? el.checked : el.value;
    },

    // Snapshot untuk tombol manual & deteksi perubahan global (urutan DOM stabil)
    snapshot() {
        const controls = document.querySelectorAll('[data-acuan-id]');
        return Array.from(controls).map((el) => (el.type === 'checkbox' ? (el.checked ? '1' : '0') : (el.value ?? ''))).join('');
    },

    hasUnsaved() {
        return this._queue.length > 0 || this.saveState === 'saving' || this.saveState === 'pending' || this.saveState === 'error';
    },

    // ---------- CSRF fresh ----------
    freshCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    // ---------- init & teardown ----------
    startAutosave() {
        const root = this.$el;

        // Selenium: debounce per-field. @change (blur/pindah kolom) tetap
        // langsung enqueue tanpa menunggu; @input men-schedule debounce.
        root.addEventListener('input', (e) => {
            const el = e.target.closest('[data-acuan-id]');
            if (!el) return;
            this.saveState = 'typing';
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => this.enqueueControl(el), 1500);
        });
        // Perubahan dari keyboard selesai -> debounce. blur/change -> langsung.
        root.addEventListener('change', (e) => {
            const el = e.target.closest('[data-acuan-id]');
            if (!el) return;
            this.enqueueControl(el);
        });

        if (!this._beforeunloadBound) {
            this._beforeunload = (e) => {
                if (this.hasUnsaved()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', this._beforeunload);
            this._beforeunloadBound = true;
        }
    },

    destroy() {
        this._destroyed = true;
        clearTimeout(this._debounceTimer);
        if (this._beforeunloadBound) {
            window.removeEventListener('beforeunload', this._beforeunload);
            this._beforeunloadBound = false;
        }
    },

    // ---------- enqueue per-field ----------
    enqueueControl(el) {
        if (this._destroyed) return;
        clearTimeout(this._debounceTimer);
        const item = {
            key: el.dataset.acuanId + '|' + el.dataset.acuanField,
            id: el.dataset.acuanId,
            field: el.dataset.acuanField,
            value: this.controlValue(el),
            type: el.dataset.acuanType,
            el: el,
        };
        // Dedup: nilai terbaru menimpa antrian untuk field yang sama
        if (this._queuedKeys[item.key] !== undefined) {
            this._queue[this._queuedKeys[item.key]] = item;
        } else {
            this._queuedKeys[item.key] = this._queue.length;
            this._queue.push(item);
        }
        this.saveState = 'pending';
        this.pump();
    },

    // Enqueue semua kontrol pada halaman (dipakai tombol manual)
    enqueueAll() {
        document.querySelectorAll('[data-acuan-id]').forEach((el) => this.enqueueControl(el));
    },

    // ---------- worker serial: satu request aktif ----------
    async pump() {
        if (this._networkBusy) return;   // request berjalan -> otomatis lanjut saat selesai
        const item = this._queue.shift();
        if (!item) {
            if (this.saveState === 'pending' || this.saveState === 'typing') this.saveState = 'idle';
            return;
        }
        delete this._queuedKeys[item.key];
        this._networkBusy = true;
        this.saveState = 'saving';
        try {
            await this.sendWithRetry(item);
            if (this.saveState === 'saving' || this.saveState === 'pending') {
                this.saveState = this._queue.length > 0 ? 'pending' : 'saved';
                if (this.saveState === 'saved') this.lastSaved = this.nowLabel();
            }
        } catch (err) {
            if (this._destroyed) return;
            // Gagal permanen setelah retry: field lain lanjut, field ini status merah.
            this.saveState = 'error';
            this.stateDetail = err && err.isAuth ? 'Sesi habis' : (err && err.isNetwork ? 'Jaringan bermasalah' : 'Gagal menyimpan');
        } finally {
            this._networkBusy = false;
        }
        // Lanjut item antrian berikutnya (perubahan yang masuk saat request jalan)
        if (!this._destroyed && this._queue.length > 0) {
            await this.pump();
        } else if (!this._destroyed && this._queue.length === 0 && this.saveState !== 'error') {
            if (this.saveState === 'pending') this.saveState = 'saved';
            if (this.saveState === 'saved' && !this.lastSaved) this.lastSaved = this.nowLabel();
        }
    },

    // ---------- kirim 1 field dengan retry backoff ----------
    async sendWithRetry(item, attempt = 0) {
        try {
            const res = await fetch(this.urls.update, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.freshCsrf() },
                body: JSON.stringify({ id: item.id, field: item.field, value: item.value, type: item.type }),
                signal: AbortSignal.timeout(10000),
            });
            // Redirect ke login (sesi habis) balas HTML, bukan JSON.
            if (res.redirected || (res.headers.get('content-type') || '').indexOf('json') === -1) {
                const e = new Error('auth'); e.isAuth = true; throw e;
            }
            if (!res.ok) {
                const e = new Error('http ' + res.status);
                e.status = res.status;
                if (res.status === 419 || res.status === 401) e.isAuth = true;
                throw e;
            }
            const data = await res.json();
            if (!data.success) {
                const e = new Error(data.message || 'validasi'); e.isValidation = true; throw e;
            }
            this._retryCounts[item.key] = 0;
        } catch (err) {
            const retryable = !(err.isValidation);
            if (retryable && attempt < 2 && !this._destroyed) {
                const delay = attempt === 0 ? 3000 : 8000;
                await new Promise((r) => setTimeout(r, delay));
                return this.sendWithRetry(item, attempt + 1);
            }
            if (err.isAuth) {
                err.message = 'Sesi Anda berakhir. Klik Simpan untuk login ulang.';
                this.stopForAuth();
            }
            throw err;
        }
    },

    // Sesi habis -> hentikan autosave total (jangan spam retry diam-diam)
    stopForAuth() {
        this.destroy();
        this._queue = [];
        this._queuedKeys = {};
    },

    // ---------- tombol manual "Simpan Perubahan" ----------
    async saveAll() {
        if (this.savingAll) return;
        this.savingAll = true;
        try {
            this.enqueueAll();
            // Tunggu antrian habis (pump menangani serial)
            while ((this._queue.length > 0 || this._networkBusy) && !this._destroyed) {
                await new Promise((r) => setTimeout(r, 100));
            }
            if (this.saveState === 'saved' || this.saveState === 'idle') {
                this.notificationMessage = 'Semua perubahan berhasil tersimpan.';
                this.showNotification = true;
                setTimeout(() => { this.showNotification = false; }, 3000);
            } else if (this.saveState === 'error') {
                this.showError('Sebagian perubahan gagal tersimpan (' + (this.stateDetail || 'error') + '). Cek indikator status lalu coba lagi.');
            }
        } finally {
            this.savingAll = false;
        }
    },

    addParent(kategori) {
        fetch(this.urls.addParent, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.freshCsrf() },
            body: JSON.stringify({ kategori: kategori })
        })
        .then(res => {
            if (!res.ok) throw res;
            return res.json();
        })
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { this.showError(data.message || 'Gagal menambah sasaran kinerja.'); }
        })
        .catch(err => this.handleFetchError(err));
    },
    deleteParent(id) {
        if(!confirm('Hapus baris ini beserta seluruh isinya?')) return;
        fetch(this.urls.deleteParent.replace(':id', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.freshCsrf() }
        })
        .then(res => {
            if (!res.ok) throw res;
            return res.json();
        })
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { this.showError(data.message || 'Gagal menghapus sasaran kinerja.'); }
        })
        .catch(err => this.handleFetchError(err));
    },
    addChild(parentId) {
        fetch(this.urls.addChild, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.freshCsrf() },
            body: JSON.stringify({ parent_id: parentId })
        })
        .then(res => {
            if (!res.ok) throw res;
            return res.json();
        })
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { this.showError(data.message || 'Gagal menambah sub indikator.'); }
        })
        .catch(err => this.handleFetchError(err));
    },
    deleteChild(id) {
        if(!confirm('Hapus baris ini?')) return;
        fetch(this.urls.deleteChild.replace(':id', id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.freshCsrf() }
        })
        .then(res => {
            if (!res.ok) throw res;
            return res.json();
        })
        .then(data => {
            if (data.success) { window.location.reload(); }
            else { this.showError(data.message || 'Gagal menghapus sub indikator.'); }
        })
        .catch(err => this.handleFetchError(err));
    },
    showError(message) {
        const toast = document.querySelector('#toast-error-acuan');
        if (toast) {
            toast.querySelector('span').textContent = message;
            toast.style.display = 'flex';
            setTimeout(() => { toast.style.display = 'none'; }, 5000);
        } else {
            alert(message);
        }
    },
    async handleFetchError(err) {
        let message = 'Terjadi kesalahan. Perubahan mungkin tidak tersimpan. Coba lagi atau refresh halaman.';
        try {
            if (err instanceof Response && (err.status === 419 || err.status === 401)) {
                message = 'Sesi Anda berakhir. Halaman akan dimuat ulang untuk login kembali.';
                setTimeout(() => window.location.reload(), 1500);
            } else if (err instanceof Response && err.status === 422) {
                const data = await err.json();
                message = (data.errors && data.errors.kategori && data.errors.kategori[0])
                    || (data.errors && Object.values(data.errors)[0]?.[0])
                    || data.message
                    || message;
            } else if (err instanceof Response) {
                message = `Gagal menyimpan (HTTP ${err.status}). Perubahan mungkin tidak tersimpan.`;
            }
        } catch (e) { /* tetap pakai pesan default */ }
        this.showError(message);
    }
});

Alpine.data('acuanEditor', acuanEditor);

// State sidebar disimpan di Alpine store (singleton JS) supaya stabil lintas
// navigasi Turbo — tidak ikut ter-reset tiap Alpine.initTree(document.body)
// dijalankan pada turbo:load. Ini menghilangkan flicker melebar-mengecil
// karena nilai state tidak berubah saat <aside> di-re-evaluasi.
// Sumber kebenaran = cookie (konsisten dengan render server-side di blade),
// fallback localStorage lalu default true (sama seperti request()->cookie()).
document.addEventListener('alpine:init', () => {
    const readInitial = () => {
        const c = document.cookie.match(/(?:^|;\s*)sidebarOpen=([^;]*)/);
        if (c) return c[1] !== 'false';
        const l = localStorage.getItem('sidebarOpen');
        if (l !== null) return l !== 'false';
        return true;
    };
    
    // Store sidebar disederhanakan, hapus isMobile dan setIsMobile
    Alpine.store('sidebar', {
        open: readInitial(),
        mobileOpen: false,
        toggle() {
            this.open = !this.open;
            const v = this.open ? 'true' : 'false';
            document.cookie = `sidebarOpen=${v};max-age=31536000;path=/`;
            localStorage.setItem('sidebarOpen', this.open);
        },
        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
        },
        closeMobile() {
            this.mobileOpen = false;
        }
    });
});

// Delegated click handler untuk baris tabel yang bisa diklik (data-turbo-row).
// Pakai Turbo.visit() (bukan window.location) supaya navigasi tetap mulus
// lewat Turbo Drive, konsisten dengan link-link lain. Klik pada <a>, <button>,
// atau elemen ber-class .no-click di dalam baris tetap jalan normal (tidak
// di-override oleh handler ini).
document.addEventListener('click', (event) => {
    const row = event.target.closest('[data-turbo-row]');
    if (!row) return;
    if (event.target.closest('a, button, .no-click')) return;
    Turbo.visit(row.dataset.turboRow);
});

// Update highlight menu aktif di sidebar (permanent) berdasarkan route Laravel
// yang sedang aktif (data-route di <body>, dihitung server-side lewat
// Route::currentRouteName() - selalu akurat walau ada redirect). Dijalankan
// tiap turbo:load + sekali di awal. Blade server-side tetap ada sebagai
// fallback (non-Turbo / JS mati); JS di atasnya yang override.
const routeMatchesPattern = (current, pattern) => {
    if (!current || !pattern) return false;
    const escaped = pattern.split('*').map((s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('.*');
    return new RegExp('^' + escaped + '$').test(current);
};
const updateActiveSidebar = () => {
    const current = document.body.dataset.route;
    document.querySelectorAll('#sidebar a[data-route-pattern]').forEach((link) => {
        const patterns = link.dataset.routePattern.split('|');
        const isActive = patterns.some((p) => routeMatchesPattern(current, p));
        const isDashboard = patterns.some((p) => routeMatchesPattern(current, p.replace(/dashboard/, 'dashboard')) && current && current.endsWith('dashboard'));

        if (isActive) {
            link.classList.add('text-slate-700', 'font-medium');
            link.classList.remove('hover:bg-[#DEE535]', 'hover:text-black');

            if (isDashboard) {
                link.classList.add('bg-[#16BC2E]');
                link.classList.remove('bg-[#DEE535]');
            } else {
                link.classList.add('bg-[#DEE535]');
                link.classList.remove('bg-[#16BC2E]');
            }
        } else {
            link.classList.remove('bg-[#DEE535]', 'bg-[#16BC2E]', 'font-medium');
            link.classList.add('text-slate-700', 'hover:bg-[#DEE535]', 'hover:text-black');
        }

        if (isActive) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
};

document.addEventListener('turbo:load', () => {
    Alpine.initTree(document.body);
    updateActiveSidebar();
});

// Inisialisasi highlight aktif saat app.js pertama load (sebelum Turbo load).
updateActiveSidebar();

// Blur input number saat scroll (cegah spinner mouse). Sekali saja, global,
// supaya tidak menumpuk tiap kali navigasi Turbo bolak-balik.
document.addEventListener('wheel', (event) => {
    const el = document.activeElement;
    if (el && el.type === 'number') el.blur();
}, { passive: false });

// =========================================================================
// HEARTBEAT & AUTO-LOGOUT (dipindah dari layouts/app.blade.php)
// Dijalankan sekali (module ini hanya dieksekusi satu kali) untuk user
// terautentikasi (ditandai dengan adanya #logout-form di header).
// =========================================================================
const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content');

if (document.getElementById('logout-form')) {
    const csrfToken = meta('csrf-token');
    const keepAliveUrl = meta('route-keep-alive');
    const loginUrl = meta('route-login') || '/login';
    const lifetimeMin = parseInt(meta('session-lifetime') || '120', 10);

    let heartbeatInterval = null;
    if (csrfToken && keepAliveUrl) {
        heartbeatInterval = setInterval(() => {
            fetch(keepAliveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).then((response) => {
                if (response.status === 401 || response.status === 419) {
                    window.location.href = loginUrl;
                }
            }).catch(() => {});
        }, 60000);
    }

    const sessionLifetimeMs = lifetimeMin * 60 * 1000;
    const timeoutDuration = sessionLifetimeMs + 2000;
    let sessionTimeout;

    const startTimer = () => {
        sessionTimeout = setTimeout(() => {
            if (heartbeatInterval) clearInterval(heartbeatInterval);
            const logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                logoutForm.submit();
            } else {
                window.location.href = loginUrl;
            }
        }, timeoutDuration);
    };

    const resetTimer = () => {
        clearTimeout(sessionTimeout);
        startTimer();
    };

    document.addEventListener('click', resetTimer, { passive: true });
    document.addEventListener('keypress', resetTimer, { passive: true });
    document.addEventListener('mousemove', resetTimer, { passive: true });
    document.addEventListener('scroll', resetTimer, { passive: true });

    startTimer();
}

// Mulai Alpine (setelah semua komponen terdaftar).
Alpine.start();