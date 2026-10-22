<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\ProfileController;

// Controller Anggaran Baru
use App\Http\Controllers\ProgramKegiatanController;
use App\Http\Controllers\ProgramKegiatanProdiController;
use App\Http\Controllers\LpjController;
use App\Http\Controllers\PeriodeAnggaranController;

// Controller Lama (SKP & Dashboard)
use App\Http\Controllers\User\UserSkpController;
use App\Http\Controllers\User\UserPenilaianController;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SuperAdmin\MasterSkpTendikController;
use App\Http\Controllers\SuperAdmin\ProdiController;
use App\Http\Controllers\SuperAdmin\DaftarTupoksiController;
use App\Http\Controllers\Dekan\DekanController;
use App\Http\Controllers\WakilDekan\WakilDekanController;
use App\Http\Controllers\Kaprodi\KaprodiController;
use App\Http\Controllers\Sekprodi\SekprodiController;
use App\Http\Controllers\KepalaLaboratorium\KepalaLaboratoriumController;
use App\Http\Controllers\AdminKeuanganFakultas\AdminKeuanganFakultasController;
use App\Http\Controllers\UnitPenunjang\UnitPenunjangController;
use App\Http\Controllers\Dosen\DosenController;
use App\Http\Controllers\Rektorat\RektoratController;
use App\Http\Controllers\Penilai\EvaluasiBawahanController;
use App\Http\Controllers\Penilai\ApprovalSkpDosenKualitatifController;
use App\Http\Controllers\Penilai\MasterSkpApprovalController;
use App\Http\Controllers\RekapSkpController;

Route::redirect('/', '/login');

// =========================================================================
// DASHBOARD REDIRECT & PROFILE DINAMIS
// =========================================================================
Route::middleware(['auth'])->group(function () {
    // Shared Lookup Routes
    Route::get('/lookup/daftar-tupoksi/bidang/{bidang}', [\App\Http\Controllers\SuperAdmin\DaftarTupoksiController::class, 'getByBidang'])->name('lookup.daftar-tupoksi.by-bidang');
    Route::get('/lookup/pangkat-golongan/by-jenjang/{jenjang_jabatan_id}', [\App\Http\Controllers\SuperAdmin\PangkatGolonganController::class, 'byJenjang'])->name('lookup.pangkat-golongan.by-jenjang');

    Route::post('/keep-alive', function () {
        if (auth()->check()) {
            Cache::put('user-online-' . auth()->id(), true, now()->addMinutes(2));
            return response()->json(['status' => 'ok']);
        }
        return response()->json(['status' => 'unauthorized'], 401);
    })->name('keep-alive');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        $role = $user->roles()->orderBy('kategori', 'asc')->first();

        if ($role && !empty($role->dashboard_route)) {
            if (Route::has($role->dashboard_route)) {
                return redirect()->route($role->dashboard_route);
            }
        }
        return redirect()->route('pegawai.dashboard');
    })->name('dashboard');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});

