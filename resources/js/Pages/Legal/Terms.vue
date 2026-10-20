<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    FileText,
    KeyRound,
    Lock,
    Scale,
    ShieldAlert,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import BrandMark from '../../Components/BrandMark.vue';

const props = defineProps({
    updatedAt: { type: String, default: '2026-10-20' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const branding = computed(() => page.props.branding || {});
</script>

<template>
    <Head title="Persyaratan Layanan & Kode Etik Pengasuhan" />

    <div class="min-h-screen bg-slate-50 text-slate-800">
        <!-- Top Navbar -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <Link :href="user ? '/dashboard' : '/login'" class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" :aria-label="user ? 'Kembali ke dasbor' : 'Kembali ke halaman login'">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <BrandMark size="sm" />
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="user ? '/dashboard' : '/login'" class="btn-secondary text-xs">
                        {{ user ? 'Masuk ke Dasbor' : 'Masuk Akun' }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Header Hero -->
        <div class="border-b border-slate-200 bg-gradient-to-b from-amber-50/60 to-white px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-4xl">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-300">
                    <Scale class="h-3.5 w-3.5" /> Ketentuan Penggunaan Resmi Satuan Pendidikan
                </div>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                    Persyaratan Layanan &amp; Etika Akses Sistem
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">
                    Ketentuan operasional dan standar etika bagi seluruh pendidik, wali kelas, wali asrama, konselor BK,
                    tim TPPK, dan staf administrasi dalam mengoperasikan sistem {{ branding.app_name || 'SIGAP-SR' }}.
                </p>
                <p class="mt-2 text-xs text-slate-400">Pembaruan terakhir: {{ updatedAt }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <div class="space-y-8">
                <!-- 1. Akun dan Tanggung Jawab -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-100 text-brand-800"><KeyRound class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">1. Tanggung Jawab Kredensial &amp; Keamanan Akun</h2>
                            <p class="text-xs text-slate-500">Kerahasiaan akun staf adalah tanggung jawab individu</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>Setiap pengguna yang diberikan akses ke SIGAP-SR wajib mematuhi ketentuan berikut:</p>
                        <ul class="list-disc space-y-1.5 pl-5">
                            <li><strong>Dilarang keras berbagi akun atau kata sandi</strong> kepada rekan kerja, siswa, atau pihak eksternal dengan alasan apa pun.</li>
                            <li>Wajib mengganti kata sandi secara berkala melalui menu <em>Profil &amp; Keamanan</em> dan menggunakan kombinasi kata sandi yang kuat (minimal 8 karakter).</li>
                            <li>Wajib melakukan <em>Logout</em> saat meninggalkan komputer atau perangkat kerja bersama.</li>
                        </ul>
                    </div>
                </section>

                <!-- 2. Larangan Pembocoran Data -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-100 text-rose-800"><ShieldAlert class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">2. Kewajiban Kerahasiaan Data Kasus &amp; Anak</h2>
                            <p class="text-xs text-slate-500">Perlindungan mutlak identitas anak korban, saksi, dan terlapor</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>
                            Sesuai UU Perlindungan Anak dan Permendikbudristek No. 46/2023, data insiden kekerasan anak, catatan konseling BK,
                            dan kondisi kerentanan santri berstatus <strong>SANGAT RAHASIA</strong>.
                        </p>
                        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-900">
                            <strong>Peringatan Tegas:</strong> Mengambil tangkapan layar (screenshot), memotret layar monitor,
                            menyebarluaskan identitas anak ke grup perpesanan (WhatsApp/Telegram), atau membocorkan materi investigasi TPPK
                            merupakan pelanggaran hukum berat dan tunduk pada sanksi pidana serta pemecatan dinas.
                        </div>
                    </div>
                </section>

                <!-- 3. Integritas Data Presensi & Logbook -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-100 text-teal-800"><CheckCircle2 class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">3. Kejujuran &amp; Integritas Pencatatan Data</h2>
                            <p class="text-xs text-slate-500">Data presensi, ronda asrama, dan jurnal pengasuhan</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>
                            Wali kelas dan wali asrama wajib mencatat kehadiran, status kesehatan, dan ronda malam asrama secara faktual, akurat, dan tepat waktu.
                        </p>
                        <ul class="list-disc space-y-1.5 pl-5">
                            <li>Memalsukan kehadiran siswa, menyembunyikan keterlambatan ronda, atau memanipulasi logbook asrama dilarang keras.</li>
                            <li>Ketiadaan siswa tanpa keterangan (*missing student*) yang melebihi batas waktu toleransi wajib segera dieskalasikan ke pimpinan sekolah.</li>
                        </ul>
                    </div>
                </section>

                <!-- 4. Penanganan Insiden & Rujukan -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-100 text-amber-800"><AlertTriangle class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">4. Kepatuhan Protokol &amp; Rencana Aksi Insiden</h2>
                            <p class="text-xs text-slate-500">Alur berjenjang dan pemulihan korban</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>
                            Seluruh staf yang mengetahui atau menyaksikan dugaan kekerasan wajib segera mencatatnya melalui fitur <em>Laporkan Insiden</em>
                            untuk ditindaklanjuti oleh Kepala Sekolah dan TPPK.
                        </p>
                        <p>
                            Dilarang menyelesaikan kasus kekerasan berat secara informal atau sepihak tanpa koordinasi tertulis dengan Tim Pencegahan dan Penanganan Kekerasan.
                        </p>
                    </div>
                </section>

                <!-- 5. Penonaktifan Akun & Sanksi -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-200 text-slate-800"><Scale class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">5. Sanksi &amp; Penonaktifan Akses</h2>
                            <p class="text-xs text-slate-500">Penegakan disiplin dan perlindungan integritas lembaga</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm leading-relaxed text-slate-600">
                        <p>
                            Administrator Sekolah berhak mencabut atau menonaktifkan akun staf yang mutasi, purna tugas, atau terindikasi melakukan penyalahgunaan data.
                            Pelanggaran berat terhadap kerahasiaan data anak akan diteruskan ke aparat penegak hukum sesuai perundang-undangan Republik Indonesia.
                        </p>
                    </div>
                </section>
            </div>

            <!-- Footer links -->
            <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6 text-xs text-slate-500">
                <div class="flex items-center gap-3">
                    <Link href="/kebijakan-privasi" class="font-semibold text-brand-700 hover:underline">← Baca Kebijakan Privasi</Link>
                </div>
                <p>{{ branding.app_name || 'SIGAP-SR' }} · Sekolah Rakyat Berdaya &amp; Melindungi</p>
            </div>
        </main>
    </div>
</template>
