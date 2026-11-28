<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\PeriodeSkp;
use App\Models\Prodi;
use App\Models\SkpDosen;
use App\Models\SkpHeader;
use App\Models\SkpTendik;
use App\Models\SkpTugasTambahan;
use App\Models\PenilaianPerilaku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // Pastikan Rule di-import
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\JenjangJabatan;
use App\Models\PangkatGolongan;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Exports\UserTemplateExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    // =========================================================================
    // KONSTANTA ROLE & DASHBOARD (sumber tunggal untuk rolesIndex/Store/Update)
    // Nama role bawaan dirujuk hardcode di sidebar, middleware role:, dan
    // whereHas('roles') di banyak controller — maka rename/hapus dilindungi.
    // =========================================================================

    private const PRODI_BAWAAN = ['Arsitektur', 'Teknik Elektro', 'Teknik Mesin', 'Teknik Perkapalan', 'Teknik Sipil', 'Teknik Industri', 'Informatika'];

    private const UPF_BAWAAN = ['Penelitian', 'Kemahasiswaan', 'Kerjasama', 'Humas', 'Laboratorium', 'Penjaminan Mutu'];

    private const TENDIK_BAWAAN = ['Tata Usaha', 'Kesekretariatan', 'Kepegawaian', 'Sumber Daya Manusia', 'Kesekretariatan (Kerumahtanggaan)', 'IT', 'Kerumahtanggaan', 'Sarana dan Prasarana', 'Humas', 'Kerjasama dan Internasional', 'Front Office', 'Laboran', 'Administrasi Prodi', 'Admin Keuangan Fakultas'];

    public const BASE_DASHBOARDS = [
        'dekan.dashboard' => 'Dashboard Pimpinan (Dekan)',
        'wakildekan.dashboard' => 'Dashboard Pimpinan (Wakil Dekan)',
        'rektorat.dashboard' => 'Dashboard Pimpinan (Rektorat)',
        'kaprodi.dashboard' => 'Dashboard Struktural Prodi (Kaprodi)',
        'sekprodi.dashboard' => 'Dashboard Struktural Prodi (Sekprodi)',
        'kepalalab.dashboard' => 'Dashboard Kepala Laboratorium',
        'dosen.dashboard' => 'Dashboard Dosen',
        'adminkeuanganfakultas.dashboard' => 'Dashboard Admin Keuangan',
        'unitpenunjang.dashboard' => 'Dashboard Unit Penunjang',
        'pegawai.dashboard' => 'Dashboard Pegawai / Tendik Umum'
    ];

    public const DASHBOARD_PERMISSIONS = [
        'dekan.dashboard' => ['akses-dashboard-pimpinan'],
        'wakildekan.dashboard' => ['akses-dashboard-pimpinan'],
        'rektorat.dashboard' => ['akses-dashboard-pimpinan', 'akses-dashboard-rektorat'],
        'kaprodi.dashboard' => ['akses-dashboard-prodi'],
        'sekprodi.dashboard' => ['akses-dashboard-prodi'],
        'kepalalab.dashboard' => ['akses-dashboard-lab'],
        'dosen.dashboard' => ['akses-dashboard-dosen'],
        'adminkeuanganfakultas.dashboard' => ['akses-dashboard-admin-keuangan'],
        'unitpenunjang.dashboard' => ['akses-dashboard-upf'],
        'pegawai.dashboard' => [],
    ];

    // Role 'Super Admin' dikunci penuh: editor di area ini pasti Super Admin
    // sendiri, jadi mengubahnya = superadmin mencabut akses dirinya sendiri.
    public const LOCKED_ROLE = 'Super Admin';

    public static function builtInRoleNames(): array
    {
        $names = ['Super Admin', 'Dekan', 'Wadek 1', 'Wadek 2', 'Rektorat'];
        foreach (self::PRODI_BAWAAN as $prodi) {
            $names[] = "Kaprodi $prodi";
            $names[] = "Sekprodi $prodi";
            $names[] = "Kepala Lab $prodi";
            $names[] = "Dosen $prodi";
        }
        foreach (self::UPF_BAWAAN as $upf) {
            $names[] = "Kepala UPF $upf";
        }
        return array_merge($names, self::TENDIK_BAWAAN);
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function dashboard()
    {
        $totalUsers = User::count();
        $totalPeriodeSkp = PeriodeSkp::count();
        $totalProdi = Prodi::count();
        $totalRole = Role::count();
        $totalSkpDosen = SkpDosen::count();
        $totalSkpTendik = SkpTendik::count();

        // Breakdown status SKP
        $skpStatuses = ['Draft', 'Diajukan', 'Pending Utama', 'Pending Akhir', 'Disetujui'];
        $skpStatusCounts = [];
        foreach ($skpStatuses as $status) {
            $skpStatusCounts[$status] = SkpHeader::where('status', $status)->count();
        }

        // Distribusi user per role (top 10 berdasarkan jumlah user)
        $rolesDist = Role::withCount('users')->orderByDesc('users_count')->limit(10)->get();

        /*
         * SPARKLINE / TREN HISTORIS
         * ------------------------------------------------
         * Saat ini HANYA "Total Users" yang diberi sparkline ($userTrend),
         * karena tabel users punya created_at dengan variasi waktu nyata
         * (user dibuat bertahap dari waktu ke waktu) sehingga tren kumulatif
         * mingguan punya makna.
         *
         * 5 metrik berikut SENGAJA tidak diberi sparkline:
         *   - Total Prodi      : dibuat serentak via seeder (created_at sama)
         *   - Total Periode SKP: hanya 3 row, semua di awal Juli (data usang)
         *   - Total Role        : dibuat serentak via seeder (created_at sama)
         *   - Total SKP Dosen   : tabel masih kosong (count = 0)
         *   - Total SKP Tendik  : tabel masih kosong (count = 0)
         * Kelima metrik tersebut belum punya variasi data historis yang cukup,
         * sehingga sparkline akan flat/kosong (tidak informatif).
         *
         * CATATAN PENGEMBANGAN KE DEPAN:
         * Begitu data SKP Dosen / SKP Tendik (dan metrik lain) mulai terisi
         * dari waktu ke waktu, sparkline serupa $userTrend bisa ditambahkan
         * dengan pola query yang sama:
         *   - hitung kumulatif mingguan via whereBetween('created_at', [...])
         *     untuk N minggu terakhir, lalu masukkan ke compact() dan render
         *     di canvas card terkait (lihat #sparkUsers di view).
         * Tidak ada perubahan struktur diperlukan — cukup tambah variabel
         * trend + canvas saat datanya sudah memadai.
         */
        // Sparkline Total Users: kumulatif mingguan 8 minggu terakhir (created_at asli)
        $userTrend = [];
        $cumulative = 0;
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = now()->subWeeks($i)->endOfWeek();
            $cumulative += User::whereBetween('created_at', [$start, $end])->count();
            $userTrend[] = $cumulative;
        }

        return view('super-admin.dashboard', compact(
            'totalUsers', 'totalPeriodeSkp', 'totalProdi', 'totalRole',
            'totalSkpDosen', 'totalSkpTendik',
            'skpStatuses', 'skpStatusCounts', 'rolesDist', 'userTrend'
        ));
    }

    // =========================================================================
    // KELOLA ROLE DINAMIS
    // =========================================================================

    public function rolesIndex()
    {
        $roles = Role::withCount('users')->with('permissions:id,name')->get()->sortBy(function($role) {
            $roleName = strtolower($role->name);
            if (str_contains($roleName, 'super admin')) return 0;
            if (str_contains($roleName, 'rektor')) return 1;
            if (str_contains($roleName, 'dekan')) return 2;
            if (str_contains($roleName, 'wadek')) return 3;
            if (str_contains($roleName, 'kaprodi')) return 4;
            if (str_contains($roleName, 'sekprodi')) return 5;
            if (str_contains($roleName, 'kepala lab')) return 6;
            if (str_contains($roleName, 'dosen')) return 7;
            if (str_contains($roleName, 'upf')) return 8;
            return 99; // others
        })->values();

        $builtInRoles = self::builtInRoleNames();
        $baseDashboards = self::BASE_DASHBOARDS;
        // Peta permission per dashboard untuk ringkasan selisih di modal edit (client-side).
        $dashboardPermissions = self::DASHBOARD_PERMISSIONS;

        return view('super-admin.roles.index', compact('roles', 'builtInRoles', 'baseDashboards', 'dashboardPermissions'));
    }

    public function rolesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'kategori' => 'required|in:utama,tendik',
            'dashboard_route' => ['required', 'string', Rule::in(array_keys(self::BASE_DASHBOARDS))]
        ], [
            'dashboard_route.in' => 'Template dashboard tidak valid. Pilih salah satu dari daftar.',
            'name.max' => 'Nama jabatan maksimal 100 karakter.'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'kategori' => $request->kategori,
            'dashboard_route' => $request->dashboard_route,
            'guard_name' => 'web'
        ]);

        $this->syncDashboardPermissions($role, null, $request->dashboard_route);

        return back()->with('success', 'Jabatan / Role baru berhasil ditambahkan beserta hak akses dasarnya!');
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        // Role terkunci (Super Admin): eksekutor di area ini pasti Super Admin
        // sendiri — mengubahnya berarti superadmin mencabut akses dirinya sendiri.
        if ($role->name === self::LOCKED_ROLE) {
            abort(403, 'Role Super Admin tidak boleh diubah.');
        }

        // Role bawaan sistem tidak boleh di-rename: nama dipakai hardcode di
        // sidebar, middleware role:, dan whereHas('roles') di banyak controller.
        if (in_array($role->name, self::builtInRoleNames(), true) && $request->name !== $role->name) {
            return back()->withInput()->with('error', 'Gagal: Role bawaan sistem tidak boleh di-rename. Nama role dipakai untuk menu sidebar dan pemeriksaan hak akses di banyak modul.');
        }

        $allowedRoutes = array_keys(self::BASE_DASHBOARDS);
        // Role non-bawaan boleh mempertahankan dashboard_route custom existing-nya.
        if (!in_array($role->dashboard_route, $allowedRoutes, true) && !empty($role->dashboard_route)) {
            $allowedRoutes[] = $role->dashboard_route;
        }

        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($role->id)],
            'kategori' => 'required|in:utama,tendik',
            'dashboard_route' => ['required', 'string', Rule::in($allowedRoutes)]
        ], [
            'dashboard_route.in' => 'Template dashboard tidak valid. Pilih salah satu dari daftar.',
            'name.max' => 'Nama jabatan maksimal 100 karakter.'
        ]);

        $dashboardBerubah = $request->dashboard_route !== $role->dashboard_route;
        // Tangkap nilai lama SEBELUM update — Model::update() menjalankan
        // syncOriginal(), sehingga getOriginal() setelah update sudah bernilai baru.
        $routeLama = $role->dashboard_route;

        $role->update([
            'name' => $role->name === $request->name ? $role->name : $request->name,
            'kategori' => $request->kategori,
            'dashboard_route' => $request->dashboard_route,
        ]);

        if ($dashboardBerubah) {
            $this->syncDashboardPermissions($role, $routeLama, $request->dashboard_route);
        }

        // Update role (rename/ubah atribut) tidak memicu auto-flush cache Spatie.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Jabatan / Role berhasil diperbarui!');
    }

    // Sinkronisasi permission role berbasis SELISIH dua peta dashboard, hanya
    // dipanggil saat dashboard_route berubah.
    // - Cabut permission yang ada di peta dashboard LAMA tapi tidak di peta BARU.
    // - Tambahkan permission peta dashboard BARU.
    // - Permission di luar kedua peta (mis. akses-manajemen-anggaran) TIDAK disentuh.
    // - Peta kosong (pegawai.dashboard) = himpunan kosong biasa, tanpa pengecualian.
    // - Tidak pernah memanggil syncPermissions([]) pada seluruh permission role.
    private function syncDashboardPermissions(Role $role, ?string $routeLama, string $routeBaru): void
    {
        $petaLama = $routeLama === null ? [] : (self::DASHBOARD_PERMISSIONS[$routeLama] ?? []);
        $petaBaru = self::DASHBOARD_PERMISSIONS[$routeBaru] ?? [];

        $dicabut = array_diff($petaLama, $petaBaru);
        $ditambah = array_diff($petaBaru, $petaLama);

        if (!empty($dicabut)) {
            $role->revokePermissionTo($dicabut);
        }

        if (!empty($ditambah)) {
            $role->givePermissionTo($ditambah);
        }
    }

    public function rolesDestroy(Role $role)
    {
        if (in_array($role->name, self::builtInRoleNames(), true)) {
            return back()->with('error', 'Gagal: Role bawaan sistem tidak boleh dihapus karena dipakai untuk menu sidebar dan pemeriksaan hak akses di banyak modul.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Gagal: Jabatan ini masih digunakan oleh ' . $role->users()->count() . ' pegawai.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', 'Jabatan / Role berhasil dihapus!');
    }

    public function rolesBulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:roles,id'
        ]);

        $builtIn = self::builtInRoleNames();
        $rolesInUse = 0;
        $builtInSelected = 0;
        foreach ($request->ids as $id) {
            $role = Role::find($id);
            if (!$role) {
                continue;
            }
            if (in_array($role->name, $builtIn, true)) {
                $builtInSelected++;
                continue;
            }
            if ($role->users()->count() > 0) {
                $rolesInUse++;
            }
        }

        if ($builtInSelected > 0) {
            return back()->with('error', 'Gagal: Ada ' . $builtInSelected . ' role bawaan sistem dalam pilihan Anda. Role bawaan tidak boleh dihapus.');
        }

        if ($rolesInUse > 0) {
            return back()->with('error', 'Gagal: Ada ' . $rolesInUse . ' jabatan yang masih digunakan oleh pegawai dalam pilihan Anda!');
        }

        Role::whereIn('id', $request->ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', count($request->ids) . ' Jabatan berhasil dihapus!');
    }

    // =========================================================================
    // USER MANAGEMENT
    // =========================================================================

    public function usersIndex()
    {
        $users = User::with(['profile', 'roles'])->get()->sortBy(function($user) {
            $roleName = strtolower($user->roles->first()?->name ?? 'z_tendik');
            if (str_contains($roleName, 'super admin')) return 0;
            if (str_contains($roleName, 'rektor')) return 1;
            if (str_contains($roleName, 'dekan')) return 2;
            if (str_contains($roleName, 'wadek')) return 3;
            if (str_contains($roleName, 'kaprodi')) return 4;
            if (str_contains($roleName, 'sekprodi')) return 5;
            if (str_contains($roleName, 'kepala lab')) return 6;
            if (str_contains($roleName, 'dosen')) return 7;
            if (str_contains($roleName, 'upf')) return 8;
            return 99; // others
        })->values();

        return view('super-admin.users.index', compact('users'));
    }

    public function usersCreate()
    {
        $jabatanUtamaRoles = Role::where('kategori', 'utama')->get();
        $peranTendikRoles = Role::where('kategori', 'tendik')->get();
        $jabatanFungsional = ['Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Guru Besar / Profesor'];
        $jenjangJabatans = JenjangJabatan::orderBy('urutan')->get();

        return view('super-admin.users.create', compact('jabatanUtamaRoles', 'peranTendikRoles', 'jabatanFungsional', 'jenjangJabatans'));
    }

    public function usersEdit(User $user)
    {
        $user->load(['profile', 'profile.pangkatGolongan.jenjangJabatan']);

        $jabatanUtamaRoles = Role::where('kategori', 'utama')->get();
        $peranTendikRoles = Role::where('kategori', 'tendik')->get();
        $jabatanFungsional = ['Asisten Ahli', 'Lektor', 'Lektor Kepala', 'Guru Besar / Profesor'];
        $jenjangJabatans = JenjangJabatan::orderBy('urutan')->get();

        return view('super-admin.users.edit', compact('user', 'jabatanUtamaRoles', 'peranTendikRoles', 'jabatanFungsional', 'jenjangJabatans'));
    }

    public function usersStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role_utama' => 'nullable|exists:roles,name',
            'roles_tendik' => 'required_without:role_utama|array',
            'roles_tendik.*' => 'exists:roles,name',
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:20|unique:user_profiles,nip',
            'pangkat_golongan_id' => 'nullable|exists:pangkat_golongans,id',
            'pangkat_golongan'    => 'nullable|string|max:100',
            'jabatan_fungsional' => 'nullable|string|max:100',
            'unit_kerja' => 'nullable|string|max:100',
        ], [
            'roles_tendik.required_without' => 'Anda harus memilih minimal satu Jabatan Utama atau Peran Tendik.'
        ]);

        if ($request->filled('role_utama') && !str_contains($request->role_utama, 'Dosen')) {
            $existingUser = User::role($request->role_utama)->first();
            if ($existingUser) {
                return back()->withErrors(['role_utama' => "Jabatan {$request->role_utama} sudah diisi oleh {$existingUser->profile->nama_lengkap}!"])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            $jabatanPekerjaanText = $request->role_utama ?? (is_array($request->roles_tendik) ? implode(', ', $request->roles_tendik) : 'Tenaga Kependidikan');

            $pangkatGolonganId = null;
            $pangkatGolonganString = $request->pangkat_golongan;

            if ($request->filled('pangkat_golongan_id') && $request->pangkat_golongan_id !== 'manual') {
                $pg = PangkatGolongan::find($request->pangkat_golongan_id);
                $pangkatGolonganId = $pg->id;
                $pangkatGolonganString = $pg->kode_pangkat;
            }

            UserProfile::create([
                'user_id' => $user->id,
                'nama_lengkap' => $request->nama,
                'nip' => $request->nip,
                'pangkat_golongan' => $pangkatGolonganString,
                'pangkat_golongan_id' => $pangkatGolonganId,
                'jabatan_pekerjaan' => $jabatanPekerjaanText,
                'jabatan_fungsional' => $request->jabatan_fungsional,
                'unit_kerja' => $request->unit_kerja,
            ]);

            $rolesToAssign = [];
            if ($request->filled('role_utama')) { 
                $rolesToAssign[] = $request->role_utama; 
            } elseif ($request->has('roles_tendik')) { 
                $rolesToAssign = $request->roles_tendik; 
            }
            $user->syncRoles($rolesToAssign);

            DB::commit();
            return redirect()->route('superadmin.users.index')->with('success', 'Pegawai berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    public function usersUpdate(Request $request, User $user)
    {
        $request->validate([
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
            'role_utama' => 'nullable|exists:roles,name',
            'roles_tendik' => 'required_without:role_utama|array',
            'roles_tendik.*' => 'exists:roles,name',
            'nama' => 'required|string|max:255',
            'nip' => ['required', 'string', 'max:20', Rule::unique('user_profiles')->ignore($user->profile->id ?? null)],
            'pangkat_golongan_id' => 'nullable|exists:pangkat_golongans,id',
            'pangkat_golongan'    => 'nullable|string|max:100',
            'jabatan_fungsional' => 'nullable|string|max:100',
            'unit_kerja' => 'nullable|string|max:100',
        ], [
            'roles_tendik.required_without' => 'Anda harus memilih minimal satu Jabatan Utama atau Peran Tendik.'
        ]);

        if ($request->filled('role_utama') && !str_contains($request->role_utama, 'Dosen')) {
            $existingUser = User::role($request->role_utama)->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                return back()->withErrors(['role_utama' => "Jabatan {$request->role_utama} sudah diisi oleh {$existingUser->profile->nama_lengkap}!"])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $userData = ['email' => $request->email];
            if ($request->filled('password')) { $userData['password'] = Hash::make($request->password); }
            $user->update($userData);

            $jabatanPekerjaanText = $request->role_utama ?? (is_array($request->roles_tendik) ? implode(', ', $request->roles_tendik) : 'Tenaga Kependidikan');

            $pangkatGolonganId = null;
            $pangkatGolonganString = $request->pangkat_golongan;

            if ($request->filled('pangkat_golongan_id') && $request->pangkat_golongan_id !== 'manual') {
                $pg = PangkatGolongan::find($request->pangkat_golongan_id);
                $pangkatGolonganId = $pg->id;
                $pangkatGolonganString = $pg->kode_pangkat;
            }

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lengkap' => $request->nama,
                    'nip' => $request->nip,
                    'pangkat_golongan' => $pangkatGolonganString,
                    'pangkat_golongan_id' => $pangkatGolonganId,
                    'jabatan_pekerjaan' => $jabatanPekerjaanText,
                    'jabatan_fungsional' => $request->jabatan_fungsional,
                    'unit_kerja' => $request->unit_kerja,
                ]
            );

            $rolesToAssign = [];
            if ($request->filled('role_utama')) { 
                $rolesToAssign[] = $request->role_utama; 
            } elseif ($request->has('roles_tendik')) { 
                $rolesToAssign = $request->roles_tendik; 
            }
            $user->syncRoles($rolesToAssign);

            DB::commit();
            return redirect()->route('superadmin.users.index')->with('success', 'Pegawai berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal update: ' . $e->getMessage()])->withInput();
        }
    }

    public function usersDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri saat sedang login!');
        }

        $isOnline = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->getTimestamp() - 300)
            ->exists();

        if ($isOnline) {
            return back()->with('error', 'Gagal: Akun ini sedang online/aktif digunakan!');
        }

        $user->delete();
        return redirect()->route('superadmin.users.index')->with('success', 'Pegawai berhasil dihapus!');
    }

    public function usersBulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id'
        ]);

        if (in_array(auth()->id(), $request->ids)) {
            return back()->with('error', 'Gagal: Anda memilih akun Anda sendiri untuk dihapus!');
        }

        $onlineIds = DB::table('sessions')
            ->whereIn('user_id', $request->ids)
            ->where('last_activity', '>=', now()->getTimestamp() - 300)
            ->distinct()
            ->pluck('user_id');

        if (count($onlineIds) > 0) {
            return back()->with('error', 'Gagal: Ada ' . count($onlineIds) . ' akun yang sedang online/aktif digunakan dalam pilihan Anda!');
        }

        User::whereIn('id', $request->ids)->delete();
        return redirect()->route('superadmin.users.index')->with('success', count($request->ids) . ' Pegawai berhasil dihapus!');
    }

    public function usersImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
            return redirect()->route('superadmin.users.index')->with('success', 'Data user berhasil diimport!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengimport data: ' . $e->getMessage()]);
        }
    }

    public function usersDownloadTemplate()
    {
        return Excel::download(new UserTemplateExport, 'template_import_user.xlsx');
    }

    // =========================================================================
    // SKP TENDIK
    // =========================================================================

    public function skpTendikIndex(Request $request)
    {
        // mulai desc lalu id desc → default aktif deterministik bila ada 2 aktif
        $periodes = PeriodeSkp::orderBy('mulai', 'desc')->orderBy('id', 'desc')->get();
        $periodeAktif = $periodes->where('status', 'aktif')->first();
        $periodeId = $request->periode_id ?? $periodeAktif?->id;

        $headers = collect();
        $skpGrouped = collect();

        if ($periodeId) {
            $headers = \App\Models\SkpHeader::where('periode_skp_id', $periodeId)
                ->with(['user.profile', 'pejabatPenilai.profile', 'penilaiUtama.profile', 'penilaiAkhir.profile'])
                ->get()
                ->keyBy('user_id');

            $skpGrouped = \App\Models\SkpTendik::where('periode_skp_id', $periodeId)
                ->with(['user.profile'])
                ->get()
                ->groupBy('user_id');
        }

        return view('super-admin.skp-tendik.index', compact('periodes', 'skpGrouped', 'headers', 'periodeId'));
    }

    public function skpTendikCreate(Request $request)
    {
        // 1. Data Dasar untuk Modal Pemilihan
        // mulai desc lalu id desc → default aktif deterministik bila ada 2 aktif
        $periode = PeriodeSkp::orderBy('mulai', 'desc')->orderBy('id', 'desc')->get();
        $periodeAktif = $periode->where('status', 'aktif')->first();
        $periodeId = $request->periode_id ?? $periodeAktif?->id;

        $usersTendik = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'tendik');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        // Penilai Utama: Dekan, Wadek 1, dan Wadek 2
        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Wadek 1', 'Wadek 2']);
        })->with('profile', 'roles')->get()->sortBy('name');

        // Penilai Akhir: Dekan atau Rektor (Manual)
        $calonPenilaiAkhir = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Rektorat', 'Rektor']);
        })->with('profile', 'roles')->get()->sortBy('name');

        $jsonPegawai = $usersTendik->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->first() ?? '-'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        $jsonPenilaiAkhir = $calonPenilaiAkhir->map(fn($cpa) => [
            'id' => $cpa->id,
            'nama' => $cpa->profile->nama_lengkap ?? $cpa->name,
            'nip' => $cpa->profile->nip ?? '-',
            'pangkat' => $cpa->profile->pangkat_golongan ?? '-',
            'jabatan' => $cpa->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        // 2. Data untuk Form Input (Jika target sudah dipilih)
        $user = null;
        $selPeriode = null;
        $penilai = null;
        $penilaiAkhir = null;
        $groupedMasters = collect();

        if ($request->has(['user_id', 'periode_skp_id', 'pejabat_penilai_id', 'penilai_akhir_id'])) {
            $user = User::with('profile')->findOrFail($request->user_id);
            $selPeriode = PeriodeSkp::findOrFail($request->periode_skp_id);
            $penilai = User::with('profile')->findOrFail($request->pejabat_penilai_id);
            $penilaiAkhir = User::with('profile')->findOrFail($request->penilai_akhir_id);

            $masters = \App\Models\MasterSkpTendik::where('user_id', $user->id)
                ->where('status_approval', 'approved')
                ->get();

            // Tetap berikan pesan error jika master belum ada/approved, tapi jangan redirect ke 'create' yang lama
            if ($masters->isEmpty()) {
                session()->flash('error', "Gagal: Belum ada Master Template SKP personal untuk {$user->profile->nama_lengkap} yang sudah DI-APPROVE.");
            } else {
                $groupedMasters = $masters->groupBy(fn($item) => trim(preg_replace('/\s+/', ' ', $item->tupoksi)));
            }
        }

        return view('super-admin.skp-tendik.create_form', compact(
            'periode', 'periodeId', 'jsonPegawai', 'jsonPenilai', 'jsonPenilaiAkhir',
            'user', 'selPeriode', 'penilai', 'penilaiAkhir', 'groupedMasters'
        ));
    }

    public function skpTendikStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
            'penilai_akhir_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.tupoksi' => 'required|string',
            'items.*.activities' => 'required|array',
            'items.*.activities.*.jenis_aktivitas' => 'required|string',
            'items.*.activities.*.ak' => 'required|numeric|min:0',
            'items.*.activities.*.kuantitas' => 'required|numeric|min:0',
            'items.*.activities.*.kualitas' => 'required|numeric|min:0|max:100',
            'items.*.activities.*.waktu' => 'required|numeric|min:0|max:12',
            'items.*.activities.*.biaya' => 'nullable|numeric|min:0',
            'perilaku' => 'required|array',
            'perilaku.*.indicators' => 'required|array|min:1',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // ✅ CEK APAKAH STATUS SUDAH "Selesai" (Security Check)
            $checkSelesai = \App\Models\SkpHeader::where('periode_skp_id', $request->periode_skp_id)
                ->where('user_id', $request->user_id)
                ->where('status', 'Selesai')
                ->exists();

            if ($checkSelesai) {
                throw new \Exception("SKP untuk pegawai ini pada periode tersebut sudah berstatus 'Selesai' (Final) dan tidak dapat dimodifikasi atau ditambah lagi.");
            }

            $user = User::find($request->user_id);
            $roleName = $user->getRoleNames()->first() ?? 'Tendik';

            foreach ($request->items as $tupoksiData) {
                $skpInduk = \App\Models\SkpTendik::updateOrCreate(
                    [
                        'periode_skp_id' => $request->periode_skp_id,
                        'user_id' => $request->user_id,
                        'tupoksi' => $tupoksiData['tupoksi']
                    ],
                    ['role_name' => $roleName]
                );

                foreach ($tupoksiData['activities'] as $act) {
                    \App\Models\SkpTendikDetail::updateOrCreate(
                        [
                            'skp_tendik_id' => $skpInduk->id,
                            'jenis_aktivitas' => $act['jenis_aktivitas'],
                        ],
                        [
                            'ak' => $act['ak'],
                            'kuantitas_output' => $act['kuantitas'],
                            'kualitas_mutu' => $act['kualitas'],
                            'waktu' => $act['waktu'],
                            'biaya' => $act['biaya'] ?? 0
                        ]
                    );
                }
            }

            $header = \App\Models\SkpHeader::updateOrCreate(
                ['periode_skp_id' => $request->periode_skp_id, 'user_id' => $request->user_id],
                [
                    'pejabat_penilai_id' => $request->pejabat_penilai_id,
                    'penilai_utama_id' => $request->pejabat_penilai_id,
                    'penilai_akhir_id' => $request->penilai_akhir_id,
                    'status' => 'Draft',
                    'catatan_penilai' => null
                ]
            );

            // Simpan Indikator Perilaku
            \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
            foreach ($request->perilaku as $aspek => $data) {
                $count = count($data['indicators']);
                $bobot = 100 / ($count > 0 ? $count : 1);
                foreach ($data['indicators'] as $ind) {
                    if (empty($ind)) continue;
                    \App\Models\SkpPerilakuIndicator::create([
                        'skp_header_id' => $header->id,
                        'aspek' => $aspek,
                        'indikator' => $ind,
                        'bobot_persen' => $bobot,
                        'is_from_admin' => true
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('superadmin.skp-tendik.index')->with('success', 'SKP Tendik berhasil dibuat dan dikirim ke Penilai Utama.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function skpTendikEdit(Request $request, $user_id)
    {
        $periodeId = $request->periode_id;
        $user = User::with('profile')->findOrFail($user_id);
        
        // Load data periode
        $selPeriode = PeriodeSkp::findOrFail($periodeId);
        $periode = PeriodeSkp::orderBy('mulai', 'desc')->get();

        $header = \App\Models\SkpHeader::where('user_id', $user_id)
                    ->where('periode_skp_id', $periodeId)
                    ->with(['pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'user.profile'])
                    ->firstOrFail();

        // ✅ CEK STATUS
        if ($header->status === 'Disetujui') {
            return redirect()->route('superadmin.skp-tendik.index', ['periode_id' => $periodeId])
                             ->with('error', 'Gagal: Target SKP tidak dapat diubah karena sudah disetujui (Approved).');
        }

        $penilai = $header->pejabatPenilai;

        $skpTendiks = \App\Models\SkpTendik::where('user_id', $user_id)
                        ->where('periode_skp_id', $periodeId)
                        ->with('details')
                        ->get();

        $indicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');

        // Data tambahan untuk Setup Modal di View Edit
        $usersTendik = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'tendik');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        $calonPenilai = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Wadek 1', 'Wadek 2']);
        })->with('profile', 'roles')->get()->sortBy('name');

        $calonPenilaiAkhir = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Rektorat', 'Rektor']);
        })->with('profile', 'roles')->get()->sortBy('name');

        $jsonPegawai = $usersTendik->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->first() ?? '-'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        $jsonPenilaiAkhir = $calonPenilaiAkhir->map(fn($cpa) => [
            'id' => $cpa->id,
            'nama' => $cpa->profile->nama_lengkap ?? $cpa->name,
            'nip' => $cpa->profile->nip ?? '-',
            'pangkat' => $cpa->profile->pangkat_golongan ?? '-',
            'jabatan' => $cpa->getRoleNames()->implode(', ') ?? '-'
        ])->values();

        $penilaiAkhir = $header->penilaiAkhir;

        return view('super-admin.skp-tendik.edit', compact(
            'user', 'periode', 'selPeriode', 'penilai', 'penilaiAkhir', 'skpTendiks', 'indicators',
            'periodeId', 'jsonPegawai', 'jsonPenilai', 'jsonPenilaiAkhir'
        ));
    }

    public function skpTendikUpdate(Request $request, $user_id)
    {
        $request->validate([
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.tupoksi' => 'required|string',
            'items.*.activities' => 'required|array',
            'items.*.activities.*.jenis_aktivitas' => 'required|string',
            'items.*.activities.*.ak' => 'required|numeric|min:0',
            'items.*.activities.*.kuantitas' => 'required|numeric|min:0',
            'items.*.activities.*.kualitas' => 'required|numeric|min:0|max:100',
            'items.*.activities.*.waktu' => 'required|numeric|min:0|max:12',
            'items.*.activities.*.biaya' => 'nullable|numeric|min:0',
            'perilaku' => 'required|array',
            'perilaku.*.indicators' => 'required|array|min:1',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $user = User::findOrFail($user_id);
            $roleName = $user->getRoleNames()->first() ?? 'Tendik';

            // ✅ CEK STATUS (Security Check)
            $header = \App\Models\SkpHeader::where('user_id', $user_id)
                ->where('periode_skp_id', $request->periode_skp_id)
                ->first();

            if ($header && $header->status === 'Disetujui') {
                return redirect()->route('superadmin.skp-tendik.index', ['periode_id' => $request->periode_skp_id])
                                 ->with('error', 'Gagal: Target SKP tidak dapat diperbarui karena sudah disetujui.');
            }

            \App\Models\SkpTendik::where('periode_skp_id', $request->periode_skp_id)
                ->where('user_id', $user_id)
                ->delete();

            foreach ($request->items as $tupoksiData) {
                $skpInduk = \App\Models\SkpTendik::create([
                    'periode_skp_id' => $request->periode_skp_id,
                    'user_id' => $user_id,
                    'role_name' => $roleName,
                    'tupoksi' => $tupoksiData['tupoksi']
                ]);

                foreach ($tupoksiData['activities'] as $act) {
                    \App\Models\SkpTendikDetail::create([
                        'skp_tendik_id' => $skpInduk->id,
                        'jenis_aktivitas' => $act['jenis_aktivitas'],
                        'ak' => $act['ak'],
                        'kuantitas_output' => $act['kuantitas'],
                        'kualitas_mutu' => $act['kualitas'],
                        'waktu' => $act['waktu'],
                        'biaya' => $act['biaya'] ?? 0
                    ]);
                }
            }

            $dekan = User::role('Dekan')->first();
            $header = \App\Models\SkpHeader::updateOrCreate(
                ['periode_skp_id' => $request->periode_skp_id, 'user_id' => $user_id],
                [
                    'pejabat_penilai_id' => $request->pejabat_penilai_id,
                    'penilai_utama_id' => $request->pejabat_penilai_id,
                    'penilai_akhir_id' => $dekan?->id,
                    'status' => 'Draft',
                    'catatan_penilai' => null
                ]
            );

            // Update Indikator Perilaku
            \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
            foreach ($request->perilaku as $aspek => $data) {
                $count = count($data['indicators']);
                $bobot = 100 / ($count > 0 ? $count : 1);
                foreach ($data['indicators'] as $ind) {
                    if (empty($ind)) continue;
                    \App\Models\SkpPerilakuIndicator::create([
                        'skp_header_id' => $header->id,
                        'aspek' => $aspek,
                        'indikator' => $ind,
                        'bobot_persen' => $bobot,
                        'is_from_admin' => true
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('superadmin.skp-tendik.index', ['periode_id' => $request->periode_skp_id])->with('success', 'Target SKP Tendik & Perilaku berhasil diperbarui!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function skpTendikShow($user_id, Request $request)
    {
        $user = User::with('profile')->findOrFail($user_id);
        $skpTendiks = \App\Models\SkpTendik::where('user_id', $user_id)->where('periode_skp_id', $request->periode_id)->with('details')->get();
        $header = \App\Models\SkpHeader::where('user_id', $user_id)->where('periode_skp_id', $request->periode_id)->with('pejabatPenilai.profile')->first();
        
        $indicators = collect();
        if ($header) {
            $indicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');
        }

        return view('super-admin.skp-tendik.show', compact('user', 'skpTendiks', 'header', 'indicators'));
    }

    public function skpTendikDestroy(Request $request, $user_id)
    {
        $periodeId = $request->periode_id;

        DB::beginTransaction();
        try {
            // 1. Hapus Daftar Kegiatan (Anak-anaknya akan terhapus otomatis via Cascade)
            $skpQuery = \App\Models\SkpTendik::where('user_id', $user_id);
            if ($periodeId) {
                $skpQuery->where('periode_skp_id', $periodeId);
            }
            $skpQuery->delete();

            // 2. Hapus Header & Indikator Perilaku (Indikator terhapus otomatis via Cascade)
            $headerQuery = \App\Models\SkpHeader::where('user_id', $user_id);
            if ($periodeId) {
                $headerQuery->where('periode_skp_id', $periodeId);
            }
            $headerQuery->delete();

            // 3. Hapus Tugas Tambahan
            $tugasQuery = \App\Models\SkpTugasTambahan::where('user_id', $user_id);
            if ($periodeId) {
                $tugasQuery->where('periode_skp_id', $periodeId);
            }
            $tugasQuery->delete();

            // 4. Hapus Penilaian Perilaku (Jika Ada)
            $penilaianPerilakuQuery = \App\Models\PenilaianPerilaku::where('user_id', $user_id);
            if ($periodeId) {
                $penilaianPerilakuQuery->where('periode_skp_id', $periodeId);
            }
            $penilaianPerilakuQuery->delete();

            DB::commit();
            return back()->with('success', 'Seluruh rincian target SKP, tugas tambahan, dan target perilaku pegawai berhasil dihapus/reset.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }


    // =========================================================================
    // SKP DOSEN (TRIDHARMA)
    // =========================================================================

    public function skpDosenIndex(Request $request)
    {
        $periode = PeriodeSkp::orderBy('tahun', 'desc')->orderBy('mulai', 'desc')->orderBy('id', 'desc')->get();
        $periodeId = $request->periode_id ?? ($periode->where('status', 'aktif')->first()?->id);

        // Ambil Header SKP yang dimiliki oleh pegawai dengan role kategori 'utama' (Dosen/Struktural)
        $query = \App\Models\SkpHeader::whereHas('user.roles', function($q) {
            $q->where('kategori', 'utama');
        })->whereHas('skpDosens')->with(['user.profile', 'periode', 'pejabatPenilai.profile', 'skpDosens']);

        if ($periodeId) {
            $query->where('periode_skp_id', $periodeId);
        }

        $headers = $query->latest()->get();

        $calonPenilai = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with('profile', 'roles')->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        });

        return view('super-admin.skp-dosen.index', compact('periode', 'periodeId', 'headers', 'calonPenilai'));
    }

    public function skpDosenCreate()
    {
        $periode = PeriodeSkp::where('status', 'aktif')->orWhere('status', 'draft')->get();

        // Penilai Akhir: Dekan atau Rektor (Manual)
        $calonPenilaiAkhir = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Rektorat', 'Rektor']);
        })->with('profile', 'roles')->get()->sortBy('name');

        $getRank = function ($user) {
            $role = strtolower($user->getRoleNames()->first() ?? '');
            if (strpos($role, 'rektor') !== false) return 1;
            if (strpos($role, 'dekan') !== false) return 2;
            if (strpos($role, 'kaprodi') !== false || strpos($role, 'kepala') !== false) return 3;
            if (strpos($role, 'sekprodi') !== false) return 4;
            return 5;
        };

        $usersDosen = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            return $user->profile->nama_lengkap ?? $user->name;
        })->sortBy($getRank);

        $calonPenilai = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            // Sort by priority roles: Dekan, Wadek 1, Wadek 2
            $roleNames = strtolower($user->getRoleNames()->implode(','));
            if (strpos($roleNames, 'dekan') !== false && strpos($roleNames, 'wakil') === false) return 1;
            if (strpos($roleNames, 'wakil dekan i') !== false || strpos($roleNames, 'wakil dekan 1') !== false) return 2;
            if (strpos($roleNames, 'wakil dekan ii') !== false || strpos($roleNames, 'wakil dekan 2') !== false) return 3;
            return 10;
        })->values();

        $jsonPegawai = $usersDosen->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? 'Dosen'
        ])->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? 'Dosen',
            'rank' => $getRank($cp)
        ])->values();

        $jsonPenilaiAkhir = $calonPenilaiAkhir->map(fn($cpa) => [
            'id' => $cpa->id,
            'nama' => $cpa->profile->nama_lengkap ?? $cpa->name,
            'nip' => $cpa->profile->nip ?? '-',
            'pangkat' => $cpa->profile->pangkat_golongan ?? '-',
            'jabatan' => $cpa->getRoleNames()->implode(', ') ?? 'Dosen'
        ])->values();

        // --- TAMBAHAN UNTUK TABEL TUPOKSI ---
        $requestedRoles = ['Dekan', 'Wakil Dekan 1', 'Wakil Dekan 2', 'Wadek 1', 'Wadek 2', 'Wakil Dekan I', 'Wakil Dekan II'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
                            ->where(function($q) use ($requestedRoles) {
                                foreach($requestedRoles as $r) {
                                    $q->orWhere('name', 'like', $r . '%');
                                }
                            })
                            ->get();

        $roleBidang = \Spatie\Permission\Models\Role::where('kategori', 'tendik')->pluck('name')->toArray();
        $kamusBidang = \App\Models\DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))->unique()->sort()->values();
        // ------------------------------------

        return view('super-admin.skp-dosen.create', compact('periode', 'usersDosen', 'calonPenilai', 'jsonPegawai', 'jsonPenilai', 'jsonPenilaiAkhir', 'getRank', 'penilaiRoles', 'bidangList'));
    }

    public function skpDosenStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
            'penilai_akhir_id' => 'required|exists:users,id',
            'tupoksi_items' => 'nullable|array',
            'tupoksi_items.*.role_name' => 'nullable|string',
            'tupoksi_items.*.tupoksi' => 'nullable|string',
            'tupoksi_items.*.jenis_aktivitas' => 'nullable|string',
            'tupoksi_items.*.penilai_role' => 'nullable|string',
            'tupoksi_items.*.periode' => 'nullable|string',
            'tupoksi_items.*.frekuensi_per_tahun' => 'nullable|numeric',
            'tupoksi_items.*.durasi_menit' => 'nullable|numeric',
            'tupoksi_items.*.jumlah_sdm' => 'nullable|numeric',
            'kegiatan' => 'required|array|min:1',
            'kegiatan.*.kategori' => 'required|string',
            'kegiatan.*.kegiatan' => 'required|string',
            'kegiatan.*.jenis' => 'nullable|string',
            'tugas_tambahan' => 'nullable|array',
            'tugas_tambahan.*.jenis' => 'nullable|string',
            'tugas_tambahan.*.detail' => 'nullable|string',
            'tugas_tambahan.*.ak' => 'nullable|numeric',
            'perilaku' => 'required|array',
            'perilaku.*.indicators' => 'required|array|min:1',
        ]);

        // Guard duplikat: satu pegawai hanya boleh punya satu header per periode
        $duplikat = \App\Models\SkpHeader::where('periode_skp_id', $request->periode_skp_id)
            ->where('user_id', $request->user_id)
            ->exists();
        if ($duplikat) {
            return back()->withInput()->with('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request) {

                // ✅ 1. SELALU BUAT HEADER BARU (Agar tidak merge jika periode sama)
                // Unik per (periode, pegawai) — guard duplikat & constraint DB di atas.
                $header = \App\Models\SkpHeader::create([
                    'user_id'                   => $request->user_id,
                    'periode_skp_id'            => $request->periode_skp_id,
                    'pejabat_penilai_id'        => $request->pejabat_penilai_id,
                    'penilai_utama_id'          => $request->pejabat_penilai_id,
                    'atasan_pejabat_penilai_id' => $request->penilai_akhir_id,
                    'penilai_akhir_id'          => $request->penilai_akhir_id,
                    'status'                    => 'Draft'
                ]);

                // --- SIMPAN MASTER TUPOKSI (Linked ke Header) ---
                if ($request->has('tupoksi_items')) {
                    foreach ($request->tupoksi_items as $tupoksi) {
                        if (empty($tupoksi['tupoksi'])) continue;

                        \App\Models\MasterSkpTendik::create([
                            'user_id'              => $request->user_id,
                            'skp_header_id'        => $header->id,
                            'role_name'            => $tupoksi['role_name'] ?? '-',
                            'tupoksi'              => $tupoksi['tupoksi'],
                            'jenis_aktivitas'      => $tupoksi['jenis_aktivitas'] ?? '-',
                            'penilai_role'         => $tupoksi['penilai_role'] ?? 'Pejabat Penilai',
                            'kategori'             => $tupoksi['kategori'] ?? [],
                            'periode'              => $tupoksi['periode'] ?? 'tahunan',
                            'frekuensi_per_tahun'  => $tupoksi['frekuensi_per_tahun'] ?? 0,
                            'durasi_menit'         => $tupoksi['durasi_menit'] ?? 0,
                            'jumlah_sdm'           => $tupoksi['jumlah_sdm'] ?? 1,
                            'status_approval'      => 'pending',
                        ]);
                    }
                }

                // --- SIMPAN KEGIATAN DOSEN (Linked ke Header) ---
                foreach ($request->kegiatan as $item) {
                    $hash = md5($request->user_id . $header->id . $item['kategori'] . $item['kegiatan']);
                    $detailKegiatan = null;
                    if (isset($item['detail']) && is_array($item['detail'])) {
                        if ($item['jenis'] === 'melaksanakan_perkuliahan' || $item['jenis'] === 'membina_kemahasiswaan') {
                            $detailKegiatan = array_values($item['detail']);
                        } else {
                            $detailKegiatan = $item['detail'];
                        }
                    }

                    \App\Models\SkpDosen::create([
                        'skp_header_id'   => $header->id,
                        'user_id'         => $request->user_id,
                        'periode_skp_id'  => $request->periode_skp_id,
                        'kegiatan_hash'   => $hash,
                        'kategori'        => $item['kategori'],
                        'kegiatan'        => $item['kegiatan'],
                        'jenis'           => $item['jenis'] ?? null,
                        'detail_kegiatan' => $detailKegiatan,
                        'ak'              => $item['ak'] ?? 0,
                        'kuantitas_output'=> $item['kuantitas_output'] ?? 0,
                        'kualitas_mutu'   => $item['kualitas_mutu'] ?? 100,
                        'waktu'           => $item['waktu'] ?? 6,
                        'biaya'           => $item['biaya'] ?? 0,
                    ]);
                }

                // --- SIMPAN TUGAS TAMBAHAN (DOSEN) (Linked ke Header) ---
                if ($request->has('tugas_tambahan')) {
                    foreach ($request->tugas_tambahan as $tt) {
                        if (empty($tt['jenis']) && empty($tt['detail'])) continue;
                        
                        \App\Models\SkpTugasTambahan::create([
                            'skp_header_id'  => $header->id,
                            'user_id'        => $request->user_id,
                            'periode_skp_id' => $request->periode_skp_id,
                            'jenis'          => $tt['jenis'] ?? 'Tugas Tambahan',
                            'kegiatan'       => $tt['detail'],
                            'ak'             => $tt['ak'] ?? 1,
                        ]);
                    }
                }

                // --- SIMPAN INDIKATOR PERILAKU (Linked ke Header) ---
                foreach ($request->perilaku as $aspek => $data) {
                    if (!isset($data['indicators']) || !is_array($data['indicators'])) continue;
                    foreach ($data['indicators'] as $ind) {
                        if (empty($ind['text'])) continue;
                        \App\Models\SkpPerilakuIndicator::create([
                            'skp_header_id' => $header->id,
                            'aspek'         => $aspek,
                            'indikator'     => $ind['text'],
                            'bobot_persen'  => $ind['bobot_persen'] ?? 0,
                            'is_from_admin' => true
                        ]);
                    }
                }
            });
            return redirect()->route('superadmin.skp-dosen.index')->with('success', 'Target SKP Dosen & Perilaku berhasil disimpan!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Jaring pengaman race condition: constraint unik (periode_skp_id, user_id)
            if ($this->adalahPelanggaranUnikHeader($e)) {
                return back()->withInput()->with('error', 'SKP untuk pegawai ini pada periode tersebut sudah ada. Silakan edit SKP yang sudah ada.');
            }
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Deteksi pelanggaran unique constraint skp_headers_periode_user_unique.
     */
    private function adalahPelanggaranUnikHeader(\Illuminate\Database\QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'skp_headers_periode_user_unique')
            || in_array((int) ($e->errorInfo[1] ?? 0), [1062, 19, 23505], true);
    }

    public function skpDosenEdit($id)
    {
        // $id di sini sekarang adalah header_id
        $header = \App\Models\SkpHeader::with(['pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'user.profile', 'periode'])
                    ->findOrFail($id);
        
        $user_id = $header->user_id;
        $periodeId = $header->periode_skp_id;
        $user = $header->user;
        $selectedPeriode = $header->periode;
        $periodeList = PeriodeSkp::where('status', 'aktif')->orWhere('status', 'draft')->get();

        $penilai = $header->pejabatPenilai;
        $penilaiAkhir = $header->penilaiAkhir;

        $usersDosen = \App\Models\User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get();

        $skpDosens = \App\Models\SkpDosen::where('skp_header_id', $header->id)->get();

        $indicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');

        // Data tambahan untuk Setup di View Edit
        $calonPenilaiAkhir = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Dekan', 'Rektorat', 'Rektor']);
        })->with('profile', 'roles')->get()->sortBy('name');

        $jsonPenilaiAkhir = $calonPenilaiAkhir->map(fn($cpa) => [
            'id' => $cpa->id,
            'nama' => $cpa->profile->nama_lengkap ?? $cpa->name,
            'nip' => $cpa->profile->nip ?? '-',
            'pangkat' => $cpa->profile->pangkat_golongan ?? '-',
            'jabatan' => $cpa->getRoleNames()->implode(', ') ?? 'Dosen'
        ])->values();

        $tugasTambahan = \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)
                            ->get()
                            ->map(fn($t) => [
                                'jenis' => $t->jenis,
                                'detail' => $t->kegiatan,
                                'ak' => $t->ak
                            ]);

        // Format Perilaku Kerja untuk Alpine
        $formattedPerilaku = [];
        $aspekList = ["Orientasi Pelayanan", "Integritas", "Komitmen", "Disiplin", "Kerjasama", "Kepemimpinan"];
        foreach ($aspekList as $aspek) {
            $inds = isset($indicators[$aspek]) ? $indicators[$aspek]->map(fn($ind) => ['text' => $ind->indikator, 'bobot_persen' => round($ind->bobot_persen)])->toArray() : [];
            $formattedPerilaku[] = [
                "name" => $aspek,
                "indicators" => $inds
            ];
        }

        // Format Kegiatan Tridharma untuk Alpine (Key by Jenis)
        $kegiatans_by_jenis = $skpDosens->keyBy(fn($item) => trim($item->jenis))->map(function($item) {
            $detail = $item->detail_kegiatan ?? [];
            if (is_string($detail)) {
                $decoded = json_decode($detail, true);
                if (json_last_error() === JSON_ERROR_NONE) $detail = $decoded;
            }
            return [
                'id' => $item->id,
                'kategori' => $item->kategori,
                'kegiatan' => $item->kegiatan,
                'jenis' => trim($item->jenis),
                'detail' => $detail,
                'ak' => $item->ak
            ];
        });

        // --- TAMBAHAN UNTUK TABEL TUPOKSI ---
        $requestedRoles = ['Dekan', 'Wakil Dekan 1', 'Wakil Dekan 2', 'Wadek 1', 'Wadek 2', 'Wakil Dekan I', 'Wakil Dekan II'];
        $penilaiRoles = \Spatie\Permission\Models\Role::where('kategori', 'utama')
                            ->where(function($q) use ($requestedRoles) {
                                foreach($requestedRoles as $r) {
                                    $q->orWhere('name', 'like', $r . '%');
                                }
                            })
                            ->get();

        $roleBidang = \Spatie\Permission\Models\Role::where('kategori', 'tendik')->pluck('name')->toArray();
        $kamusBidang = \App\Models\DaftarTupoksi::select('bidang')->distinct()->pluck('bidang')->toArray();
        $bidangList = collect(array_merge($roleBidang, $kamusBidang))->unique()->sort()->values();
        // ------------------------------------

        $getRank = function ($user) {
            $role = strtolower($user->getRoleNames()->first() ?? '');
            if (strpos($role, 'rektor') !== false) return 1;
            if (strpos($role, 'dekan') !== false) return 2;
            if (strpos($role, 'kaprodi') !== false || strpos($role, 'kepala') !== false) return 3;
            if (strpos($role, 'sekprodi') !== false) return 4;
            return 5;
        };

        // --- DATA UNTUK MODAL SETUP ---
        $jsonPegawai = collect([$user])->map(fn($u) => [
            'id' => $u->id,
            'nama' => $u->profile->nama_lengkap ?? $u->name,
            'nip' => $u->profile->nip ?? '-',
            'pangkat' => $u->profile->pangkat_golongan ?? '-',
            'jabatan' => $u->getRoleNames()->implode(', ') ?? 'Dosen'
        ])->values();

        $calonPenilai = User::whereHas('roles', function($q) {
            $q->where('kategori', 'utama')->where('name', '!=', 'Super Admin');
        })->with(['profile', 'roles'])->get()->sortBy(function($user) {
            $roleNames = strtolower($user->getRoleNames()->implode(','));
            if (strpos($roleNames, 'dekan') !== false && strpos($roleNames, 'wakil') === false) return 1;
            if (strpos($roleNames, 'wakil dekan i') !== false || strpos($roleNames, 'wakil dekan 1') !== false) return 2;
            if (strpos($roleNames, 'wakil dekan ii') !== false || strpos($roleNames, 'wakil dekan 2') !== false) return 3;
            return 10;
        })->values();

        $jsonPenilai = $calonPenilai->map(fn($cp) => [
            'id' => $cp->id,
            'nama' => $cp->profile->nama_lengkap ?? $cp->name,
            'nip' => $cp->profile->nip ?? '-',
            'pangkat' => $cp->profile->pangkat_golongan ?? '-',
            'jabatan' => $cp->getRoleNames()->implode(', ') ?? 'Dosen',
            'rank' => $getRank($cp)
        ])->values();
        // ---------------------------------------------------

        return view('super-admin.skp-dosen.edit', [
            'user' => $user,
            'periode' => $periodeList,
            'selectedPeriode' => $selectedPeriode,
            'penilai' => $penilai,
            'penilaiAkhir' => $penilaiAkhir,
            'skpDosens' => $skpDosens,
            'skp' => $header,
            'indicators' => $indicators,
            'formattedPerilaku' => $formattedPerilaku,
            'kegiatans_by_jenis' => $kegiatans_by_jenis,
            'jsonPenilaiAkhir' => $jsonPenilaiAkhir,
            'getRank' => $getRank,
            'penilaiRoles' => $penilaiRoles,
            'bidangList' => $bidangList,
            'tugasTambahan' => $tugasTambahan,
            'calonPenilai' => $calonPenilai,
            'jsonPegawai' => $jsonPegawai,
            'jsonPenilai' => $jsonPenilai,
            'usersDosen' => $usersDosen,
            'activities' => \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->get()
        ]);
    }

    public function skpDosenUpdate(Request $request, $id)
    {
        $request->validate([
            'periode_skp_id' => 'required|exists:periode_skp,id',
            'pejabat_penilai_id' => 'required|exists:users,id',
            'penilai_akhir_id' => 'required|exists:users,id',
            'tupoksi_items' => 'nullable|array',
            'tupoksi_items.*.role_name' => 'nullable|string',
            'tupoksi_items.*.tupoksi' => 'nullable|string',
            'tupoksi_items.*.jenis_aktivitas' => 'nullable|string',
            'tupoksi_items.*.penilai_role' => 'nullable|string',
            'tupoksi_items.*.periode' => 'nullable|string',
            'tupoksi_items.*.frekuensi_per_tahun' => 'nullable|numeric',
            'tupoksi_items.*.durasi_menit' => 'nullable|numeric',
            'tupoksi_items.*.jumlah_sdm' => 'nullable|numeric',
            'kegiatan' => 'required|array|min:1',
            'kegiatan.*.kategori' => 'required|string',
            'kegiatan.*.kegiatan' => 'required|string',
            'kegiatan.*.jenis' => 'nullable|string',
            'tugas_tambahan' => 'nullable|array',
            'tugas_tambahan.*.jenis' => 'nullable|string',
            'tugas_tambahan.*.detail' => 'nullable|string',
            'tugas_tambahan.*.ak' => 'nullable|numeric',
            'perilaku' => 'required|array',
            'perilaku.*.indicators' => 'required|array|min:1',
        ]);

        // $id di sini adalah header_id
        $header = \App\Models\SkpHeader::findOrFail($id);
        $user_id = $header->user_id;

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $header, $user_id) {
                // ✅ CEK STATUS (Security Check)
                if ($header->status === 'Disetujui') {
                    throw new \Exception('Gagal: Target SKP tidak dapat diperbarui karena sudah disetujui.');
                }

                $header->update([
                    'pejabat_penilai_id'        => $request->pejabat_penilai_id,
                    'penilai_utama_id'          => $request->pejabat_penilai_id,
                    'atasan_pejabat_penilai_id' => $request->penilai_akhir_id,
                    'penilai_akhir_id'          => $request->penilai_akhir_id,
                    'status'                    => 'Draft'
                ]);

                // --- UPDATE MASTER TUPOKSI (Linked ke Header) ---
                \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->delete();
                if ($request->has('tupoksi_items')) {
                    foreach ($request->tupoksi_items as $tupoksi) {
                        if (empty($tupoksi['tupoksi'])) continue;

                        \App\Models\MasterSkpTendik::create([
                            'user_id'              => $user_id,
                            'skp_header_id'        => $header->id,
                            'role_name'            => $tupoksi['role_name'] ?? '-',
                            'tupoksi'              => $tupoksi['tupoksi'],
                            'jenis_aktivitas'      => $tupoksi['jenis_aktivitas'] ?? '-',
                            'penilai_role'         => $tupoksi['penilai_role'] ?? 'Pejabat Penilai',
                            'kategori'             => $tupoksi['kategori'] ?? [],
                            'periode'              => $tupoksi['periode'] ?? 'tahunan',
                            'frekuensi_per_tahun'  => $tupoksi['frekuensi_per_tahun'] ?? 0,
                            'durasi_menit'         => $tupoksi['durasi_menit'] ?? 0,
                            'jumlah_sdm'           => $tupoksi['jumlah_sdm'] ?? 1,
                            'status_approval'      => 'pending',
                        ]);
                    }
                }

                // --- UPDATE KEGIATAN DOSEN (Linked ke Header) ---
                \App\Models\SkpDosen::where('skp_header_id', $header->id)->delete();
                foreach ($request->kegiatan as $item) {
                    $hash = md5($user_id . $header->id . $item['kategori'] . $item['kegiatan']);
                    $detailKegiatan = null;
                    if (isset($item['detail']) && is_array($item['detail'])) {
                        if ($item['jenis'] === 'melaksanakan_perkuliahan' || $item['jenis'] === 'membina_kemahasiswaan' || $item['jenis'] === 'unsur_penunjang') {
                            $detailKegiatan = array_values($item['detail']);
                        } else {
                            $detailKegiatan = $item['detail'];
                        }
                    }

                    \App\Models\SkpDosen::create([
                        'skp_header_id'   => $header->id,
                        'user_id'         => $user_id,
                        'periode_skp_id'  => $request->periode_skp_id,
                        'kegiatan_hash'   => $hash,
                        'kategori'        => $item['kategori'],
                        'kegiatan'        => $item['kegiatan'],
                        'jenis'           => $item['jenis'] ?? null,
                        'detail_kegiatan' => $detailKegiatan,
                        'ak'              => $item['ak'] ?? 0,
                        'kuantitas_output'=> $item['kuantitas_output'] ?? 0,
                        'kualitas_mutu'   => $item['kualitas_mutu'] ?? 100,
                        'waktu'           => $item['waktu'] ?? 6,
                        'biaya'           => $item['biaya'] ?? 0,
                    ]);
                }

                // --- UPDATE TUGAS TAMBAHAN (Linked ke Header) ---
                \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->delete();
                if ($request->has('tugas_tambahan')) {
                    foreach ($request->tugas_tambahan as $tt) {
                        if (empty($tt['jenis']) && empty($tt['detail'])) continue;
                        
                        \App\Models\SkpTugasTambahan::create([
                            'skp_header_id'  => $header->id,
                            'user_id'        => $user_id,
                            'periode_skp_id' => $request->periode_skp_id,
                            'jenis'          => $tt['jenis'] ?? 'Tugas Tambahan',
                            'kegiatan'       => $tt['detail'],
                            'ak'             => $tt['ak'] ?? 1,
                        ]);
                    }
                }

                // --- UPDATE INDIKATOR PERILAKU (Linked ke Header) ---
                \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();
                foreach ($request->perilaku as $aspek => $data) {
                    if (!isset($data['indicators']) || !is_array($data['indicators'])) continue;
                    foreach ($data['indicators'] as $ind) {
                        if (empty($ind['text'])) continue;
                        \App\Models\SkpPerilakuIndicator::create([
                            'skp_header_id' => $header->id,
                            'aspek'         => $aspek,
                            'indikator'     => $ind['text'],
                            'bobot_persen'  => $ind['bobot_persen'] ?? 0,
                            'is_from_admin' => true
                        ]);
                    }
                }
            });
            return redirect()->route('superadmin.skp-dosen.index', ['periode_id' => $request->periode_skp_id])->with('success', 'Target SKP Dosen & Perilaku berhasil diperbarui!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function skpDosenShow($id)
    {
        // $id di sini sekarang adalah header_id
        $header = \App\Models\SkpHeader::with(['pejabatPenilai.profile', 'atasanPejabatPenilai.profile', 'periode', 'user.profile'])
            ->findOrFail($id);
            
        $user = $header->user;
        $periodeId = $header->periode_skp_id;

        $skpDosens = \App\Models\SkpDosen::where('skp_header_id', $header->id)->get();

        // Ambil Master Activities (Jika ada, untuk monitoring)
        $activities = \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->get();

        $indicators = \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->get()->groupBy('aspek');

        // Ambil Tugas Tambahan
        $tugasTambahan = \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->get();

        return view('super-admin.skp-dosen.show', compact('user', 'skpDosens', 'header', 'indicators', 'activities', 'tugasTambahan'));
    }

    public function skpDosenDestroy($id)
    {
        // $id di sini adalah header_id
        DB::beginTransaction();
        try {
            $header = \App\Models\SkpHeader::findOrFail($id);
            
            // 1. Hapus Daftar Kegiatan Dosen linked to this header
            \App\Models\SkpDosen::where('skp_header_id', $header->id)->delete();

            // 2. Hapus Master Tupoksi (Jika ada yang di-link ke header ini)
            \App\Models\MasterSkpTendik::where('skp_header_id', $header->id)->delete();

            // 3. Hapus Tugas Tambahan linked to this header
            \App\Models\SkpTugasTambahan::where('skp_header_id', $header->id)->delete();

            // 4. Hapus Perilaku & Indikatornya linked to this header
            \App\Models\SkpPerilakuIndicator::where('skp_header_id', $header->id)->delete();

            // 5. Hapus Penilaian Perilaku (Jika ada)
            \App\Models\PenilaianPerilaku::where('user_id', $header->user_id)
                ->where('periode_skp_id', $header->periode_skp_id)
                ->delete();

            // 6. Hapus Header itu sendiri
            $header->delete();

            DB::commit();
            return back()->with('success', 'Data target SKP Dosen berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal hapus data: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PERIODE SKP
    // =========================================================================

    public function periodeSkpIndex()
    {
        $periodeSkp = PeriodeSkp::orderBy('mulai', 'desc')->get();
        return view('super-admin.periode-skp.index', compact('periodeSkp'));
    }

    public function periodeSkpCreate()
    {
        return view('super-admin.periode-skp.create');
    }

    // ✅ VALIDASI UNIK DI STORE
    public function periodeSkpStore(Request $request)
    {
        $this->terapkanDefaultTanggalBulanan($request);
        $this->tambahBatasMaksimal($request);

        $validated = $request->validate([
            'tahun' => [
                'required',
                'numeric',
                Rule::unique('periode_skp')->where(function ($query) use ($request) {
                    return $query->where('mulai', $request->mulai)
                                 ->where('selesai', $request->selesai);
                })
            ],
            'tipe' => 'required|in:tahunan,semester,bulanan',
            'semester' => 'required_if:tipe,semester|nullable|in:ganjil,genap',
            'bulan' => 'required_if:tipe,bulanan|nullable|numeric|in:1,2,3,4',
            'bulan_akhir' => 'nullable|numeric',
            'semester_genap' => 'nullable',
            'semester_gasal' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'required|date|after:mulai',
            'batas_pengisian' => 'nullable|date|after_or_equal:mulai|before_or_equal:batas_maksimal',
            'batas_penilaian' => 'nullable|date|after_or_equal:batas_pengisian|before_or_equal:batas_maksimal',
            'label_triwulan' => 'nullable|string|max:50',
            'label_periode_penilaian' => 'nullable|string|max:100',
            'status' => 'required|in:draft,aktif,selesai'
        ], $this->pesanValidasiPeriode($request));

        if ($conflict = $this->periodeBentrok($validated)) {
            return back()->withInput()->with('error', $conflict);
        }

        DB::beginTransaction();
        try {
            if (($validated['status'] ?? null) === 'aktif') {
                $this->tutupPeriodeLamaJikaSudahLewat($validated['tipe']);
            }

            PeriodeSkp::create($validated);
            DB::commit();
            return redirect()->route('superadmin.periode-skp.index')->with('success', 'Periode SKP baru berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat periode: ' . $e->getMessage());
        }
    }

    public function periodeSkpEdit(PeriodeSkp $periodeSkp)
    {
        return view('super-admin.periode-skp.edit', compact('periodeSkp'));
    }

    // ✅ VALIDASI UNIK DI UPDATE (Ignore Self)
    public function periodeSkpUpdate(Request $request, PeriodeSkp $periodeSkp)
    {
        $this->terapkanDefaultTanggalBulanan($request);
        $this->tambahBatasMaksimal($request);

        $validated = $request->validate([
            'tahun' => [
                'required',
                'numeric',
                Rule::unique('periode_skp')->ignore($periodeSkp->id)->where(function ($query) use ($request) {
                    return $query->where('mulai', $request->mulai)
                                 ->where('selesai', $request->selesai);
                })
            ],
            'tipe' => 'required|in:tahunan,semester,bulanan',
            'semester' => 'required_if:tipe,semester|nullable|in:ganjil,genap',
            'bulan' => 'required_if:tipe,bulanan|nullable|numeric|in:1,2,3,4',
            'bulan_akhir' => 'nullable|numeric',
            'semester_genap' => 'nullable',
            'semester_gasal' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'required|date|after:mulai',
            'batas_pengisian' => 'nullable|date|after_or_equal:mulai|before_or_equal:batas_maksimal',
            'batas_penilaian' => 'nullable|date|after_or_equal:batas_pengisian|before_or_equal:batas_maksimal',
            'label_triwulan' => 'nullable|string|max:50',
            'label_periode_penilaian' => 'nullable|string|max:100',
            'status' => 'required|in:draft,aktif,selesai'
        ], $this->pesanValidasiPeriode());

        // Guard: periode yang sudah dipakai data SKP tidak boleh ubah identitas
        // (tipe/tahun/semester/bulan/tanggal), hanya batas waktu, status, dan label.
        if ($pesan = $this->periodeTerpakai($validated, $periodeSkp)) {
            return back()->withInput()->with('error', $pesan);
        }

        if ($conflict = $this->periodeBentrok($validated, $periodeSkp->id)) {
            return back()->withInput()->with('error', $conflict);
        }

        DB::beginTransaction();
        try {
            if (($validated['status'] ?? null) === 'aktif') {
                $this->tutupPeriodeLamaJikaSudahLewat($validated['tipe'], $periodeSkp->id);
            }

            $periodeSkp->update($validated);
            DB::commit();
            return redirect()->route('superadmin.periode-skp.index')->with('success', 'Periode SKP berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal mengupdate periode: ' . $e->getMessage());
        }
    }

    public function periodeSkpDestroy(PeriodeSkp $periodeSkp)
    {
        $terpakai = array_filter($periodeSkp->jumlahDataTerkait(), fn ($jumlah) => $jumlah > 0);

        if (!empty($terpakai)) {
            $rincian = collect($terpakai)
                ->map(fn ($jumlah, $jenis) => "{$jumlah} {$jenis}")
                ->implode(' dan ');

            return back()->with('error',
                "Periode tidak bisa dihapus karena sudah dipakai {$rincian}. "
                . "Ubah status menjadi Selesai untuk menutup periode."
            );
        }

        $periodeSkp->delete();
        return redirect()->route('superadmin.periode-skp.index')->with('success', 'Dihapus.');
    }

    // Field identitas periode yang terkunci saat periode sudah dipakai data SKP.
    private function periodeTerpakai(array $validated, PeriodeSkp $periodeSkp): ?string
    {
        if (!$periodeSkp->adaDataTerkait()) {
            return null;
        }

        // bulan/bulan_akhir hanya relevan (dan hanya dikirim form) untuk tipe bulanan;
        // untuk semester/tahunan nilainya bisa legacy (mis. 1) dan tidak dikirim form,
        // jadi tidak boleh ikut dibandingkan.
        $kunci = ['tipe', 'tahun', 'semester', 'mulai', 'selesai'];
        if ($periodeSkp->tipe === 'bulanan') {
            $kunci[] = 'bulan';
            $kunci[] = 'bulan_akhir';
        }
        $ubah = [];

        foreach ($kunci as $field) {
            $lama = $periodeSkp->$field;
            $baru = $validated[$field] ?? null;

            // "" dan null dianggap sama (kosong).
            if (in_array($baru, [null, ''], true) && in_array($lama, [null, ''], true)) continue;

            // Bandingkan tanggal secara string (format request = Y-m-d).
            if (in_array($field, ['mulai', 'selesai']) && $lama && $baru) {
                if (\Carbon\Carbon::parse($lama)->toDateString() !== \Carbon\Carbon::parse($baru)->toDateString()) {
                    $ubah[] = $field;
                }
                continue;
            }

            // Angka (tahun, bulan) dibandingkan sebagai int.
            if (is_numeric($lama) && is_numeric($baru)) {
                if ((int) $lama !== (int) $baru) {
                    $ubah[] = $field;
                }
                continue;
            }

            if ((string) $lama !== (string) $baru) {
                $ubah[] = $field;
            }
        }

        if (empty($ubah)) return null;

        $terpakai = collect($periodeSkp->jumlahDataTerkait())->filter(fn ($n) => $n > 0);
        $totalSkp = $terpakai->get('SKP', 0);

        return 'Periode ini sudah dipakai ' . ($totalSkp > 0 ? "{$totalSkp} SKP" : 'data SKP')
            . ', sehingga tipe, tahun, semester, bulan, dan tanggal periode tidak boleh diubah (terkirim berubah: '
            . implode(', ', $ubah) . '). Ubah hanya batas waktu, status, atau label.';
    }

    // Default tanggal mulai=1 & selesai=akhir bulan HANYA untuk tipe 'bulanan',
    // dipakai sebagai fallback saat admin mengosongkan field (bisa di-override manual).
    private function terapkanDefaultTanggalBulanan(Request $request)
    {
        // Default rentang per tipe (isian otomatis, BUKAN aturan wajib —
        // admin bebas mengubah tanggal ke nilai apa pun sesudahnya).
        // Bulanan (triwulan): I 01-01 s.d. 03-31, II 04-01 s.d. 06-30,
        //                     III 07-01 s.d. 09-30, IV 10-01 s.d. 12-31.
        if ($request->tipe === 'bulanan') {
            if (!$request->tahun || !$request->bulan) return;
            $range = PeriodeSkp::rentangTriwulan((int) $request->bulan, (int) $request->tahun);
            if (empty($request->mulai)) {
                $request->merge(['mulai' => $range['mulai']]);
            }
            if (empty($request->selesai)) {
                $request->merge(['selesai' => $range['selesai']]);
            }
            return;
        }

        // Semester (kalender akademik, Tugas 17): Genap = 01-03 s.d. 31-08 tahun sama,
        // Ganjil = 01-09 s.d. 28/29-02 TAHUN BERIKUTNYA (tahun periode = tahun mulai).
        if ($request->tipe === 'semester') {
            if (!$request->tahun || !$request->semester) return;
            if ($request->semester === 'genap') {
                if (empty($request->mulai)) {
                    $request->merge(['mulai' => Carbon::create((int) $request->tahun, 3, 1)->toDateString()]);
                }
                if (empty($request->selesai)) {
                    $request->merge(['selesai' => Carbon::create((int) $request->tahun, 8, 31)->toDateString()]);
                }
            } else {
                if (empty($request->mulai)) {
                    $request->merge(['mulai' => Carbon::create((int) $request->tahun, 9, 1)->toDateString()]);
                }
                if (empty($request->selesai)) {
                    $request->merge(['selesai' => Carbon::create((int) $request->tahun + 1, 2, 1)->endOfMonth()->toDateString()]);
                }
            }
            return;
        }

        // Tahunan: 01-01 s.d. 12-31.
        if ($request->tipe === 'tahunan' && $request->tahun) {
            if (empty($request->mulai)) {
                $request->merge(['mulai' => Carbon::create((int) $request->tahun, 1, 1)->toDateString()]);
            }
            if (empty($request->selesai)) {
                $request->merge(['selesai' => Carbon::create((int) $request->tahun, 12, 31)->toDateString()]);
            }
        }
    }

    // Suntik field virtual 'batas_maksimal' = tanggal_selesai + N hari
    // (config('periode.maks_hari_setelah_selesai'), sinkron dgn PeriodeSkp::MAKS_HARI_SETELAH_SELESAI)
    // agar rule before_or_equal:batas_maksimal bisa dipakai validasi batas pengisian/penilaian.
    private function tambahBatasMaksimal(Request $request): void
    {
        if ($request->filled('selesai')) {
            $maksHari = (int) config('periode.maks_hari_setelah_selesai', PeriodeSkp::MAKS_HARI_SETELAH_SELESAI);
            $request->merge([
                'batas_maksimal' => Carbon::parse($request->selesai)->addDays($maksHari)->toDateString(),
            ]);
        }
    }

    // Pesan validasi Indonesia untuk form periode (dipakai store & update).
    // Saat 'selesai' tidak terkirim (mis. field disabled tanpa hidden), pesan
    // "maksimal 7 hari" jangan ditampilkan karena menyesatkan — cukup minta
    // field selesai diisi ulang.
    private function pesanValidasiPeriode(?Request $request = null): array
    {
        $pesan = [            'required' => ':attribute wajib diisi.',
            'tahun.required' => 'Tahun acuan wajib diisi.',
            'tipe.required' => 'Tipe periode wajib dipilih.',
            'mulai.required' => 'Tanggal mulai wajib diisi.',
            'selesai.required' => 'Tanggal selesai wajib diisi.',
            'status.required' => 'Status periode wajib dipilih.',
            'tahun.unique' => 'Gagal! Terdapat Periode SKP lain yang menggunakan Tahun dan rentang tanggal pelaksanaan yang persis sama.',
            'semester.required' => 'Semester wajib dipilih.',
            'bulan.required' => 'Triwulan wajib dipilih.',
            'bulan.in' => 'Triwulan wajib dipilih salah satu dari: Triwulan I, II, III, atau IV.',
            'selesai.after' => 'Tanggal selesai harus setelah tanggal mulai periode.',
            'batas_pengisian.after_or_equal' => 'Batas akhir pengisian tidak boleh sebelum tanggal mulai periode.',
            'batas_penilaian.after_or_equal' => 'Batas akhir penilaian tidak boleh sebelum batas akhir pengisian.',
        ];

        if ($request && $request->filled('selesai')) {
            $pesan['batas_pengisian.before_or_equal'] = 'Batas akhir pengisian maksimal ' . PeriodeSkp::MAKS_HARI_SETELAH_SELESAI . ' hari setelah tanggal selesai periode.';
            $pesan['batas_penilaian.before_or_equal'] = 'Batas akhir penilaian maksimal ' . PeriodeSkp::MAKS_HARI_SETELAH_SELESAI . ' hari setelah tanggal selesai periode.';
        } else {
            $pesan['batas_pengisian.before_or_equal'] = 'Tanggal selesai periode belum terkirim — isi tanggal mulai dan selesai terlebih dahulu, lalu simpan kembali.';
            $pesan['batas_penilaian.before_or_equal'] = 'Tanggal selesai periode belum terkirim — isi tanggal mulai dan selesai terlebih dahulu, lalu simpan kembali.';
        }

        return $pesan;
    }

    // Cek overlap HANYA antar periode dengan tipe yang SAMA.
    // Overlap = (mulai_baru <= selesai_lain) AND (selesai_baru >= mulai_lain).
    // Kembalikan string pesan error (berisi periode bentrok) atau null kalau aman.
    private function periodeBentrok(array $validated, ?int $ignoreId = null): ?string
    {
        $conflict = PeriodeSkp::where('tipe', $validated['tipe'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('mulai', '<=', $validated['selesai'])
            ->where('selesai', '>=', $validated['mulai'])
            ->first();

        if (!$conflict) return null;

        return "Periode bentrok dengan periode existing #{$conflict->id} ({$conflict->nama_periode}) "
            . "tanggal {$conflict->mulai->format('d M Y')}–{$conflict->selesai->format('d M Y')}. "
            . "Ubah rentang tanggal atau tipe periode agar tidak overlap.";
    }

    // Tutup periode lama (setipe) yang jendela waktunya sudah benar-benar lewat,
    // dipanggil saat periode baru setipe diaktifkan (store/update/update-status).
    // Beda dengan perilaku lama yang menutup paksa SEMUA periode aktif setipe:
    // periode yang masih punya jendela (mis. batas penilaian di masa depan) dibiarkan
    // tetap aktif agar penilai tidak kehilangan jendela penilaian.
    // Update dilakukan PER MODEL (bukan update massal query builder) supaya
    // model event PeriodeSkp::updated jalan → DistribusiRiil::freeze terpicu.
    private function tutupPeriodeLamaJikaSudahLewat(string $tipe, ?int $ignoreId = null): void
    {
        PeriodeSkp::where('status', 'aktif')
            ->where('tipe', $tipe)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get()
            ->each(function (PeriodeSkp $lama) {
                // Inklusif seperti periode:auto-close: hari batas sendiri belum lewat.
                // startOfDay() wajib: batasTutupTerakhir() sudah startOfDay, tanpa ini
                // now() jam 00:00:01 hari batas dianggap lewat (penutupan dini).
                $batas = $lama->batasTutupTerakhir() ?? $lama->selesai;
                if ($batas === null || !now()->startOfDay()->gt($batas->startOfDay())) return;

                $lama->update(['status' => 'selesai']);
            });
    }

    public function periodeSkpUpdateStatus(Request $request, PeriodeSkp $periodeSkp)
    {
        $request->validate(['status' => 'required|in:draft,aktif,selesai']);

        DB::beginTransaction();
        try {
            if ($request->status === 'aktif') {
                $this->tutupPeriodeLamaJikaSudahLewat($periodeSkp->tipe, $periodeSkp->id);
            }

            $periodeSkp->update(['status' => $request->status]);
            DB::commit();

            return back()->with('success', 'Status diubah.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    public function downloadBukti(Request $request)
    {
        $path = $request->query('path');
        if (!$path) abort(404);
        if (!str_starts_with($path, 'skp-tendik/')) abort(403);

        // Cegah path traversal (mis. ?path=skp-tendik/../../.env): setelah
        // resolve realpath, file wajib tetap berada di dalam direktori
        // storage/app/public/skp-tendik.
        $fullPath = storage_path('app/public/' . $path);
        $realFullPath = realpath($fullPath);
        $realBaseDir = realpath(storage_path('app/public/skp-tendik'));
        if ($realFullPath === false || $realBaseDir === false || !str_starts_with($realFullPath, $realBaseDir . DIRECTORY_SEPARATOR)) {
            abort(403);
        }
        return response()->file($realFullPath);
    }

    public function settingsIndex()
    {
        $showSkpDosenKuantitatif = \App\Models\SystemSetting::getBool('show_skp_dosen_kuantitatif', true);
        return view('super-admin.settings.index', compact('showSkpDosenKuantitatif'));
    }

    public function toggleSetting(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|in:show_skp_dosen_kuantitatif',
        ]);

        $current = \App\Models\SystemSetting::getBool($validated['key'], true);
        \App\Models\SystemSetting::setBool($validated['key'], !$current);

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}

