<x-app-layout>
    @section('page-title', 'Dashboard Kepala ' . $data['upf_name'])

@section('sidebar')
    @include('partials.sidebar')
@endsection

    @section('content')
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $user->profile->nama_lengkap ?? $user->name }}</h2>
                    <p class="text-gray-600 mt-2 text-lg">Kepala Unit Pelaksana Fakultas (UPF) <span class="font-bold text-blue-600">{{ $data['upf_name'] }}</span></p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-4 py-2 bg-blue-50 text-blue-700 rounded-lg font-medium border border-blue-100">
                        NIP: {{ $user->profile->nip ?? '-' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Kegiatan Aktif</h3>
                <p class="text-3xl font-black text-gray-800 mt-2">{{ $data['total_kegiatan'] }} <span class="text-sm font-medium text-gray-400">Program</span></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Serapan Anggaran</h3>
                <p class="text-3xl font-black text-gray-800 mt-2">{{ $data['anggaran_terserap'] }}<span class="text-xl">%</span></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-orange-500">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Dokumen Perlu TTD</h3>
                <div class="flex items-center mt-2">
                    <p class="text-3xl font-black text-gray-800">{{ $data['dokumen_pending'] }}</p>
                    @if($data['dokumen_pending'] > 0)
                        <span class="ml-3 px-2 py-1 text-xs font-bold bg-red-100 text-red-600 rounded-full">Urgent</span>
                    @endif
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
