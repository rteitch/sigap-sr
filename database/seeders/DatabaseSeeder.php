<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\DormLog;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! filter_var(env('SIGAP_ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('Seeder demo dilewati di production. Set SIGAP_ALLOW_DEMO_SEED=true hanya untuk lingkungan uji.');

            return;
        }

        $school = School::create([
            'name' => 'Sekolah Rakyat Nusantara',
            'npsn' => '10293847',
            'level' => 'SMP',
            'address' => 'Jl. Pendidikan Rakyat No. 12, Kecamatan Cibiru, Kota Bandung',
            'phone' => '022-7800123',
            'settings' => [
                'small_group_threshold' => 5,
                'missing_student_escalation_minutes' => 120,
                'academic_year' => '2026/2027',
                'app_name' => 'SIGAP-SR',
                'tagline' => 'Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat',
                'primary_color' => '#0f766e',
            ],
        ]);

        $levels = $this->seedLevels($school);
        $academicYear = $this->seedAcademicYears($school);
        $this->seedLocations($school);
        $users = $this->seedUsers($school);
        $classrooms = $this->seedClassrooms($school, $users, $levels, $academicYear);
        $dorms = $this->seedDorms($school, $users);
        $students = $this->seedStudents($school, $classrooms, $dorms, $users);
        $categories = $this->seedCategories($school);

        $this->seedAttendance($school, $classrooms, $dorms, $students, $users);
        $this->seedIncidents($school, $users, $students, $categories);
        $this->seedDormLogs($school, $dorms, $users);

        $this->command?->info('SIGAP-SR siap. Login demo: kepsek@sigap.test / password');
    }

    private function seedLevels(School $school): array
    {
        $levels = [];
        foreach (array_keys(config('sigap.levels')) as $index => $code) {
            $levels[$code] = Level::create([
                'school_id' => $school->id,
                'code' => $code,
                'name' => $code,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        return $levels;
    }

    private function seedAcademicYears(School $school): AcademicYear
    {
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);

        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    private function seedLocations(School $school): void
    {
        foreach (array_values(config('sigap.locations')) as $index => $name) {
            IncidentLocation::create([
                'school_id' => $school->id,
                'name' => $name,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    private function seedUsers(School $school): array
    {
        $definitions = [
            'admin' => ['Nadia Prameswari', 'admin@sigap.test', '0812-1000-0001'],
            'kepala_sekolah' => ['Dani Saputra', 'kepsek@sigap.test', '0812-1000-0002'],
            'tppk' => ['Ratna Kusuma', 'tppk@sigap.test', '0812-1000-0003'],
            'bk' => ['Sari Wulandari', 'bk@sigap.test', '0812-1000-0004'],
            'wali_kelas' => ['Agus Prasetyo', 'walikelas@sigap.test', '0812-1000-0005'],
            'wali_asrama' => ['Rina Marlina', 'waliasrama@sigap.test', '0812-1000-0006'],
            'guru' => ['Fajar Nugroho', 'guru@sigap.test', '0812-1000-0007'],
        ];

        $users = [];
        foreach ($definitions as $role => [$name, $email, $phone]) {
            $users[$role] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => $role,
                'school_id' => $school->id,
                'is_active' => true,
                'phone' => $phone,
            ]);
        }

        $users['wali_asrama_putra'] = User::create([
            'name' => 'Bayu Santoso',
            'email' => 'waliasrama2@sigap.test',
            'password' => Hash::make('password'),
            'role' => 'wali_asrama',
            'school_id' => $school->id,
            'is_active' => true,
            'phone' => '0812-1000-0008',
        ]);

        return $users;
    }

    private function seedClassrooms(School $school, array $users, array $levels, AcademicYear $academicYear): array
    {
        $definitions = [
            '7A' => ['SMP', $users['wali_kelas']],
            '7B' => ['SMP', $users['guru']],
            '8A' => ['SMP', $users['wali_kelas']],
            '8B' => ['SMP', $users['guru']],
            '10A' => ['SMA', $users['wali_kelas']],
            '11A' => ['SMA', $users['guru']],
        ];

        $classrooms = [];
        foreach ($definitions as $name => [$level, $teacher]) {
            $classrooms[$name] = Classroom::create([
                'school_id' => $school->id,
                'name' => $name,
                'level' => $level,
                'level_id' => $levels[$level]->id,
                'homeroom_teacher_id' => $teacher->id,
                'academic_year' => $academicYear->name,
                'academic_year_id' => $academicYear->id,
                'is_active' => true,
            ]);
        }

        return $classrooms;
    }

    private function seedDorms(School $school, array $users): array
    {
        $definitions = [
            'Melati' => ['P', $users['wali_asrama'], 24],
            'Anggrek' => ['P', $users['wali_asrama'], 24],
            'Cempaka' => ['L', $users['wali_asrama_putra'], 24],
            'Mawar' => ['L', $users['wali_asrama_putra'], 24],
        ];

        $dorms = [];
        foreach ($definitions as $name => [$gender, $wali, $capacity]) {
            $dorms[$name] = Dorm::create([
                'school_id' => $school->id,
                'name' => $name,
                'gender' => $gender,
                'wali_id' => $wali->id,
                'capacity' => $capacity,
            ]);
        }

        return $dorms;
    }

    private function seedStudents(School $school, array $classrooms, array $dorms, array $users): array
    {
        $names = [
            ['Aisyah Putri Ramadhani', 'P'], ['Bima Arya Pratama', 'L'], ['Citra Lestari', 'P'],
            ['Dimas Aditya Saputra', 'L'], ['Eka Nur Fadilah', 'P'], ['Fajar Ramadhan', 'L'],
            ['Gita Maharani', 'P'], ['Hendra Wijaya', 'L'], ['Intan Permata Sari', 'P'],
            ['Joko Susilo', 'L'], ['Kartika Dewi', 'P'], ['Lukman Hakim', 'L'],
            ['Maya Anggraini', 'P'], ['Naufal Rizky', 'L'], ['Olivia Rahmawati', 'P'],
            ['Putra Mahendra', 'L'], ['Qonita Azzahra', 'P'], ['Rafi Kurniawan', 'L'],
            ['Salsabila Putri', 'P'], ['Taufik Hidayat', 'L'], ['Umi Kalsum', 'P'],
            ['Vino Bastian', 'L'], ['Wulan Sari', 'P'], ['Yoga Pratama', 'L'],
            ['Zahra Nabila', 'P'], ['Arif Setiawan', 'L'], ['Bella Safitri', 'P'],
            ['Candra Wijaya', 'L'], ['Dinda Ayu Lestari', 'P'], ['Erlangga Putra', 'L'],
        ];

        $classNames = array_keys($classrooms);
        $femaleDorms = ['Melati', 'Anggrek'];
        $maleDorms = ['Cempaka', 'Mawar'];
        $students = [];
        $femaleCount = 0;
        $maleCount = 0;

        foreach ($names as $index => [$name, $gender]) {
            $classroom = $classrooms[$classNames[$index % count($classNames)]];

            if ($gender === 'P') {
                $dorm = $dorms[$femaleDorms[$femaleCount % 2]];
                $femaleCount++;
            } else {
                $dorm = $dorms[$maleDorms[$maleCount % 2]];
                $maleCount++;
            }

            $status = 'aktif';
            if ($index === 22) {
                $status = 'cuti';
            } elseif ($index === 27) {
                $status = 'pindah';
            }

            $student = Student::create([
                'school_id' => $school->id,
                'classroom_id' => $classroom->id,
                'dorm_id' => $dorm->id,
                'code' => 'SR-2026-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'gender' => $gender,
                'birth_date' => now()->subYears(13 + ($index % 5))->subDays($index * 17)->toDateString(),
                'entry_date' => now()->subMonths(8)->subDays($index)->toDateString(),
                'status' => $status,
                'special_needs' => $index === 4 ? 'Memerlukan pendampingan membaca tingkat dasar.' : null,
                'consent_given_at' => now()->subMonths(8)->subDays($index),
                'consent_scope' => 'Pemrosesan data pengasuhan, presensi, dan evaluasi.',
                'consent_document' => 'Formulir persetujuan orang tua 2026',
                'created_by' => $users['admin']->id,
            ]);

            $student->identity()->create([
                'full_name' => $name,
                'nickname' => explode(' ', $name)[0],
                'birth_place' => ['Bandung', 'Garut', 'Tasikmalaya', 'Cianjur', 'Sumedang'][$index % 5],
                'address' => 'Kampung Cibiru RT '.($index + 1).' RW 04, Bandung',
            ]);

            $student->familyContacts()->create([
                'name' => 'Wali dari '.explode(' ', $name)[0],
                'relation' => $index % 3 === 0 ? 'Ibu' : ($index % 3 === 1 ? 'Ayah' : 'Wali'),
                'phone' => '0813-2000-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'is_primary' => true,
                'can_pickup' => true,
            ]);

            $students[] = $student;
        }

        return $students;
    }

    private function seedCategories(School $school): array
    {
        $definitions = [
            ['Perkelahian fisik', 'normal', false],
            ['Perundungan', 'normal', false],
            ['Kabur / tidak ditemukan', 'normal', false],
            ['Membolos', 'normal', false],
            ['Pelanggaran ringan', 'normal', false],
            ['Merokok atau vape', 'normal', false],
            ['Kerusakan fasilitas', 'normal', false],
            ['Pencurian', 'rahasia', false],
            ['Kekerasan fisik berat', 'rahasia', false],
            ['Pelanggaran aturan asrama', 'normal', false],
            ['Kekerasan seksual', 'sangat_rahasia', false],
            ['Dugaan kekerasan oleh orang dewasa', 'sangat_rahasia', false],
            ['Krisis emosi / ide melukai diri', 'sangat_rahasia', false],
            ['Apresiasi dan capaian', 'normal', true],
        ];

        $categories = [];
        foreach ($definitions as $index => [$name, $confidentiality, $positive]) {
            $categories[$name] = IncidentCategory::create([
                'school_id' => $school->id,
                'name' => $name,
                'default_confidentiality' => $confidentiality,
                'is_positive' => $positive,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        return $categories;
    }

    private function seedAttendance(School $school, array $classrooms, array $dorms, array $students, array $users): void
    {
        $teacher = $users['wali_kelas'];
        $dormWali = $users['wali_asrama'];

        for ($dayOffset = 14; $dayOffset >= 0; $dayOffset--) {
            $date = now()->subDays($dayOffset);

            if ($date->isWeekend()) {
                continue;
            }

            foreach ($classrooms as $classroom) {
                $classStudents = array_filter($students, fn (Student $s) => $s->classroom_id === $classroom->id);

                foreach ($classStudents as $student) {
                    $roll = $this->deterministicRoll($date->toDateString().'|'.$student->id.'|kelas');
                    $status = match (true) {
                        $roll <= 88 => 'hadir',
                        $roll <= 93 => 'izin',
                        $roll <= 97 => 'sakit',
                        default => 'alpa',
                    };

                    AttendanceRecord::create([
                        'school_id' => $school->id,
                        'student_id' => $student->id,
                        'date' => $date->toDateString(),
                        'type' => 'kelas',
                        'classroom_id' => $classroom->id,
                        'status' => $status,
                        'recorded_by' => $teacher->id,
                    ]);
                }
            }
        }

        for ($dayOffset = 2; $dayOffset >= 0; $dayOffset--) {
            $date = now()->subDays($dayOffset);

            foreach ($dorms as $dorm) {
                $dormStudents = array_filter($students, fn (Student $s) => $s->dorm_id === $dorm->id);

                foreach (['apel_pagi', 'makan', 'apel_malam', 'tidur'] as $type) {
                    foreach ($dormStudents as $student) {
                        $roll = $this->deterministicRoll($date->toDateString().'|'.$student->id.'|'.$dorm->id.'|'.$type);
                        $status = match (true) {
                            $roll <= 91 => 'hadir',
                            $roll <= 94 => 'izin',
                            $roll <= 97 => 'sakit',
                            $roll <= 99 => 'dispensasi',
                            default => 'alpa',
                        };

                        AttendanceRecord::create([
                            'school_id' => $school->id,
                            'student_id' => $student->id,
                            'date' => $date->toDateString(),
                            'type' => $type,
                            'dorm_id' => $dorm->id,
                            'status' => $status,
                            'recorded_by' => $dorm->wali_id ?? $dormWali->id,
                        ]);
                    }
                }
            }
        }
    }

    private function seedIncidents(School $school, array $users, array $students, array $categories): void
    {
        $service = app(IncidentService::class);

        $items = [
            [
                'days' => 0, 'hours' => 6, 'category' => 'Perkelahian fisik', 'severity' => 3,
                'status' => 'baru', 'reporter' => 'wali_asrama', 'location' => 'Kamar asrama',
                'description' => 'Dua siswa terlibat perselisihan lisan yang meningkat menjadi dorongan fisik di koridor asrama. Tidak ada luka terbuka. Petugas memisahkan keduanya dan mendampingi sampai tenang.',
                'initial_action' => 'Dipisahkan, ditenangkan, dan dicatat oleh wali asrama malam.',
                'medical' => false, 'participants' => [[$students[0], 'pelaku'], [$students[1], 'korban'], [$students[2], 'saksi']],
            ],
            [
                'days' => 1, 'hours' => 4, 'category' => 'Perundungan', 'severity' => 2,
                'status' => 'ditangani', 'reporter' => 'guru', 'location' => 'Kelas',
                'description' => 'Siswa melaporkan ejekan berulang terkait penampilan di grup kelas. Guru menghentikan percakapan, memisahkan siswa, dan merujuk ke wali kelas.',
                'initial_action' => 'Percakapan dihentikan dan wali kelas diinformasikan.',
                'medical' => false, 'participants' => [[$students[3], 'pelaku'], [$students[4], 'korban']],
            ],
            [
                'days' => 2, 'hours' => 8, 'category' => 'Kabur / tidak ditemukan', 'severity' => 3,
                'status' => 'ditangani', 'reporter' => 'wali_asrama', 'location' => 'Asrama',
                'description' => 'Siswa tidak berada di kamar saat apel malam. Pencarian dimulai pukul 21.15 dan siswa ditemukan di area kantin dalam lingkungan sekolah pada pukul 21.40.',
                'initial_action' => 'Pencarian di lingkungan sekolah, keluarga dihubungi, siswa ditemukan dan didampingi.',
                'medical' => false, 'participants' => [[$students[5], 'pelapor']],
            ],
            [
                'days' => 3, 'hours' => 2, 'category' => 'Apresiasi dan capaian', 'severity' => 1,
                'status' => 'selesai', 'reporter' => 'wali_kelas', 'location' => 'Kelas',
                'description' => 'Siswa secara konsisten membantu teman yang kesulitan membaca dan menunjukkan kepemimpinan positif di kelompok belajar.',
                'initial_action' => 'Apresiasi disampaikan di kelas dan dicatat di portofolio positif.',
                'medical' => false, 'participants' => [[$students[6], 'saksi']],
            ],
            [
                'days' => 4, 'hours' => 5, 'category' => 'Merokok atau vape', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'wali_asrama', 'location' => 'Halaman dan pagar belakang',
                'description' => 'Petugas menemukan siswa menggunakan vape di area belakang asrama saat ronde malam. Barang disita dan diserahkan kepada wali asrama.',
                'initial_action' => 'Barang disita, siswa didampingi, dan orang tua dihubungi.',
                'medical' => false, 'participants' => [[$students[7], 'pelaku']],
            ],
            [
                'days' => 6, 'hours' => 3, 'category' => 'Krisis emosi / ide melukai diri', 'severity' => 3,
                'status' => 'ditangani', 'reporter' => 'bk', 'location' => 'Ruang BK',
                'description' => 'Siswa menyampaikan perasaan tertekan dan pikiran menyakiti diri dalam sesi konseling. Tidak ada tindakan melukai diri saat ini. Siswa tetap dalam pendampingan BK.',
                'initial_action' => 'Pendampingan BK, rencana keselamatan disusun, wali asrama dan kepala sekolah diinformasikan secara terbatas.',
                'medical' => true, 'participants' => [[$students[8], 'korban']],
            ],
            [
                'days' => 8, 'hours' => 6, 'category' => 'Kekerasan seksual', 'severity' => 4,
                'status' => 'menunggu_rujukan', 'reporter' => 'bk', 'location' => 'Kamar asrama',
                'description' => 'Dugaan kekerasan seksual dilaporkan oleh siswa kepada guru BK. Detail tidak ditulis di kolom terbuka. Korban sudah didampingi dan dipisahkan dari siswa terkait. Proses mengikuti protokol perlindungan anak.',
                'initial_action' => 'Korban didampingi, akses kasus dibatasi, TPPK dan kepala sekolah diinformasikan melalui jalur aman, rujukan sedang disiapkan.',
                'medical' => true, 'participants' => [[$students[9], 'korban'], [$students[10], 'pelaku']],
            ],
            [
                'days' => 10, 'hours' => 2, 'category' => 'Membolos', 'severity' => 1,
                'status' => 'selesai', 'reporter' => 'guru', 'location' => 'Kelas',
                'description' => 'Siswa tidak mengikuti dua jam pelajaran tanpa keterangan dan ditemukan di perpustakaan.',
                'initial_action' => 'Siswa diantar kembali ke kelas, wali kelas menghubungi keluarga, dan kesepakatan belajar dibuat.',
                'medical' => false, 'participants' => [[$students[11], 'pelaku']],
            ],
            [
                'days' => 12, 'hours' => 7, 'category' => 'Pencurian', 'severity' => 2,
                'status' => 'ditinjau', 'reporter' => 'wali_asrama', 'location' => 'Kamar asrama',
                'description' => 'Uang tunai milik salah satu siswa hilang dari lemari kamar. Belum ada kesimpulan mengenai pihak yang bertanggung jawab. Pencarian dan wawancara dilakukan secara hati-hati.',
                'initial_action' => 'Barang berharga diinventarisasi ulang, saksi diwawancara, dan aturan penyimpanan barang diingatkan.',
                'medical' => false, 'participants' => [[$students[12], 'korban'], [$students[13], 'saksi']],
            ],
            [
                'days' => 15, 'hours' => 3, 'category' => 'Kerusakan fasilitas', 'severity' => 1,
                'status' => 'selesai', 'reporter' => 'wali_asrama', 'location' => 'Kamar mandi',
                'description' => 'Keran air di kamar mandi asrama rusak akibat penggunaan yang kurang hati-hati. Petugas memperbaiki dan siswa membantu membersihkan.',
                'initial_action' => 'Perbaikan dilakukan, siswa dilibatkan dalam membersihkan area.',
                'medical' => false, 'participants' => [[$students[14], 'pelaku']],
            ],
            [
                'days' => 20, 'hours' => 5, 'category' => 'Perkelahian fisik', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'wali_kelas', 'location' => 'Halaman sekolah',
                'description' => 'Dua siswa berselisih saat jam istirahat. Perselisihan tidak menimbulkan luka dan berhasil dilerai oleh guru piket.',
                'initial_action' => 'Kedua siswa dipisahkan, mediasi restoratif dilakukan, dan orang tua dihubungi.',
                'medical' => false, 'participants' => [[$students[15], 'pelaku'], [$students[16], 'saksi']],
            ],
            [
                'days' => 25, 'hours' => 4, 'category' => 'Pelanggaran aturan asrama', 'severity' => 1,
                'status' => 'ditinjau_ulang', 'reporter' => 'wali_asrama', 'location' => 'Asrama',
                'description' => 'Siswa beberapa kali terlambat kembali ke kamar setelah jam belajar malam. Alasan yang disampaikan adalah mengobrol dengan teman.',
                'initial_action' => 'Pengingat aturan asrama dan kesepakatan waktu kembali ke kamar.',
                'medical' => false, 'participants' => [[$students[17], 'pelaku']],
            ],
            [
                'days' => 35, 'hours' => 6, 'category' => 'Perundungan', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'guru', 'location' => 'Kantin',
                'description' => 'Siswa menyampaikan ketidaknyamanan karena dikucilkan oleh kelompok teman saat makan siang.',
                'initial_action' => 'Wali kelas memfasilitasi percakapan kelompok dan membuat kesepakatan inklusi.',
                'medical' => false, 'participants' => [[$students[18], 'korban'], [$students[19], 'pelaku']],
            ],
            [
                'days' => 45, 'hours' => 8, 'category' => 'Kabur / tidak ditemukan', 'severity' => 3,
                'status' => 'selesai', 'reporter' => 'wali_asrama', 'location' => 'Asrama',
                'description' => 'Siswa tidak ditemukan saat pemeriksaan tidur. Keluarga dihubungi dan siswa kembali ke asrama keesokan paginya.',
                'initial_action' => 'Pencarian, komunikasi dengan keluarga, pendampingan, dan kesepakatan adaptasi.',
                'medical' => false, 'participants' => [[$students[20], 'pelapor']],
            ],
            [
                'days' => 60, 'hours' => 3, 'category' => 'Perkelahian fisik', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'wali_asrama', 'location' => 'Ruang makan',
                'description' => 'Perselisihan memperebutkan tempat duduk saat makan malam. Tidak ada luka dan situasi segera dikendalikan.',
                'initial_action' => 'Pemisahan tempat sementara dan mediasi restoratif.',
                'medical' => false, 'participants' => [[$students[21], 'pelaku'], [$students[22], 'saksi']],
            ],
            [
                'days' => 75, 'hours' => 6, 'category' => 'Perundungan', 'severity' => 3,
                'status' => 'selesai', 'reporter' => 'tppk', 'location' => 'Asrama',
                'description' => 'Laporan berulang mengenai tekanan kelompok terhadap seorang siswa. TPPK melakukan asesmen dan pendampingan intensif.',
                'initial_action' => 'Pendampingan korban, pemisahan kegiatan sementara, dan keterlibatan keluarga.',
                'medical' => false, 'participants' => [[$students[23], 'korban'], [$students[24], 'pelaku']],
            ],
            [
                'days' => 95, 'hours' => 4, 'category' => 'Krisis emosi / ide melukai diri', 'severity' => 3,
                'status' => 'selesai', 'reporter' => 'bk', 'location' => 'Ruang BK',
                'description' => 'Siswa melaporkan kesulitan mengelola emosi setelah konflik keluarga. Rencana keselamatan dan jadwal konseling disusun bersama.',
                'initial_action' => 'Konseling, komunikasi dengan wali asrama, dan rujukan layanan psikologis.',
                'medical' => false, 'participants' => [[$students[25], 'korban']],
            ],
            [
                'days' => 110, 'hours' => 5, 'category' => 'Perundungan', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'guru', 'location' => 'Kelas',
                'description' => 'Siswa saling mengejek dan membuat kelompok yang menyingkirkan salah satu teman. Guru melakukan mediasi kelas.',
                'initial_action' => 'Mediasi kelas, kesepakatan perilaku, dan pemantauan dua minggu.',
                'medical' => false, 'participants' => [[$students[26], 'korban'], [$students[27], 'pelaku']],
            ],
            [
                'days' => 130, 'hours' => 2, 'category' => 'Apresiasi dan capaian', 'severity' => 1,
                'status' => 'selesai', 'reporter' => 'wali_kelas', 'location' => 'Kelas',
                'description' => 'Siswa menunjukkan peningkatan kedisiplinan dan membantu menyiapkan kelas setiap pagi selama dua minggu.',
                'initial_action' => 'Apresiasi diberikan di depan kelas dan dicatat dalam portofolio positif.',
                'medical' => false, 'participants' => [[$students[28], 'saksi']],
            ],
            [
                'days' => 160, 'hours' => 6, 'category' => 'Perkelahian fisik', 'severity' => 2,
                'status' => 'selesai', 'reporter' => 'wali_asrama', 'location' => 'Asrama',
                'description' => 'Perselisihan antar siswa terkait barang pinjaman. Situasi dilerai dan diselesaikan melalui mediasi.',
                'initial_action' => 'Mediasi restoratif dan kesepakatan pengembalian barang.',
                'medical' => false, 'participants' => [[$students[29], 'pelaku']],
            ],
        ];

        foreach ($items as $item) {
            $occurredAt = now()->subDays($item['days'])->subHours($item['hours']);
            $reporter = $users[$item['reporter']];

            $incident = $service->create([
                'category_id' => $categories[$item['category']]->id,
                'severity' => $item['severity'],
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
                'location' => $item['location'],
                'description' => $item['description'],
                'initial_action' => $item['initial_action'],
                'needs_medical' => $item['medical'],
                'participants' => array_map(fn ($p) => [
                    'student_id' => $p[0]->id,
                    'role' => $p[1],
                ], $item['participants']),
            ], $reporter, $school);

            $status = $item['status'];
            $closed = in_array($status, ['selesai'], true);

            $update = [
                'status' => $status,
                'assigned_to' => $incident->assigned_to,
                'created_at' => $occurredAt->copy()->addMinutes(25),
                'updated_at' => $closed ? $occurredAt->copy()->addDays(2) : now(),
            ];

            if ($closed) {
                $update['closed_at'] = $occurredAt->copy()->addDays(2);
                $update['resolution_notes'] = 'Ditangani sesuai protokol, pemulihan dan tindak lanjut dipantau.';
                $update['sanction'] = $item['severity'] >= 2 ? 'mediasi_restoratif' : 'teguran_lisan';
            }

            if ($item['days'] === 1) {
                $update['assigned_to'] = $users['wali_kelas']->id;
                $update['resolution_notes'] = 'Wali kelas mendampingi korban dan pelaku, kesepakatan perilaku disusun.';
            }

            if ($item['days'] === 2) {
                $update['resolution_notes'] = 'Siswa ditemukan, keluarga dihubungi, dan pendampingan adaptasi dijadwalkan.';
            }

            if ($item['days'] === 6 || $item['days'] === 8) {
                $update['assigned_to'] = $users['tppk']->id;
            }

            if ($item['days'] === 12 && $status === 'ditinjau') {
                $update['due_at'] = now()->subDays(2);
            }

            if (in_array($item['days'], [0, 1, 2, 6, 7], true)) {
                $update['due_at'] = now()->addHours($item['days'] === 0 ? 18 : 10);
            }

            $incident->forceFill($update)->save();

            if ($closed) {
                foreach ($incident->tasks as $task) {
                    $task->forceFill([
                        'is_done' => true,
                        'done_by' => $incident->assigned_to,
                        'done_at' => $occurredAt->copy()->addDay(),
                    ])->save();
                }

                foreach ($incident->followups as $followup) {
                    $followup->forceFill([
                        'status' => 'done',
                        'done_by' => $incident->assigned_to,
                        'done_at' => $occurredAt->copy()->addWeeks($followup->week),
                        'notes' => 'Tindak lanjut dilakukan, kondisi siswa stabil.',
                    ])->save();
                }
            }
        }
    }

    private function deterministicRoll(string $seed): int
    {
        return (crc32($seed) % 100) + 1;
    }

    private function seedDormLogs(School $school, array $dorms, array $users): void
    {
        $checkpoints = config('sigap.dorm_checkpoints');

        $entries = [
            ['days' => 0, 'shift' => 'malam', 'dorm' => 'Melati', 'officer' => $users['wali_asrama'], 'notes' => 'Ronde pukul 22.00. Semua kamar dalam kondisi aman. Satu siswa izin ke UKS didampingi teman sekamar.', 'handover' => 'Pagi: pantau siswa yang izin ke UKS dan pastikan sudah kembali.'],
            ['days' => 1, 'shift' => 'pagi', 'dorm' => 'Melati', 'officer' => $users['wali_asrama'], 'notes' => 'Apel pagi berjalan tertib. Dua siswa terlambat bangun dan sudah diingatkan.', 'handover' => 'Siang: ingatkan jadwal piket kamar.'],
            ['days' => 1, 'shift' => 'malam', 'dorm' => 'Cempaka', 'officer' => $users['wali_asrama_putra'], 'notes' => 'Ronde malam selesai. Gerbang belakang sempat terbuka dan sudah dikunci ulang.', 'handover' => 'Periksa kunci gerbang belakang setiap shift.'],
            ['days' => 2, 'shift' => 'siang', 'dorm' => 'Anggrek', 'officer' => $users['wali_asrama'], 'notes' => 'Perlengkapan makan bersih. Tidak ada kejadian khusus.', 'handover' => 'Lanjutkan pemeriksaan kamar mandi sore.'],
        ];

        foreach ($entries as $entry) {
            $checked = array_slice($checkpoints, 0, 5);

            DormLog::create([
                'school_id' => $school->id,
                'dorm_id' => $dorms[$entry['dorm']]->id,
                'log_date' => now()->subDays($entry['days'])->toDateString(),
                'shift' => $entry['shift'],
                'officer_id' => $entry['officer']->id,
                'checkpoints' => $checked,
                'notes' => $entry['notes'],
                'handover_notes' => $entry['handover'],
            ]);
        }
    }
}
