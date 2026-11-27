<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapping Label Predikat → Kategori Chart
    |--------------------------------------------------------------------------
    |
    | Mapping antara label hasil 9-box matrix (predikat_kinerja) dengan
    | label kategori chart Pola Distribusi (key di distribusiNilai).
    |
    | JANGAN ubah mapping ini tanpa koordinasi, karena dipakai di banyak tempat.
    |
    */
    'predikat_to_chart_map' => [
        'SANGAT BAIK' => 'Istimewa',
        'BAIK' => 'Baik',
        'BUTUH PERBAIKAN' => 'Butuh Perbaikan',
        'KURANG' => 'Kurang/Misconduct',
        'SANGAT KURANG' => 'Sangat Kurang',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Label Chart → Predikat (reverse)
    |--------------------------------------------------------------------------
    */
    'chart_to_predikat_map' => [
        'Istimewa' => 'SANGAT BAIK',
        'Baik' => 'BAIK',
        'Butuh Perbaikan' => 'BUTUH PERBAIKAN',
        'Kurang/Misconduct' => 'KURANG',
        'Sangat Kurang' => 'SANGAT KURANG',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimal jumlah pegawai agar distribusi riil ditampilkan sebagai chart
    |--------------------------------------------------------------------------
    */
    'distribusi_min_pegawai' => 5,
];
