<x-app-layout>
    @section('page-title', 'Detail Realisasi SKP Tendik')

    @section('sidebar')
        @include('partials.sidebar')
    @endsection

    @section('content')
        <div class="p-6 max-w-4xl mx-auto">
            <a href="{{ url()->previous() }}" class="text-blue-600 hover:underline font-bold text-sm mb-4 inline-block">&larr; Kembali</a>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Detail Realisasi: {{ $skp->tupoksi }}</h2>
                <p class="text-sm text-gray-500 mb-6">Pegawai: <span class="font-bold text-gray-700">{{ $skp->user->profile->nama_lengkap ?? $skp->user->name }}</span></p>

                <div class="space-y-6">
                    @forelse($skp->details as $detail)
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <h4 class="font-bold text-gray-700">{{ $loop->iteration }}. {{ $detail->kegiatan }}</h4>
                            <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                                <div>
                                    <p class="font-bold text-gray-500">Target Kuantitas</p>
                                    <p class="font-semibold text-gray-800">{{ $detail->kuantitas_output }}</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-500">Target Kualitas</p>
                                    <p class="font-semibold text-gray-800">{{ $detail->kualitas_mutu }}%</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-500">Target Waktu</p>
                                    <p class="font-semibold text-gray-800">{{ $detail->waktu }} Bulan</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-500">Target Biaya</p>
                                    <p class="font-semibold text-gray-800">Rp {{ number_format($detail->biaya, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 italic text-center">Tidak ada detail kegiatan untuk tupoksi ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endsection
</x-app-layout>