// =========================================================================
// AREA PEGAWAI (FRONT-END SKP)
// =========================================================================
Route::middleware(['auth'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/dashboard', fn() => view('pegawai.dashboard'))->name('dashboard');

    Route::controller(UserSkpController::class)->group(function () {
        Route::get('/skp-tendik', 'index')->name('skp-tendik.index');
        Route::get('/skp-tendik/{id}', 'show')->name('skp-tendik.show');
        Route::get('/skp-tendik/download/bukti', 'downloadBukti')->name('skp-tendik.download-bukti');
        Route::post('/skp-tendik', 'store')->name('skp-tendik.store');
        Route::get('/skp-dosen', 'indexDosen')->name('skp-dosen.index');
        Route::get('/skp-dosen/{id}', 'showDosen')->name('skp-dosen.show');
        Route::post('/skp-dosen', 'storeDosen')->name('skp-dosen.store');
        Route::post('/skp-dosen/update-target-detail', 'updateTargetDetail')->name('skp-dosen.update-target-detail');

        Route::get('/skp-dosen-kualitatif', [App\Http\Controllers\User\SkpDosenKualitatifController::class, 'index'])->name('skp-dosen-kualitatif.index');
        Route::get('/skp-dosen-kualitatif/{id}', [App\Http\Controllers\User\SkpDosenKualitatifController::class, 'show'])->name('skp-dosen-kualitatif.show');
        Route::post('/skp-dosen-kualitatif/{id}/store-realisasi', [App\Http\Controllers\User\SkpDosenKualitatifController::class, 'storeRealisasi'])->name('skp-dosen-kualitatif.store-realisasi');

        Route::patch('/skp/update-penilai', 'updatePenilai')->name('skp.update-penilai');
        Route::patch('/skp/finalisasi-target/{header_id}', 'finalisasiTarget')->name('skp.finalisasi-target');
        Route::post('/skp/store-activity', 'storeActivity')->name('skp.store-activity');
        Route::delete('/skp/destroy-activity/{id}', 'destroyActivity')->name('skp.destroy-activity');
        Route::post('/skp/tugas-tambahan/store', 'storeTugasTambahan')->name('skp.tugas-tambahan.store');
        Route::delete('/skp/tugas-tambahan/{id}', 'destroyTugasTambahan')->name('skp.tugas-tambahan.destroy');
        Route::patch('/skp/ajukan/{header_id}', 'ajukanSkp')->name('skp.ajukan');

        Route::post('/skp/perilaku/{id}/store', 'storePerilaku')->name('skp.perilaku.store');

        Route::get('/skp-dosen/perkuliahan/{skp_id}', 'detailPerkuliahan')->name('skp-dosen.perkuliahan');
        Route::post('/skp-dosen/perkuliahan/store', 'storePerkuliahan')->name('skp-dosen.perkuliahan.store');
        Route::delete('/skp-dosen/perkuliahan/destroy/{id}', 'destroyPerkuliahan')->name('skp-dosen.perkuliahan.destroy');

        Route::get('/skp-dosen/seminar/{skp_id}', 'detailSeminar')->name('skp-dosen.seminar');
        Route::post('/skp-dosen/seminar/store', 'storeSeminar')->name('skp-dosen.seminar.store');
        Route::delete('/skp-dosen/seminar/destroy/{id}', 'destroySeminar')->name('skp-dosen.seminar.destroy');

        Route::get('/skp-dosen/pkl/{id}', 'detailPkl')->name('skp-dosen.pkl');
        Route::post('/skp-dosen/pkl/store', 'storePkl')->name('skp-dosen.pkl.store');

        Route::get('/skp-dosen/bimbing/{id}', 'detailBimbing')->name('skp-dosen.bimbing');
        Route::post('/skp-dosen/bimbing/store', 'storeBimbing')->name('skp-dosen.bimbing.store');

        Route::get('/skp-dosen/penguji/{id}', 'detailPenguji')->name('skp-dosen.penguji');
        Route::post('/skp-dosen/penguji/store', 'storePenguji')->name('skp-dosen.penguji.store');

        Route::get('/skp-dosen/kemahasiswaan/{id}', 'detailKemahasiswaan')->name('skp-dosen.kemahasiswaan');
        Route::post('/skp-dosen/kemahasiswaan/store', 'storeKemahasiswaan')->name('skp-dosen.kemahasiswaan.store');
        Route::delete('/skp-dosen/kemahasiswaan/destroy/{id}', 'destroyKemahasiswaan')->name('skp-dosen.kemahasiswaan.destroy');

        Route::get('/skp-dosen/bahan/{id}', 'detailBahan')->name('skp-dosen.bahan');
        Route::post('/skp-dosen/bahan/store', 'storeBahan')->name('skp-dosen.bahan.store');

        Route::get('/skp-dosen/karya-ilmiah/{id}', 'detailKaryaIlmiah')->name('skp-dosen.karya-ilmiah');
        Route::post('/skp-dosen/karya-ilmiah/store', 'storeKaryaIlmiah')->name('skp-dosen.karya-ilmiah.store');

        Route::get('/skp-dosen/abdimas/{id}', 'detailAbdimas')->name('skp-dosen.abdimas');
        Route::post('/skp-dosen/abdimas/store', 'storeAbdimas')->name('skp-dosen.abdimas.store');

        Route::get('/skp-dosen/jabatan/{id}', 'detailJabatan')->name('skp-dosen.jabatan');
        Route::post('/skp-dosen/jabatan/store', 'storeJabatan')->name('skp-dosen.jabatan.store');

        Route::get('/skp-dosen/penunjang/{id}', 'detailPenunjang')->name('skp-dosen.penunjang');
        Route::post('/skp-dosen/penunjang/store', 'storePenunjang')->name('skp-dosen.penunjang.store');
        Route::delete('/skp-dosen/penunjang/destroy/{id}', 'destroyPenunjang')->name('skp-dosen.penunjang.destroy');
    });

    Route::get('/skp-penilaian', [UserPenilaianController::class, 'index'])->name('penilaian.index');
    Route::post('/skp-penilaian/store', [UserPenilaianController::class, 'store'])->name('penilaian.store');
});

