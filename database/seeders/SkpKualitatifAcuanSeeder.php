<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SkpKualitatifAcuanSeeder extends Seeder
{
    public function run(): void
    {
        $utama = [
            [
                'sasaran_kinerja' => 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan, dengan konsentrasi pada peningkatan peran Dosen dalam mengakselerasi jumlah dan kualitas penelitian serta pengabdian masyarakat pada perguruan tinggi di Universitas Muhammadiyah Surabaya (Penugasan dari Dekan/Wakil Rektor/Rektor)',
                'indikator_kinerja' => 'Melaksanakan kegiatan penelitian yang diselesaikan pada tahun berjalan',
                'definisi_operasional' => 'Kemampuan dosen melaksanakan dan menyelesaikan penelitian yang menghasilkan luaran yang diakui dalam BKD',
                'bukti_utama' => 'Artikel ilmiah, HKI, buku, prosiding, atau laporan penelitian yang diakui BKD',
                'target_ekspektasi' => 'Minimal menghasilkan 1 luaran penelitian yang telah terbit, granted, atau dinyatakan selesai pada tahun berjalan dan diakui pada BKD',
                'acuan' => 'Pedoman BKD Kemendiktisaintek; Permendikbud No. 53 Tahun 2023; PermenPANRB No. 6 Tahun 2022'
            ],
            [
                'sasaran_kinerja' => 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan, dengan konsentrasi pada peningkatan peran Dosen dalam mengakselerasi jumlah dan kualitas penelitian serta pengabdian masyarakat pada perguruan tinggi di Universitas Muhammadiyah Surabaya (Penugasan dari Dekan/Wakil Rektor/Rektor)',
                'indikator_kinerja' => 'Melaksanakan kegiatan pengabdian masyarakat yang diselesaikan pada tahun berjalan',
                'definisi_operasional' => 'Kemampuan dosen melaksanakan dan menyelesaikan kegiatan pengabdian yang menghasilkan luaran yang diakui dalam BKD',
                'bukti_utama' => 'Laporan akhir pengabdian, artikel jurnal pengabdian, HKI, buku, media massa, atau luaran lain yang diakui BKD',
                'target_ekspektasi' => 'Minimal melaksanakan 1 kegiatan pengabdian kepada masyarakat yang selesai dan memiliki laporan akhir atau luaran yang diakui BKD pada tahun berjalan',
                'acuan' => 'Pedoman BKD Kemendiktisaintek; Permendikbud No. 53 Tahun 2023; PermenPANRB No. 6 Tahun 2022'
            ],
            [
                'sasaran_kinerja' => 'Peningkatan Kinerja Tridharma oleh Dosen Tetap Yayasan, dengan konsentrasi pada peningkatan peran Dosen dalam mengakselerasi jumlah dan kualitas penelitian serta pengabdian masyarakat pada perguruan tinggi di Universitas Muhammadiyah Surabaya (Penugasan dari Dekan/Wakil Rektor/Rektor)',
                'indikator_kinerja' => 'Melaksanakan kegiatan pengajaran tiap semester pada tahun berjalan',
                'definisi_operasional' => 'Kemampuan dosen melaksanakan pendidikan dan pembelajaran sesuai beban kerja dosen dan standar proses pembelajaran',
                'bukti_utama' => 'BKD, SISTER, BAP Perkuliahan, LMS',
                'target_ekspektasi' => 'Melaksanakan beban kerja pendidikan minimal setara 12 SKS per semester dan memperoleh status BKD Memenuhi (M) pada Semester Ganjil dan Genap',
                'acuan' => 'UU No. 14 Tahun 2005 tentang Guru dan Dosen; Pedoman BKD Kemendiktisaintek; Permendikbud No. 53 Tahun 2023'
            ]
        ];

        $jabatan = [
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Dekan / Wakil Dekan)',
                'definisi_operasional' => 'Kemampuan memimpin, mengelola, mengendalikan, dan mengevaluasi pelaksanaan tridharma, tata kelola, SDM, kemahasiswaan, keuangan, dan sumber daya fakultas sesuai Renstra dan Renop Fakultas',
                'bukti_utama' => 'Laporan Kinerja Fakultas; Renop dan Realisasi Program Kerja; Hasil AMI; Laporan Capaian IKU Fakultas',
                'target_ekspektasi' => '1. Minimal 90% program kerja fakultas terlaksana.\n2. Laporan kinerja fakultas disampaikan tepat waktu sesuai jadwal universitas.\n3. Tidak terdapat temuan mayor AMI yang belum ditindaklanjuti sampai akhir tahun.\n4. Capaian IKU fakultas minimal 80% dari target tahunan.',
                'acuan' => 'PermenPANRB No. 6 Tahun 2022; Permendikbud No. 53 Tahun 2023; SPMI Universitas; Renstra Fakultas'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Kaprodi / Sekprodi)',
                'definisi_operasional' => 'Kemampuan mengelola penyelenggaraan pendidikan, penjaminan mutu akademik, kurikulum, pembelajaran, dan pengembangan program studi',
                'bukti_utama' => 'Laporan Kinerja Prodi; Berita Acara Monev; Laporan AMI; Dokumen Akademik',
                'target_ekspektasi' => '1. Minimal 90% program kerja prodi terlaksana.\n2. Dokumen akademik wajib (kurikulum, RPS, jadwal kuliah, evaluasi pembelajaran) tersedia 100%.\n3. Minimal 80% tindak lanjut hasil AMI dan monev pembelajaran terselesaikan.\n4. Laporan kinerja prodi disampaikan tepat waktu.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; SPMI Universitas; Standar Akademik Universitas'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Kepala Laboratorium)',
                'definisi_operasional' => 'Kemampuan mengelola laboratorium untuk mendukung pembelajaran, penelitian, dan pengabdian secara aman, efektif, dan terdokumentasi',
                'bukti_utama' => 'Laporan Laboratorium; Inventaris Aset; Berita Acara Praktikum; Hasil Audit Laboratorium',
                'target_ekspektasi' => '1. Minimal 90% program kerja laboratorium terlaksana.\n2. Inventaris laboratorium diperbarui minimal 1 kali per tahun.\n3. Tidak terdapat temuan mayor terkait keselamatan kerja atau pengelolaan laboratorium.\n4. Jadwal praktikum terlaksana minimal 90%.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; Standar Laboratorium Universitas; Sistem Manajemen K3'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Kepala Unit Penjaminan Mutu Prodi / Gugus Kendali Mutu Fakultas)',
                'definisi_operasional' => 'Kemampuan melaksanakan siklus PPEPP, audit mutu internal, monitoring dan evaluasi mutu akademik secara berkelanjutan',
                'bukti_utama' => 'Laporan AMI; Daftar Tindak Lanjut Temuan; Dokumen PPEPP',
                'target_ekspektasi' => '1. AMI terlaksana sesuai jadwal universitas.\n2. Laporan hasil AMI diterbitkan tepat waktu.\n3. Minimal 80% temuan audit telah ditindaklanjuti unit terkait.\n4. Dokumen PPEPP tersedia lengkap dan terdokumentasi.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; SPMI Universitas; Pedoman AMI'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Unit Penelitian dan Pengabdian kepada Masyarakat)',
                'definisi_operasional' => 'Kemampuan mengelola, memonitor, dan mendukung pelaksanaan penelitian dan pengabdian di tingkat fakultas/prodi',
                'bukti_utama' => 'Laporan Unit; Berita Acara Monitoring; Database Penelitian dan Pengabdian',
                'target_ekspektasi' => '1. Minimal 90% program kerja unit terlaksana.\n2. Monitoring penelitian dan pengabdian dilaksanakan minimal 2 kali per tahun.\n3. Database penelitian dan pengabdian diperbarui minimal 1 kali per semester.\n4. Laporan kinerja unit disampaikan tepat waktu.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; Standar Penelitian dan Pengabdian; Renstra Fakultas'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Unit Kemahasiswaan)',
                'definisi_operasional' => 'Kemampuan mengelola layanan kemahasiswaan, pembinaan organisasi mahasiswa, prestasi mahasiswa, dan kegiatan non-akademik',
                'bukti_utama' => 'Laporan Kegiatan; Database Prestasi Mahasiswa; Laporan Monitoring',
                'target_ekspektasi' => '1. Minimal 90% program kerja kemahasiswaan terlaksana.\n2. Monitoring kegiatan mahasiswa dilaksanakan minimal 1 kali per semester.\n3. Database prestasi mahasiswa diperbarui minimal 1 kali per semester.\n4. Laporan kegiatan disampaikan tepat waktu.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; Standar Kemahasiswaan Universitas'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Unit Internasional dan Kerja Sama)',
                'definisi_operasional' => 'Kemampuan mengelola kerja sama institusional, internasionalisasi, mobilitas akademik, dan kemitraan yang mendukung tridharma perguruan tinggi',
                'bukti_utama' => 'Laporan Unit; Database Kerja Sama; MoU/MoA/IA; Laporan Capaian Kinerja',
                'target_ekspektasi' => '1. Minimal 90% program kerja unit terlaksana.\n2. Minimal 80% target kinerja unit tercapai sesuai Renop.\n3. Database kerja sama diperbarui minimal 1 kali per semester.\n4. Minimal 80% kerja sama aktif memiliki implementasi kegiatan (IA, joint activity, student exchange, joint research, guest lecture, atau bentuk implementasi lainnya).\n5. Laporan kegiatan dan capaian disampaikan tepat waktu.',
                'acuan' => 'Permendikbud No. 53 Tahun 2023; IKU Perguruan Tinggi; Renstra Universitas; Renop Fakultas'
            ],
            [
                'sasaran_kinerja' => 'Mendorong peningkatan mutu perguruan tinggi melalui peran Dosen Tetap Yayasan yang menduduki jabatan pimpinan perguruan tinggi (Penugasan dari Rektor)',
                'indikator_kinerja' => '(Unit Humas dan Promosi)',
                'definisi_operasional' => 'Kemampuan mengelola komunikasi publik, publikasi institusi, media sosial, branding, dan promosi fakultas/program studi',
                'bukti_utama' => 'Laporan Unit; Rekap Publikasi Media; Rekap Konten Media Sosial; Laporan Capaian Promosi',
                'target_ekspektasi' => '1. Minimal 90% program kerja unit terlaksana.\n2. Minimal 80% target publikasi dan promosi dalam Renop tercapai.\n3. Publikasi kegiatan fakultas/prodi dilakukan minimal 1 kali setiap bulan.\n4. Media sosial resmi unit diperbarui minimal 2 kali setiap bulan.\n5. Laporan kegiatan dan capaian disampaikan tepat waktu.',
                'acuan' => 'PermenPANRB No. 6 Tahun 2022; Renstra Universitas; Renop Fakultas; Pedoman Kehumasan Universitas'
            ]
        ];

        $tambahan = [
            [
                'sasaran_kinerja' => 'Mendukung peningkatan reputasi dan daya saing Universitas Muhammadiyah Surabaya melalui perolehan hibah kompetitif eksternal',
                'indikator_kinerja' => 'Memperoleh pendanaan hibah kompetitif dari sumber eksternal pada tahun berjalan',
                'definisi_operasional' => 'Kemampuan dosen memperoleh pendanaan penelitian, pengabdian, inovasi, atau penguatan kelembagaan dari kompetisi nasional maupun internasional. Tidak termasuk hibah yang telah digunakan sebagai luaran pada Kinerja Utama.',
                'bukti_utama' => 'SK Pendanaan Hibah/Perjanjian Kontrak Pendanaan',
                'target_ekspektasi' => 'Minimal memperoleh 1 hibah kompetitif eksternal yang didanai pada tahun berjalan',
                'acuan' => 'PermenPANRB No. 6 Tahun 2022; Renstra Universitas; IKU Perguruan Tinggi'
            ],
            [
                'sasaran_kinerja' => 'Mendukung peningkatan inovasi dan rekognisi institusi di tingkat nasional maupun internasional',
                'indikator_kinerja' => 'Menghasilkan rekognisi atau inovasi yang memberikan nilai tambah bagi institusi pada tahun berjalan',
                'definisi_operasional' => 'Kemampuan dosen menghasilkan capaian yang melampaui target tridharma wajib. Tidak termasuk luaran penelitian, pengabdian, atau pembelajaran yang telah digunakan untuk memenuhi target Kinerja Utama.',
                'bukti_utama' => 'Sertifikat HKI, Sertifikat Paten, Sertifikat Prestasi Mahasiswa, Surat Keputusan Penghargaan, Berita Acara Implementasi Inovasi',
                'target_ekspektasi' => 'Memenuhi salah satu: (a) memperoleh 1 HKI/paten tercatat atau granted; atau (b) membimbing mahasiswa meraih 1 prestasi tingkat nasional/internasional; atau (c) memperoleh penghargaan profesional tingkat nasional/internasional; atau (d) menghasilkan 1 inovasi yang diimplementasikan di tingkat fakultas/universitas',
                'acuan' => 'PermenPANRB No. 6 Tahun 2022; Permendikbud No. 53 Tahun 2023; IKU Perguruan Tinggi; Renstra Universitas'
            ]
        ];

        $perilaku = [
            [
                'sasaran_kinerja' => 'BERORIENTASI PELAYANAN',
                'indikator_kinerja' => 'Memahami dan memenuhi kebutuhan masyarakat',
                'definisi_operasional' => 'Kemampuan dosen memberikan layanan akademik kepada mahasiswa sesuai standar layanan perguruan tinggi',
                'bukti_utama' => 'Hasil EDOM/Kepuasan Mahasiswa',
                'target_ekspektasi' => 'Nilai kepuasan layanan dosen minimal mencapai standar universitas/fakultas (kepuasan ≥ 3,50 skala 5 atau ≥70 skala 100) pada semester berjalan',
                'acuan' => 'PermenPANRB 6/2022; Permendikbud 53/2023; Standar Pelayanan Akademik Universitas'
            ],
            [
                'sasaran_kinerja' => 'BERORIENTASI PELAYANAN',
                'indikator_kinerja' => 'Ramah, cekatan, solutif, dan dapat diandalkan',
                'definisi_operasional' => 'Kemampuan dosen memberikan layanan akademik tanpa menimbulkan keluhan yang terbukti',
                'bukti_utama' => 'Rekap Pengaduan/Kasus Etik',
                'target_ekspektasi' => 'Tidak terdapat pengaduan layanan akademik atau pengaduan etik yang terbukti selama periode penilaian',
                'acuan' => 'PermenPANRB 6/2022; Kode Etik Dosen; Permendikbud PPKPT'
            ],
            [
                'sasaran_kinerja' => 'BERORIENTASI PELAYANAN',
                'indikator_kinerja' => 'Melakukan perbaikan tiada henti',
                'definisi_operasional' => 'Kemampuan dosen melakukan perbaikan pembelajaran berdasarkan hasil evaluasi',
                'bukti_utama' => 'Bukti tindak lanjut evaluasi pembelajaran/Revisi RPS',
                'target_ekspektasi' => 'Minimal terdapat 1 bukti tindak lanjut hasil evaluasi pembelajaran atau revisi RPS pada tahun berjalan',
                'acuan' => 'Permendikbud 53/2023 (SPMI-PPEPP); PermenPANRB 6/2022'
            ],
            [
                'sasaran_kinerja' => 'AKUNTABEL',
                'indikator_kinerja' => 'Melaksanakan tugas dengan jujur, bertanggung jawab, cermat, disiplin, dan berintegritas tinggi',
                'definisi_operasional' => 'Kemampuan dosen melaksanakan dan mempertanggungjawabkan tugas tridharma sesuai ketentuan',
                'bukti_utama' => 'Surat Bebas Sanksi/Rekap Presensi',
                'target_ekspektasi' => 'Tingkat kehadiran atau pemenuhan jam kerja minimal 80% dan tidak pernah menerima sanksi disiplin tingkat sedang/berat',
                'acuan' => 'Peraturan Kepegawaian Universitas; PermenPANRB 6/2022'
            ],
            [
                'sasaran_kinerja' => 'AKUNTABEL',
                'indikator_kinerja' => 'Menggunakan kekayaan dan BMN/Yayasan secara bertanggung jawab, efektif, dan efisien',
                'definisi_operasional' => 'Kemampuan merawat dan memanfaatkan fasilitas kampus sesuai peruntukannya',
                'bukti_utama' => 'Surat Keterangan Bebas Pinjaman Alat/Buku',
                'target_ekspektasi' => 'Tidak terdapat laporan penyalahgunaan, kerusakan yang disengaja, atau hilangnya aset/fasilitas yang menjadi tanggung jawab dosen',
                'acuan' => 'Standar Sarpras Universitas; Pedoman Pemanfaatan Fasilitas'
            ],
            [
                'sasaran_kinerja' => 'AKUNTABEL',
                'indikator_kinerja' => 'Tidak menyalahgunakan kewenangan jabatan',
                'definisi_operasional' => 'Kepatuhan dosen terhadap nilai-nilai objektivitas akademik',
                'bukti_utama' => 'Rekap Pengaduan/Putusan Komite Etik',
                'target_ekspektasi' => 'Tidak pernah terbukti melakukan jual beli nilai, gratifikasi, atau pelanggaran kewenangan akademik lainnya',
                'acuan' => 'Kode Etik Dosen; Pakta Integritas; UU Tipikor'
            ],
            [
                'sasaran_kinerja' => 'KOMPETEN',
                'indikator_kinerja' => 'Meningkatkan kompetensi diri untuk menjawab tantangan yang selalu berubah',
                'definisi_operasional' => 'Partisipasi dalam kegiatan pengembangan diri',
                'bukti_utama' => 'Sertifikat Pelatihan/Workshop',
                'target_ekspektasi' => 'Mengikuti minimal 1 kegiatan pelatihan, workshop, atau sertifikasi keahlian dalam tahun berjalan yang relevan dengan bidang ilmu',
                'acuan' => 'Renstra SDM Universitas; Pedoman BKD'
            ],
            [
                'sasaran_kinerja' => 'KOMPETEN',
                'indikator_kinerja' => 'Membantu orang lain belajar',
                'definisi_operasional' => 'Kemampuan membimbing rekan sejawat atau mahasiswa di luar kelas (mentoring/coaching)',
                'bukti_utama' => 'Surat Tugas Mentoring/Sertifikat Narasumber Internal',
                'target_ekspektasi' => 'Menjadi narasumber kegiatan internal, mentor sejawat, atau membimbing kelompok studi mahasiswa minimal 1 kali per semester',
                'acuan' => 'PermenPANRB 6/2022; Pedoman Mentoring Universitas'
            ],
            [
                'sasaran_kinerja' => 'KOMPETEN',
                'indikator_kinerja' => 'Melaksanakan tugas dengan kualitas terbaik',
                'definisi_operasional' => 'Kualitas capaian dari tugas yang diembankan',
                'bukti_utama' => 'Bukti Capaian IKU/Prestasi Individu',
                'target_ekspektasi' => 'Menyelesaikan 100% tugas administratif tepat waktu (misal: pengisian nilai, penyusunan BKD, penyusunan RPS)',
                'acuan' => 'Standar Mutu Universitas; Kalender Akademik'
            ],
            [
                'sasaran_kinerja' => 'HARMONIS',
                'indikator_kinerja' => 'Menghargai setiap orang apapun latar belakangnya',
                'definisi_operasional' => 'Penciptaan iklim akademik yang inklusif dan toleran',
                'bukti_utama' => 'Rekap Pengaduan Diskriminasi',
                'target_ekspektasi' => 'Tidak terdapat laporan atau kasus intoleransi/diskriminasi yang terbukti terhadap dosen bersangkutan',
                'acuan' => 'Kode Etik Dosen; Permendikbud No 30 Tahun 2021 (PPKS)'
            ],
            [
                'sasaran_kinerja' => 'HARMONIS',
                'indikator_kinerja' => 'Suka menolong orang lain',
                'definisi_operasional' => 'Sikap kooperatif dosen dalam membantu keperluan institusi atau rekan sejawat',
                'bukti_utama' => 'Surat Tugas Kepanitiaan/Ad-hoc',
                'target_ekspektasi' => 'Bersedia dan aktif berpartisipasi minimal dalam 1 kepanitiaan atau tugas ad-hoc tingkat prodi/fakultas/universitas per tahun',
                'acuan' => 'Pedoman Kepanitiaan Universitas'
            ],
            [
                'sasaran_kinerja' => 'HARMONIS',
                'indikator_kinerja' => 'Membangun lingkungan kerja yang kondusif',
                'definisi_operasional' => 'Kemampuan menjalin komunikasi positif dalam komunitas akademik',
                'bukti_utama' => 'Hasil Penilaian Teman Sejawat',
                'target_ekspektasi' => 'Nilai penilaian teman sejawat dan atasan (360-degree feedback) minimal berada pada kategori "Baik"',
                'acuan' => 'Pedoman Penilaian Kinerja SDM'
            ],
            [
                'sasaran_kinerja' => 'LOYAL',
                'indikator_kinerja' => 'Memegang teguh ideologi Pancasila, UUD 1945, setia pada NKRI, serta Persyarikatan Muhammadiyah',
                'definisi_operasional' => 'Kepatuhan dan keselarasan tindakan dengan ideologi bangsa dan Al-Islam Kemuhammadiyahan (AIK)',
                'bukti_utama' => 'Sertifikat Baitul Arqam / Darul Arqam',
                'target_ekspektasi' => 'Telah mengikuti Baitul Arqam atau kegiatan pembinaan AIK, dan tidak terlibat organisasi terlarang',
                'acuan' => 'Pedoman AIK Universitas; Statuta; UU Pendidikan Tinggi'
            ],
            [
                'sasaran_kinerja' => 'LOYAL',
                'indikator_kinerja' => 'Menjaga nama baik sesama ASN/Pegawai, Pimpinan, Instansi, dan Negara',
                'definisi_operasional' => 'Sikap positif di ruang publik maupun media sosial',
                'bukti_utama' => 'Rekap Sidang Komite Etik/Humas',
                'target_ekspektasi' => 'Tidak terdapat teguran atau sanksi akibat tindakan/pernyataan yang merugikan nama baik institusi di media cetak, elektronik, maupun media sosial',
                'acuan' => 'Kode Etik Pegawai; Pedoman Bermedia Sosial Universitas'
            ],
            [
                'sasaran_kinerja' => 'LOYAL',
                'indikator_kinerja' => 'Menjaga rahasia jabatan dan negara/institusi',
                'definisi_operasional' => 'Kemampuan menjaga kerahasiaan data sensitif (nilai mahasiswa, soal ujian, kerahasiaan riset)',
                'bukti_utama' => 'Pakta Integritas / NDA',
                'target_ekspektasi' => 'Tidak pernah terbukti melakukan kebocoran data akademik atau kerahasiaan institusi yang menjadi kewenangannya',
                'acuan' => 'Standar Pengelolaan Data Universitas; Kode Etik'
            ],
            [
                'sasaran_kinerja' => 'ADAPTIF',
                'indikator_kinerja' => 'Cepat menyesuaikan diri menghadapi perubahan',
                'definisi_operasional' => 'Adaptasi terhadap teknologi atau kebijakan baru',
                'bukti_utama' => 'Bukti Penggunaan Sistem Informasi',
                'target_ekspektasi' => 'Mampu menggunakan platform sistem informasi baru (SISTER, LMS Universitas, Sistem Persuratan) secara mandiri dan tepat waktu dalam tahun pertama implementasinya',
                'acuan' => 'Kebijakan TIK Universitas'
            ],
            [
                'sasaran_kinerja' => 'ADAPTIF',
                'indikator_kinerja' => 'Terus berinovasi dan mengembangkan kreativitas',
                'definisi_operasional' => 'Inovasi dalam pelaksanaan pembelajaran',
                'bukti_utama' => 'Modul Pembelajaran/Media Ajar/RPS Inovatif',
                'target_ekspektasi' => 'Menghasilkan minimal 1 inovasi metode pembelajaran (misal: case method, project-based learning, media ajar interaktif) pada salah satu mata kuliah yang diampu per tahun',
                'acuan' => 'Standar Proses Pembelajaran Universitas; Permendikbud 53/2023'
            ],
            [
                'sasaran_kinerja' => 'ADAPTIF',
                'indikator_kinerja' => 'Bertindak proaktif',
                'definisi_operasional' => 'Keterlibatan aktif memberikan solusi/ide bagi prodi/fakultas',
                'bukti_utama' => 'Notulensi Rapat Prodi/Fakultas',
                'target_ekspektasi' => 'Kehadiran dalam rapat prodi/fakultas/universitas minimal 80% dan aktif memberikan kontribusi pemikiran',
                'acuan' => 'Standar Tata Kelola Universitas'
            ],
            [
                'sasaran_kinerja' => 'KOLABORATIF',
                'indikator_kinerja' => 'Memberi kesempatan kepada berbagai pihak untuk berkontribusi',
                'definisi_operasional' => 'Keterbukaan bermitra dengan sesama dosen atau mahasiswa',
                'bukti_utama' => 'Dokumen Kerjasama Riset/Pengabdian Kolaboratif',
                'target_ekspektasi' => 'Melibatkan minimal 2 mahasiswa atau 1 dosen dari bidang ilmu berbeda dalam kegiatan penelitian atau pengabdian masyarakat per tahun',
                'acuan' => 'Pedoman BKD; IKU Perguruan Tinggi'
            ],
            [
                'sasaran_kinerja' => 'KOLABORATIF',
                'indikator_kinerja' => 'Terbuka dalam bekerja sama untuk menghasilkan nilai tambah',
                'definisi_operasional' => 'Keterlibatan dalam kegiatan lintas program studi atau mitra luar perguruan tinggi',
                'bukti_utama' => 'MoA/IA/Surat Tugas Lintas Prodi',
                'target_ekspektasi' => 'Ikut serta dalam pelaksanaan kegiatan implementasi kerja sama dengan mitra DUDI/Perguruan Tinggi lain minimal 1 kali per tahun',
                'acuan' => 'IKU Perguruan Tinggi; Renstra Universitas'
            ],
            [
                'sasaran_kinerja' => 'KOLABORATIF',
                'indikator_kinerja' => 'Menggerakkan pemanfaatan berbagai sumber daya untuk tujuan bersama',
                'definisi_operasional' => 'Kemampuan berkontribusi dalam tim kerja institusi',
                'bukti_utama' => 'SK Tim/Surat Tugas',
                'target_ekspektasi' => 'Minimal terlibat sebagai anggota atau ketua pada 1 tim kerja resmi tingkat prodi, fakultas, atau universitas dalam tahun berjalan',
                'acuan' => 'Pedoman BKD Unsur Penunjang; PermenPANRB 6/2022'
            ]
        ];

        $processData = function($data, $kategori) {
            $currentParentText = null;
            $currentParentId = null;
            $parentUrutan = 1;
            $childUrutan = 1;

            foreach ($data as $row) {
                if ($row['sasaran_kinerja'] !== $currentParentText) {
                    $currentParentText = $row['sasaran_kinerja'];
                    $parent = \App\Models\SkpKualitatifAcuanParent::create([
                        'kategori' => $kategori,
                        'urutan' => $parentUrutan++,
                        'sasaran_kinerja' => $row['sasaran_kinerja']
                    ]);
                    $currentParentId = $parent->id;
                    $childUrutan = 1;
                }

                \App\Models\SkpKualitatifAcuanChild::create([
                    'parent_id' => $currentParentId,
                    'urutan' => $childUrutan++,
                    'indikator_kinerja' => $row['indikator_kinerja'] ?? '',
                    'definisi_operasional' => $row['definisi_operasional'] ?? '',
                    'bukti_utama' => $row['bukti_utama'] ?? '',
                    'target_ekspektasi' => $row['target_ekspektasi'] ?? '',
                    'acuan' => $row['acuan'] ?? ''
                ]);
            }
        };

        $processData($utama, 'utama');
        $processData($jabatan, 'jabatan');
        $processData($tambahan, 'tambahan');
        $processData($perilaku, 'perilaku');
    }
}
