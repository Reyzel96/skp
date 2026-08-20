<x-app-layout>
    @section('page-title', 'Rumus Hasil SKP Dosen Kualitatif')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6" x-data="{
            jumlah: {{ $jumlahPegawai ?? 0 }},
            dataReal: {{ json_encode($dataReal ?? []) }},
            distribusiNilai: {{ json_encode($distribusiNilai) }},
            distribusiPersen: {{ json_encode($distribusiPersen) }},
            hasilKerja: {{ json_encode($hasilKerja) }},
            isSaving: false,
            showNotification: false,
            notificationMessage: '',
            activeTab: 'rumus',
            
            getNilai(cat, idx) {
                let sangatBaik = Math.round(this.jumlah * this.distribusiPersen[cat][4] / 100);
                if (idx === 4) return sangatBaik;
                
                let baik = Math.round(this.jumlah * this.distribusiPersen[cat][3] / 100);
                if (cat === 'Istimewa') baik = Math.round(sangatBaik * this.distribusiPersen[cat][3] / 100);
                if (idx === 3) return baik;
                
                let butuhPerbaikan = Math.round(this.jumlah * this.distribusiPersen[cat][2] / 100);
                if (cat === 'Istimewa') butuhPerbaikan = Math.round(baik * this.distribusiPersen[cat][2] / 100);
                if (idx === 2) return butuhPerbaikan;

                let kurang = Math.round(this.jumlah * this.distribusiPersen[cat][1] / 100);
                if (cat === 'Istimewa') kurang = Math.round(butuhPerbaikan * this.distribusiPersen[cat][1] / 100);
                if (idx === 1) return kurang;

                if (idx === 0) {
                    if (cat === 'Istimewa') {
                        return Math.round(kurang * this.distribusiPersen[cat][0] / 100);
                    } else {
                        return Math.round(this.jumlah * this.distribusiPersen[cat][0] / 100);
                    }
                }
                
                return Math.round(this.jumlah * this.distribusiPersen[cat][idx] / 100);
            },
            
            init() {
                this.$nextTick(() => {
                    const categories = ['Istimewa', 'Baik', 'Butuh Perbaikan', 'Kurang/Misconduct', 'Sangat Kurang'];
                    const safeCategories = ['Istimewa', 'Baik', 'Butuh Perbaikan', 'Kurang-Misconduct', 'Sangat Kurang'];
                    const labels = ['Sangat Kurang', 'Kurang/Misconduct', 'Butuh Perbaikan', 'Baik', 'Sangat Baik'];
                    
                    categories.forEach((cat, index) => {
                        const canvas = document.getElementById('chart-' + safeCategories[index]);
                        if (canvas) {
                            const ctx = canvas.getContext('2d');
                            window.mySkpCharts[cat] = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: 'Nilai Distribusi',
                                        data: [
                                            this.getNilai(cat, 0),
                                            this.getNilai(cat, 1),
                                            this.getNilai(cat, 2),
                                            this.getNilai(cat, 3),
                                            this.getNilai(cat, 4)
                                        ],
                                        borderColor: '#6366f1',
                                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                                        borderWidth: 2,
                                        tension: 0.4,
                                        fill: true,
                                        pointBackgroundColor: '#4f46e5',
                                        pointRadius: 4,
                                        pointHoverRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return ' Nilai: ' + context.parsed.y;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { precision: 0 }
                                        }
                                    }
                                }
                            });
                        }
                    });
                    
                    this.$watch('distribusiPersen', () => {
                        this.updateDistribusiNilai();
                        this.updateCharts();
                    });
                    this.$watch('jumlah', () => {
                        this.updateDistribusiNilai();
                        this.updateCharts();
                    });
                });
            },
            updateDistribusiNilai() {
                const categories = ['Istimewa', 'Baik', 'Butuh Perbaikan', 'Kurang/Misconduct', 'Sangat Kurang'];
                categories.forEach(cat => {
                    for(let i = 0; i < 5; i++) {
                        this.distribusiNilai[cat][i] = this.getNilai(cat, i);
                    }
                });
            },
            updateCharts() {
                const categories = ['Istimewa', 'Baik', 'Butuh Perbaikan', 'Kurang/Misconduct', 'Sangat Kurang'];
                categories.forEach(cat => {
                    if (window.mySkpCharts && window.mySkpCharts[cat]) {
                        window.mySkpCharts[cat].data.datasets[0].data = [
                            this.getNilai(cat, 0),
                            this.getNilai(cat, 1),
                            this.getNilai(cat, 2),
                            this.getNilai(cat, 3),
                            this.getNilai(cat, 4)
                        ];
                        window.mySkpCharts[cat].update();
                    }
                });
            },
            saveData() {
                this.isSaving = true;
                
                const categories = ['Istimewa', 'Baik', 'Butuh Perbaikan', 'Kurang/Misconduct', 'Sangat Kurang'];
                categories.forEach(cat => {
                    for(let i = 0; i < 5; i++) {
                        this.distribusiNilai[cat][i] = this.getNilai(cat, i);
                    }
                });

                fetch('{{ route('superadmin.skp-dosen-kualitatif.rumus.update-pegawai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        distribusi_nilai: this.distribusiNilai,
                        distribusi_persen: this.distribusiPersen,
                        hasil_kerja: this.hasilKerja
                    })
                }).then(res => res.json())
                  .then(data => {
                      this.isSaving = false;
                      this.notificationMessage = data.message || 'Data berhasil disimpan!';
                      this.showNotification = true;
                      setTimeout(() => { this.showNotification = false; }, 3000);
                  }).catch(() => this.isSaving = false);
            }
        }">
            
            <!-- Toast Notification -->
            <div x-show="showNotification" x-transition.opacity style="display: none;"
                 class="fixed top-5 right-5 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50 flex items-center gap-3">
                <i class="fas fa-check-circle"></i>
                <span x-text="notificationMessage"></span>
            </div>
            {{-- HEADER --}}
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Rumus Hasil SKP Dosen Kualitatif</h1>
                    <p class="text-sm text-gray-500 mt-1">Pola Distribusi Penilaian Kinerja</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="saveData()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('superadmin.skp-dosen-kualitatif.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-bold rounded-lg shadow-md transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>

            {{-- TABS --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="#" @click.prevent="activeTab = 'rumus'" 
                       :class="activeTab === 'rumus' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" 
                       class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm tracking-wide transition-colors">
                        Rumus & Kurva
                    </a>
                    <a href="#" @click.prevent="activeTab = 'pd'" 
                       :class="activeTab === 'pd' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" 
                       class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm tracking-wide transition-colors">
                        Pola Distribusi (PD)
                    </a>
                </nav>
            </div>

            {{-- TAB: RUMUS --}}
            <div x-show="activeTab === 'rumus'" x-transition.opacity>

            <!-- Header Input -->
            <div class="bg-indigo-900 rounded-xl shadow-lg border border-indigo-800 p-6 flex flex-col sm:flex-row justify-between items-center text-white mb-8">
                <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                    <div class="p-3 bg-white/10 rounded-lg">
                        <i class="fas fa-users text-2xl text-indigo-300"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-indigo-200 uppercase tracking-wider">Universitas Muhammadiyah Surabaya</h3>
                        <p class="text-lg font-bold">JUMLAH PEGAWAI</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span x-show="isSaving" class="text-indigo-200 text-sm animate-pulse">Menyimpan...</span>
                    <div class="text-2xl sm:text-4xl font-extrabold text-indigo-900 bg-white rounded-lg py-2 px-4 w-32 sm:w-48 text-center shadow-inner border-2 border-indigo-300">
                        <span x-text="jumlah"></span>
                    </div>
                </div>
            </div>


            <style>
                .premium-table th {
                    background-color: #f8fafc;
                    color: #475569;
                    font-weight: 700;
                    text-transform: uppercase;
                    font-size: 0.75rem;
                    letter-spacing: 0.05em;
                }
                .premium-table td {
                    color: #334155;
                }
                .premium-table tr:hover td {
                    background-color: #f1f5f9;
                }
                .input-field {
                    width: 5rem;
                    text-align: right;
                    border: 1px solid #cbd5e1;
                    border-radius: 0.375rem;
                    padding: 0.25rem 0.5rem;
                    font-weight: 600;
                    transition: all 0.2s;
                }
                .input-percentage {
                    width: 4rem;
                }
                .input-field:focus {
                    outline: none;
                    border-color: #6366f1;
                    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
                }
            </style>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>window.mySkpCharts = {};</script>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Istimewa -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Istimewa</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200">Kategori</th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-center" colspan="2">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Kurang</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Istimewa'][0]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Istimewa'][0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Kurang/Misconduct</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Istimewa'][1]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Istimewa'][1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Butuh Perbaikan</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Istimewa'][2]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Istimewa'][2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Istimewa'][3]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Istimewa'][3]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Istimewa'][4]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Istimewa'][4]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="bg-blue-50 font-bold"><td class="px-6 py-3">Jumlah</td><td class="px-6 py-3 text-right pr-9" x-text="jumlah">0</td><td class="px-6 py-3 text-right pr-10" x-text="distribusiPersen['Istimewa'].reduce((a,b) => a + b, 0) + '%'"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><div class="relative h-48 w-full"><canvas id="chart-Istimewa"></canvas></div></div>
                </div>

                <!-- Baik -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-emerald-600 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Baik</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200">Kategori</th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-center" colspan="2">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Kurang</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Baik'][0]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Baik'][0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Kurang/Misconduct</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Baik'][1]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Baik'][1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Butuh Perbaikan</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Baik'][2]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Baik'][2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Baik'][3]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Baik'][3]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Baik'][4]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Baik'][4]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="bg-emerald-50 font-bold"><td class="px-6 py-3">Jumlah</td><td class="px-6 py-3 text-right pr-9" x-text="jumlah">0</td><td class="px-6 py-3 text-right pr-10" x-text="distribusiPersen['Baik'].reduce((a,b) => a + b, 0) + '%'"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><div class="relative h-48 w-full"><canvas id="chart-Baik"></canvas></div></div>
                </div>

                <!-- Butuh Perbaikan -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-amber-500 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Butuh Perbaikan</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200">Kategori</th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-center" colspan="2">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Kurang</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Butuh Perbaikan'][0]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Butuh Perbaikan'][0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Kurang/Misconduct</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Butuh Perbaikan'][1]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Butuh Perbaikan'][1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Butuh Perbaikan</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Butuh Perbaikan'][2]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Butuh Perbaikan'][2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Butuh Perbaikan'][3]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Butuh Perbaikan'][3]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Butuh Perbaikan'][4]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Butuh Perbaikan'][4]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="bg-amber-50 font-bold"><td class="px-6 py-3">Jumlah</td><td class="px-6 py-3 text-right pr-9" x-text="jumlah">0</td><td class="px-6 py-3 text-right pr-10" x-text="distribusiPersen['Butuh Perbaikan'].reduce((a,b) => a + b, 0) + '%'"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><div class="relative h-48 w-full"><canvas id="chart-Butuh Perbaikan"></canvas></div></div>
                </div>

                <!-- Kurang/Misconduct -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-orange-500 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Kurang / Misconduct</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200">Kategori</th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-center" colspan="2">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Kurang</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Kurang/Misconduct'][0]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Kurang/Misconduct'][0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Kurang/Misconduct</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Kurang/Misconduct'][1]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Kurang/Misconduct'][1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Butuh Perbaikan</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Kurang/Misconduct'][2]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Kurang/Misconduct'][2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Kurang/Misconduct'][3]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Kurang/Misconduct'][3]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Kurang/Misconduct'][4]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Kurang/Misconduct'][4]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="bg-orange-50 font-bold"><td class="px-6 py-3">Jumlah</td><td class="px-6 py-3 text-right pr-9" x-text="jumlah">0</td><td class="px-6 py-3 text-right pr-10" x-text="distribusiPersen['Kurang/Misconduct'].reduce((a,b) => a + b, 0) + '%'"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><div class="relative h-48 w-full"><canvas id="chart-Kurang-Misconduct"></canvas></div></div>
                </div>

                <!-- Sangat Kurang -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:col-span-2">
                    <div class="bg-red-600 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Sangat Kurang</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200">Kategori</th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-center" colspan="2">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Kurang</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Sangat Kurang'][0]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Sangat Kurang'][0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Kurang/Misconduct</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Sangat Kurang'][1]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Sangat Kurang'][1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Butuh Perbaikan</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Sangat Kurang'][2]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Sangat Kurang'][2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Sangat Kurang'][3]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Sangat Kurang'][3]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="border-b border-gray-100"><td class="px-6 py-3">Sangat Baik</td><td class="px-6 py-3 text-right pr-9" x-text="distribusiNilai['Sangat Kurang'][4]">0</td><td class="px-6 py-3 font-semibold text-right w-24"><input type="number" class="input-field input-percentage" x-model.number="distribusiPersen['Sangat Kurang'][4]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"> %</td></tr>
                                <tr class="bg-red-50 font-bold"><td class="px-6 py-3">Jumlah</td><td class="px-6 py-3 text-right pr-9" x-text="jumlah">0</td><td class="px-6 py-3 text-right pr-10" x-text="distribusiPersen['Sangat Kurang'].reduce((a,b) => a + b, 0) + '%'"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50"><div class="relative h-48 w-full"><canvas id="chart-Sangat Kurang"></canvas></div></div>
                </div>
            </div>
            </div> {{-- END TAB RUMUS --}}

            {{-- TAB: PD --}}
            <div x-show="activeTab === 'pd'" x-cloak x-transition.opacity>
                {{-- SECTION 3: PARAMETER HASIL KERJA --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full max-w-sm mb-8">
                    <div class="bg-purple-600 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">Parameter Hasil Kerja</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left premium-table">
                            <tbody>
                                <tr class="border-b border-gray-100">
                                    <td class="px-6 py-3 font-semibold">DIATAS EKSPEKTASI</td>
                                    <td class="px-6 py-3 text-center w-24"><input type="number" class="input-field w-16 text-center" x-model.number="hasilKerja[0]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-6 py-3 font-semibold">SESUAI EKSPEKTASI</td>
                                    <td class="px-6 py-3 text-center w-24"><input type="number" class="input-field w-16 text-center" x-model.number="hasilKerja[1]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-6 py-3 font-semibold">DIBAWAH EKSPEKTASI</td>
                                    <td class="px-6 py-3 text-center w-24"><input type="number" class="input-field w-16 text-center" x-model.number="hasilKerja[2]" @input="updateDistribusiNilai(); updateCharts()" @change="saveData" @keyup.enter="saveData"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- SECTION 2: MATRIX PD --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8 w-full">
                    <div class="bg-slate-700 px-6 py-4">
                        <h2 class="text-lg font-bold text-white tracking-wide">POLA DISTRIBUSI KESELURUHAN</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-center premium-table">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-xs bg-blue-50 text-blue-800">Istimewa</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-xs bg-emerald-50 text-emerald-800">Baik</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-xs bg-amber-50 text-amber-800">Butuh Perbaikan</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-xs bg-orange-50 text-orange-800">Kurang/Misconduct</th>
                                    <th class="px-2 py-2 border-b border-gray-200 bg-red-50 text-red-800 text-xs">Sangat Kurang</th>
                                </tr>
                                <tr>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-[10px] uppercase">Pola Distribusi</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-[10px] uppercase">Pola Distribusi</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-[10px] uppercase">Pola Distribusi</th>
                                    <th class="px-2 py-2 border-b border-gray-200 border-r border-gray-100 text-[10px] uppercase">Pola Distribusi</th>
                                    <th class="px-2 py-2 border-b border-gray-200 text-[10px] uppercase">Pola Distribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-100">
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Istimewa'][0]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Baik'][0]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Butuh Perbaikan'][0]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Kurang/Misconduct'][0]"></td>
                                    <td class="px-2 py-2 text-xs" x-text="distribusiNilai['Sangat Kurang'][0]"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Istimewa'][1]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Baik'][1]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Butuh Perbaikan'][1]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Kurang/Misconduct'][1]"></td>
                                    <td class="px-2 py-2 text-xs" x-text="distribusiNilai['Sangat Kurang'][1]"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Istimewa'][2]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Baik'][2]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Butuh Perbaikan'][2]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Kurang/Misconduct'][2]"></td>
                                    <td class="px-2 py-2 text-xs" x-text="distribusiNilai['Sangat Kurang'][2]"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Istimewa'][3]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Baik'][3]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Butuh Perbaikan'][3]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Kurang/Misconduct'][3]"></td>
                                    <td class="px-2 py-2 text-xs" x-text="distribusiNilai['Sangat Kurang'][3]"></td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Istimewa'][4]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Baik'][4]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Butuh Perbaikan'][4]"></td>
                                    <td class="px-2 py-2 border-r border-gray-100 text-xs" x-text="distribusiNilai['Kurang/Misconduct'][4]"></td>
                                    <td class="px-2 py-2 text-xs" x-text="distribusiNilai['Sangat Kurang'][4]"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                </div>
                
                </div>

            </div>


        </div>
    @endsection
</x-app-layout>