// =========================================================================
// AREA PEJABAT PENILAI (EVALUASI BAWAHAN)
// =========================================================================
Route::middleware(['auth'])->prefix('penilai')->name('penilai.')->group(function () {
    Route::controller(EvaluasiBawahanController::class)->group(function () {
        Route::get('/evaluasi-bawahan', 'index')->name('evaluasi.index');
        Route::get('/evaluasi-bawahan/{header_id}', 'show')->name('evaluasi.show');
        Route::get('/evaluasi-bawahan/tendik-detail/{skp_id}', 'showTendikDetail')->name('evaluasi.tendik-detail');
        Route::post('/evaluasi-bawahan/approve-target/{header_id}', 'approveTarget')->name('evaluasi.approve-target');
        Route::patch('/evaluasi-bawahan/verify/{header_id}', 'verify')->name('evaluasi.verify');
        Route::patch('/evaluasi-bawahan/verify-kualitatif/{header_id}', 'verifyKualitatif')->name('evaluasi.verify-kualitatif');
        Route::get('/evaluasi-bawahan/download/bukti', 'downloadBukti')->name('evaluasi.download-bukti');
        Route::get('/evaluasi-bawahan/export-pdf/{header_id}', 'exportPdfKualitatif')->name('evaluasi.export-pdf');
    });

        Route::controller(ApprovalSkpDosenKualitatifController::class)->group(function () {
            Route::get('/approval-skp-dosen-kualitatif', 'index')->name('approval-skp-dosen-kualitatif.index');
            Route::get('/approval-skp-dosen-kualitatif/{header_id}', 'show')->name('approval-skp-dosen-kualitatif.show');
            Route::post('/approval-skp-dosen-kualitatif/{id}/approve', 'approve')->name('approval-skp-dosen-kualitatif.approve');
            Route::post('/approval-skp-dosen-kualitatif/{id}/reject', 'reject')->name('approval-skp-dosen-kualitatif.reject');
        });

        Route::controller(MasterSkpApprovalController::class)->group(function () {
            Route::get('/approval-skp-tendik', 'index')->name('approval-skp-tendik.index');
            Route::get('/approval-skp-tendik/{header_id}', 'show')->name('approval-skp-tendik.show');
            Route::post('/approval-skp-tendik/{header_id}/store', 'storeActivity')->name('approval-skp-tendik.store-activity');
            Route::post('/approval-skp-tendik/{header_id}/bulk-update', 'updateAll')->name('approval-skp-tendik.bulk-update');
            Route::post('/approval-skp-tendik/{id}/approve', 'approve')->name('approval-skp-tendik.approve');
            Route::post('/approval-skp-tendik/{id}/reject', 'reject')->name('approval-skp-tendik.reject');
            Route::delete('/approval-skp-tendik/{id}', 'destroy')->name('approval-skp-tendik.destroy');
            Route::post('/approval-skp-tendik/bulk-approve', 'bulkApprove')->name('approval-skp-tendik.bulk-approve');
        });
});


