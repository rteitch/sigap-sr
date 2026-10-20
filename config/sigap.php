<?php

return [
    'app_name' => 'SIGAP-SR',
    'app_version' => env('APP_VERSION', '1.0.0'),
    'tagline' => 'Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat',
    'license' => 'MIT',

    'developer' => [
        'name' => 'Rizal Taufiq Hidayat',
        'role' => 'Developer & Maintainer',
        'github' => 'https://github.com/rteitch',
        'repository' => 'https://github.com/rteitch/sigap-sr',
    ],

    'branding' => [
        'app_name' => 'SIGAP-SR',
        'tagline' => 'Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat',
        'primary_color' => '#0f766e',
    ],

    'demo' => [
        'enabled' => env('APP_ENV', 'production') === 'local' && (bool) env('SIGAP_SHOW_DEMO_ACCOUNTS', true),
        'password' => 'password',
        'accounts' => [
            ['label' => 'Kepala sekolah', 'email' => 'kepsek@sigap.test'],
            ['label' => 'TPPK', 'email' => 'tppk@sigap.test'],
            ['label' => 'Guru BK', 'email' => 'bk@sigap.test'],
            ['label' => 'Wali kelas', 'email' => 'walikelas@sigap.test'],
            ['label' => 'Wali asrama', 'email' => 'waliasrama@sigap.test'],
            ['label' => 'Administrator', 'email' => 'admin@sigap.test'],
        ],
    ],

    'api' => [
        'key' => env('SIGAP_API_KEY'),
        'school_id' => env('SIGAP_API_SCHOOL_ID'),
    ],

    'health' => [
        'heartbeat_ttl_seconds' => (int) env('SIGAP_HEARTBEAT_TTL', 180),
        'metrics_token' => env('SIGAP_METRICS_TOKEN'),
    ],

    'telemetry' => [
        'enabled' => (bool) env('SIGAP_TELEMETRY_ENABLED', true),
    ],

    'synthetic' => [
        'enabled' => (bool) env('SIGAP_SYNTHETIC_ENABLED', false),
        'url' => env('SIGAP_SYNTHETIC_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/health/ready'),
        'timeout_seconds' => (int) env('SIGAP_SYNTHETIC_TIMEOUT', 10),
    ],

    'roles' => [
        'admin' => 'Administrator',
        'kepala_sekolah' => 'Kepala Sekolah',
        'tppk' => 'TPPK',
        'bk' => 'Guru BK',
        'wali_kelas' => 'Wali Kelas',
        'wali_asrama' => 'Wali Asrama',
        'guru' => 'Guru',
    ],

    'role_descriptions' => [
        'admin' => 'Mengelola data induk, pengguna, dan konfigurasi. Tidak membuka kasus sangat rahasia.',
        'kepala_sekolah' => 'Gambaran menyeluruh, keputusan akhir, dan pelaporan berjenjang.',
        'tppk' => 'Menerima dan menangani kasus, memastikan pemulihan korban dan kepatuhan.',
        'bk' => 'Asesmen, pendampingan, dan catatan konseling yang terbatas.',
        'wali_kelas' => 'Presensi kelas, mencatat dan merujuk insiden siswa di kelasnya.',
        'wali_asrama' => 'Presensi kegiatan asrama, ronde, logbook piket, dan serah terima shift.',
        'guru' => 'Presensi dan pencatatan insiden di kelas yang diampu.',
    ],

    'genders' => [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ],

    'student_statuses' => [
        'calon' => 'Calon',
        'aktif' => 'Aktif',
        'cuti' => 'Cuti',
        'pindah' => 'Pindah',
        'mundur' => 'Mundur',
        'lulus' => 'Lulus',
    ],

    'levels' => [
        'SD' => 'SD',
        'SMP' => 'SMP',
        'SMA' => 'SMA',
    ],

    'attendance_statuses' => [
        'hadir' => 'Hadir',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpa' => 'Alpa',
        'dispensasi' => 'Dispensasi',
        'pulang_berizin' => 'Pulang berizin',
    ],

    'attendance_types' => [
        'kelas' => 'Presensi kelas',
        'apel_pagi' => 'Apel pagi',
        'makan' => 'Makan',
        'apel_malam' => 'Apel malam',
        'tidur' => 'Tidur',
    ],

    'dorm_attendance_types' => ['apel_pagi', 'makan', 'apel_malam', 'tidur'],

    'incident_statuses' => [
        'baru' => 'Baru',
        'ditinjau' => 'Ditinjau',
        'ditangani' => 'Ditangani',
        'menunggu_rujukan' => 'Menunggu rujukan',
        'selesai' => 'Selesai',
        'ditinjau_ulang' => 'Ditinjau ulang',
    ],

    'incident_severities' => [
        1 => 'Tingkat 1 · Ringan',
        2 => 'Tingkat 2 · Sedang',
        3 => 'Tingkat 3 · Berat',
        4 => 'Tingkat 4 · Sangat berat',
    ],

    'severity_descriptions' => [
        1 => 'Gangguan kecil, dapat diselesaikan dengan pengingat dan pembinaan singkat.',
        2 => 'Berulang atau berdampak pada siswa lain, perlu pembinaan terjadwal.',
        3 => 'Berisiko pada keselamatan atau pemulihan korban, kepala sekolah harus segera tahu.',
        4 => 'Krisis atau dugaan pidana. Keselamatan dan layanan medis lebih dulu.',
    ],

    'confidentialities' => [
        'normal' => 'Normal',
        'rahasia' => 'Rahasia',
        'sangat_rahasia' => 'Sangat rahasia',
    ],

    'incident_roles' => [
        'korban' => 'Korban',
        'pelaku' => 'Pelaku',
        'saksi' => 'Saksi',
        'pelapor' => 'Pelapor',
    ],

    'locations' => [
        'Kelas',
        'Koridor sekolah',
        'Halaman sekolah',
        'Kantin',
        'Asrama',
        'Kamar asrama',
        'Kamar mandi',
        'Ruang makan',
        'Ruang BK',
        'Luar sekolah',
        'Lainnya',
    ],

    'sanctions' => [
        'teguran_lisan' => 'Teguran lisan dan pengingat',
        'mediasi_restoratif' => 'Mediasi restoratif bersama siswa',
        'tugas_edukatif' => 'Tugas edukatif dan refleksi',
        'konseling' => 'Pendampingan konseling',
        'pembinaan_mentor' => 'Pembinaan mentor Lapis 2',
        'pemulihan_korban' => 'Rencana pemulihan korban',
        'skorsing_terbatas' => 'Pemisahan kegiatan terbatas di dalam sekolah',
    ],

    'dorm_shifts' => [
        'pagi' => 'Shift pagi',
        'siang' => 'Shift siang',
        'malam' => 'Shift malam',
    ],

    'dorm_checkpoints' => [
        'Gerbang asrama',
        'Koridor blok',
        'Kamar tidur',
        'Kamar mandi',
        'Ruang makan',
        'Pos jaga',
        'Halaman dan pagar belakang',
    ],

    'positive_words' => ['nakal', 'bandel', 'malas', 'pemalas', 'pembohong', 'biang kerok'],

    'small_group_threshold' => 5,
    'response_due_hours' => [1 => 72, 2 => 48, 3 => 24, 4 => 24],
    'missing_student_escalation_minutes' => 120,
];