// =========================================================================
// AREA SUPER ADMIN
// =========================================================================
Route::middleware(['auth', 'role:Super Admin', 'prevent-back'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::controller(SuperAdminController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');

        // FITUR BARU: MANAJEMEN ROLES
        Route::get('/roles', 'rolesIndex')->name('roles.index');
        Route::post('/roles', 'rolesStore')->name('roles.store');
        Route::put('/roles/{role}', 'rolesUpdate')->name('roles.update');
        Route::delete('/roles/bulk-destroy', 'rolesBulkDestroy')->name('roles.bulk-destroy');
        Route::delete('/roles/{role}', 'rolesDestroy')->name('roles.destroy');

        // Users & Periode
        Route::get('/users', 'usersIndex')->name('users.index');
        Route::get('/users/template', 'usersDownloadTemplate')->name('users.template');
        Route::get('/users/create', 'usersCreate')->name('users.create');
        Route::post('/users', 'usersStore')->name('users.store');
        Route::post('/users/import', 'usersImport')->name('users.import');
        Route::get('/users/{user}/edit', 'usersEdit')->name('users.edit');
        Route::put('/users/{user}', 'usersUpdate')->name('users.update');
        Route::delete('/users/bulk-destroy', 'usersBulkDestroy')->name('users.bulk-destroy');
        Route::delete('/users/{user}', 'usersDestroy')->name('users.destroy');

        Route::get('/periode-skp', 'periodeSkpIndex')->name('periode-skp.index');
        Route::get('/periode-skp/create', 'periodeSkpCreate')->name('periode-skp.create');
        Route::post('/periode-skp', 'periodeSkpStore')->name('periode-skp.store');
        Route::get('/periode-skp/{periodeSkp}/edit', 'periodeSkpEdit')->name('periode-skp.edit');
        Route::put('/periode-skp/{periodeSkp}', 'periodeSkpUpdate')->name('periode-skp.update');
        Route::delete('/periode-skp/{periodeSkp}', 'periodeSkpDestroy')->name('periode-skp.destroy');
        Route::patch('/periode-skp/{periodeSkp}/status', 'periodeSkpUpdateStatus')->name('periode-skp.update-status');

        Route::get('/skp-tendik', 'skpTendikIndex')->name('skp-tendik.index');
        Route::get('/skp-tendik/download/bukti', 'downloadBukti')->name('skp-tendik.download-bukti');
        Route::get('/skp-tendik/create', 'skpTendikCreate')->name('skp-tendik.create');
        Route::post('/skp-tendik/store', 'skpTendikStore')->name('skp-tendik.store');
        Route::get('/skp-tendik/{id}/detail', 'skpTendikShow')->name('skp-tendik.show');
        Route::get('/skp-tendik/{user_id}/edit', [SuperAdminController::class, 'skpTendikEdit'])->name('skp-tendik.edit');
        Route::put('/skp-tendik/{user_id}', [SuperAdminController::class, 'skpTendikUpdate'])->name('skp-tendik.update');
        Route::delete('/skp-tendik/{id}', 'skpTendikDestroy')->name('skp-tendik.destroy');
        // BIARKAN INI TETAP HIDUP JIKA MASIH DIPAKAI
        Route::get('/skp-tendik/download/bukti', 'downloadBukti')->name('skp-tendik.download-bukti');
        // TAMBAHKAN 1 BARIS INI UNTUK MENGALIHKAN LINK KE MASTER TENDIK:
        Route::redirect('/skp-tendik', '/superadmin/master-skp-tendik/create');

        Route::get('/skp-dosen', 'skpDosenIndex')->name('skp-dosen.index');
        Route::get('/skp-dosen/create', 'skpDosenCreate')->name('skp-dosen.create');
        Route::post('/skp-dosen', 'skpDosenStore')->name('skp-dosen.store');
        Route::get('/skp-dosen/{id}/edit', 'skpDosenEdit')->name('skp-dosen.edit');
        Route::put('/skp-dosen/{id}', 'skpDosenUpdate')->name('skp-dosen.update');
        Route::get('/skp-dosen/{id}/detail', 'skpDosenShow')->name('skp-dosen.show');
        Route::delete('/skp-dosen/reset/{id}', 'skpDosenDestroy')->name('skp-dosen.destroyAll');

        Route::get('/settings', 'settingsIndex')->name('settings.index');
        Route::post('/settings/toggle', 'toggleSetting')->name('settings.toggle');
    });

    // DAFTAR TUPOKSI (KAMUS)
    Route::get('/daftar-tupoksi/template', [DaftarTupoksiController::class, 'downloadTemplate'])->name('daftar-tupoksi.template');
    Route::resource('daftar-tupoksi', DaftarTupoksiController::class);
    Route::post('/daftar-tupoksi/bulk-store', [DaftarTupoksiController::class, 'bulkStore'])->name('daftar-tupoksi.bulk-store');
    Route::get('/daftar-tupoksi/detail/{bidang}', [DaftarTupoksiController::class, 'showDetail'])->name('daftar-tupoksi.show-detail');

    Route::controller(MasterSkpTendikController::class)->group(function () {
        Route::get('/master-skp-tendik', 'index')->name('master-skp-tendik.index');
        Route::get('/master-skp-tendik/template', 'downloadTemplate')->name('master-skp-tendik.template');
        Route::get('/master-skp-tendik/create', 'create')->name('master-skp-tendik.create');
        Route::post('/master-skp-tendik', 'store')->name('master-skp-tendik.store');
        Route::post('/master-skp-tendik/import', 'import')->name('master-skp-tendik.import');
        Route::get('/master-skp-tendik/{id}', 'show')->name('master-skp-tendik.show');
        Route::get('/master-skp-tendik/{id}/edit', 'edit')->name('master-skp-tendik.edit');
        Route::put('/master-skp-tendik/{id}', 'update')->name('master-skp-tendik.update');
        Route::delete('/master-skp-tendik/{id}', 'destroy')->name('master-skp-tendik.destroy');
    });

    // TODO (2026-09-09): Route acuan.store & acuan.destroy berikut menunjuk endpoint
    // yang sudah dinonaktifkan (return 410) karena tabel skp_kualitatif_acuans tidak ada.
    // Frontend (acuan.blade.php) tidak pernah memanggilnya. Aman dihapus permanen
    // setelah log akses menunjukkan tidak ada pemanggil (cek laravel.log, response 410).
    Route::get('/skp-dosen-kualitatif-acuan', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'acuanSkp'])->name('skp-dosen-kualitatif.acuan');
    Route::post('/skp-dosen-kualitatif-acuan/update', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'updateAcuan'])->name('skp-dosen-kualitatif.acuan.update');
    Route::post('/skp-dosen-kualitatif-acuan/store', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'storeAcuan'])->name('skp-dosen-kualitatif.acuan.store');
    Route::post('/skp-dosen-kualitatif-acuan/add-parent', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'addParent'])->name('skp-dosen-kualitatif.acuan.add-parent');
    Route::post('/skp-dosen-kualitatif-acuan/delete-parent/{id}', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'deleteParent'])->name('skp-dosen-kualitatif.acuan.delete-parent');
    Route::post('/skp-dosen-kualitatif-acuan/add-child', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'addChild'])->name('skp-dosen-kualitatif.acuan.add-child');
    Route::post('/skp-dosen-kualitatif-acuan/delete-child/{id}', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'deleteChild'])->name('skp-dosen-kualitatif.acuan.delete-child');
    Route::delete('/skp-dosen-kualitatif-acuan/{id}', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'destroyAcuan'])->name('skp-dosen-kualitatif.acuan.destroy');
    Route::get('/skp-dosen-kualitatif-rumus', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'rumusHasil'])->name('skp-dosen-kualitatif.rumus');
    Route::post('/skp-dosen-kualitatif-rumus/update-pegawai', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'updateRumusPegawai'])->name('skp-dosen-kualitatif.rumus.update-pegawai');
    // Sinkron Ulang Persyaratan Lampiran (preview + apply, manual sadder eksplisit)
    Route::get('/skp-dosen-kualitatif/{header_id}/lampiran-sync/preview', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'previewSync'])->name('skp-dosen-kualitatif.lampiran-sync.preview');
    Route::post('/skp-dosen-kualitatif/{header_id}/lampiran-sync/apply', [App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class, 'applySync'])->name('skp-dosen-kualitatif.lampiran-sync.apply');
    Route::resource('skp-dosen-kualitatif', App\Http\Controllers\SuperAdmin\SkpDosenKualitatifController::class);

    // MANAJEMEN PRODI
    Route::resource('prodi', ProdiController::class)->except(['create', 'show', 'edit']);
});

// =========================================================================
// PROGRAM FAKULTAS, USULAN PRODI & LPJ (ALUR BARU)
// =========================================================================
Route::middleware(['auth', 'permission:akses-manajemen-anggaran'])->group(function () {
    Route::prefix('program-kegiatan')->name('program-kegiatan.')->controller(ProgramKegiatanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}/show', 'show')->name('show');
        Route::middleware('permission:kelola-master-anggaran')->group(function() {
            Route::delete('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}/edit', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{kegiatan}', 'destroy')->name('destroy');
            Route::post('/import', 'import')->name('import');
        });
        Route::get('/export', 'export')->name('export');
        Route::patch('/{kegiatan}/approve', 'approve')->name('approve')->middleware('permission:approve-pagu-anggaran');
    });

    Route::prefix('kegiatan-prodi')->name('kegiatan-prodi.')->controller(ProgramKegiatanProdiController::class)->group(function () {
        Route::get('/my-pengajuan', 'myPengajuan')->name('my-pengajuan');
        Route::get('/{kegiatan}/create', 'create')->name('create');
        Route::post('/{kegiatan}/store', 'store')->name('store');
        Route::get('/{pengajuan}/show', 'show')->name('show');
        Route::get('/{pengajuan}/download-proposal', 'downloadProposal')->name('download-proposal');
        Route::delete('/{pengajuan}', 'destroy')->name('destroy');
        Route::get('/list-admin', 'listAdmin')->name('list-admin');
        Route::patch('/{pengajuan}/approve', 'approve')->name('approve');
        Route::post('/{pengajuan}/cairkan', 'cairkan')->name('cairkan');
    });

    Route::prefix('lpj')->name('lpj.')->controller(LpjController::class)->group(function () {
        Route::get('/monitoring', 'monitoring')->name('monitoring');
        Route::get('/monitoring/{pengajuan}/detail', 'monitorDetail')->name('monitor-detail');
        Route::get('/my-lpj', 'myLpj')->name('my-lpj');
        Route::get('/create/{pengajuan}', 'create')->name('create');
        Route::post('/{pengajuan}/store', 'store')->name('store');
        Route::patch('/{lpj}/verify', 'verify')->name('verify');
        Route::get('/{lpj}/show', 'show')->name('show');
        Route::get('/{lpj}/download-bukti', 'downloadBukti')->name('download-bukti');
        Route::delete('/{lpj}', 'destroy')->name('destroy');
        Route::patch('/{pengajuan}/tutup', 'tutup')->name('tutup');
    });
});

// =========================================================================
// DASHBOARD STRUKTURAL
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // Rekap SKP (Fitur Baru)
    Route::prefix('rekap-skp')->name('rekap-skp.')->controller(RekapSkpController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/dosen/{periode_id}', 'dosen')->name('dosen');
        Route::get('/dosen-kualitatif/{periode_id}', 'dosenKualitatif')->name('dosen-kualitatif');
        Route::get('/tendik/{periode_id}', 'tendik')->name('tendik');
    });

    // Periode Anggaran (Fitur Baru)
    Route::middleware(['role:Admin Keuangan Fakultas|Admin Keuangan'])->group(function () {
        Route::resource('periode-anggaran', PeriodeAnggaranController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::prefix('pimpinan')->name('dekan.')->controller(DekanController::class)->group(function () {      
        Route::get('/persetujuan-skp', 'persetujuanSkp')->name('persetujuan-skp');
        Route::get('/persetujuan-skp/{id}/detail', 'showSkp')->name('persetujuan-skp.show');
        Route::post('/persetujuan-skp/{id}/approve', 'approveSkp')->name('persetujuan-skp.approve');        
        Route::post('/persetujuan-skp/{id}/tolak', 'tolakSkp')->name('persetujuan-skp.tolak');
    });

    Route::middleware('permission:akses-dashboard-pimpinan')->group(function() {
        Route::prefix('dekan')->name('dekan.')->controller(DekanController::class)->group(function () {
            Route::get('/dashboard', 'dashboard')->name('dashboard');
            Route::get('/data-prodi', 'dataProdi')->name('data-prodi');
            Route::get('/laporan', 'laporanFakultas')->name('laporan');
        });

        // PERUBAHAN: Penggabungan rute Wakil Dekan menjadi 1 rute
        Route::prefix('wakil-dekan')->name('wakildekan.')->controller(WakilDekanController::class)->group(function () {
            Route::get('/dashboard', 'dashboard')->name('dashboard');
        });
    });
    Route::middleware('permission:akses-dashboard-prodi')->group(function() {
        Route::prefix('kaprodi')->name('kaprodi.')->controller(KaprodiController::class)->group(function () {
            Route::get('/dashboard', 'dashboard')->name('dashboard');
        });
        Route::prefix('sekprodi')->name('sekprodi.')->controller(SekprodiController::class)->group(function () {
            Route::get('/dashboard', 'dashboard')->name('dashboard');
        });
    });

    Route::middleware('permission:akses-dashboard-lab')->prefix('kepala-lab')->name('kepalalab.')->controller(KepalaLaboratoriumController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('permission:akses-dashboard-dosen')->prefix('dosen')->name('dosen.')->controller(DosenController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('permission:akses-dashboard-admin-keuangan')->prefix('admin-keuangan-fakultas')->name('adminkeuanganfakultas.')->controller(AdminKeuanganFakultasController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('permission:akses-dashboard-rektorat')->prefix('rektorat')->name('rektorat.')->controller(RektoratController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('permission:akses-dashboard-upf')->prefix('unit-penunjang')->name('unitpenunjang.')->controller(UnitPenunjangController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });
});

require __DIR__.'/auth.php';
