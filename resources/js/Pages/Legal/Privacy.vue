<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Database,
    EyeOff,
    FileCheck2,
    Lock,
    Scale,
    ShieldAlert,
    ShieldCheck,
    UserCheck,
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
    <Head title="Kebijakan Privasi & Perlindungan Data Anak" />

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
        <div class="border-b border-slate-200 bg-gradient-to-b from-brand-50/60 to-white px-4 py-10 sm:px-6 sm:py-14">
            <div class="mx-auto max-w-4xl">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800 ring-1 ring-brand-300">
                    <ShieldCheck class="h-3.5 w-3.5" /> Standar Perlindungan Data Pribadi (UU PDP No. 27/2022)
                </div>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                    Kebijakan Privasi &amp; Perlindungan Identitas Anak
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">
                    Sistem Informasi Pengasuhan, Pembinaan, dan Evaluasi Sekolah Rakyat ({{ branding.app_name || 'SIGAP-SR' }})
                    dirancang dengan prinsip <em>Privacy by Design</em> untuk melindungi harkat, martabat, dan kerahasiaan santri/siswa,
                    khususnya dalam pencatatan insiden perlindungan anak.
                </p>
                <p class="mt-2 text-xs text-slate-400">Pembaruan terakhir: {{ updatedAt }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <div class="space-y-8">
                <!-- 1. Prinsip Dasar -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-100 text-brand-800"><Scale class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">1. Landasan Hukum &amp; Komitmen Pengasuhan</h2>
                            <p class="text-xs text-slate-500">Kepatuhan terhadap regulasi perlindungan anak dan data pribadi</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>
                            Pengelolaan data pada SIGAP-SR tunduk pada <strong>Undang-Undang No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)</strong>
                            serta <strong>Permendikbudristek No. 46 Tahun 2023 tentang Pencegahan dan Penanganan Kekerasan di Lingkungan Satuan Pendidikan (PPKSP)</strong>.
                        </p>
                        <p>
                            Setiap pemrosesan data bertujuan semata-mata untuk keselamatan, pembinaan moral/karakter, pengasuhan komprehensif, dan pemulihan anak di lingkungan Sekolah Rakyat.
                        </p>
                    </div>
                </section>

                <!-- 2. Pemisahan Identitas -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-100 text-teal-800"><EyeOff class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">2. Pemisahan Identitas Langsung (Pseudonimisasi)</h2>
                            <p class="text-xs text-slate-500">Mencegah pelabelan dan stigma sosial terhadap peserta didik</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>
                            SIGAP-SR menerapkan pemisahan arsitektural antara <strong>Tabel Identitas Siswa</strong> (nama lengkap, foto avatar, NIK, alamat domisili)
                            dan <strong>Tabel Operasional Pengasuhan</strong> (presensi, rekap evaluasi, dan log kegiatan asrama).
                        </p>
                        <ul class="list-disc space-y-1.5 pl-5">
                            <li>Data analisis operasional mengidentifikasi siswa menggunakan kode pseudonim unik (contoh: <code>SR-2026-0001</code>).</li>
                            <li>Foto/avatar dan identitas lengkap siswa hanya terbuka bagi staf yang memiliki otorisasi langsung (Wali Kelas yang mengampu, Wali Asrama yang membina, Guru BK, dan Kepala Sekolah).</li>
                            <li>Laporan eksternal atau rekapitulasi dinas otomatis diagregasi tanpa menyertakan identitas langsung anak.</li>
                        </ul>
                    </div>
                </section>

                <!-- 3. Klasifikasi Kerahasiaan Insiden -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-100 text-rose-800"><Lock class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">3. Tingkat Kerahasiaan Kasus &amp; Insiden</h2>
                            <p class="text-xs text-slate-500">Akses bertingkat berdasarkan tingkat keparahan (*severity*)</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
                        <p>Laporan insiden diklasifikasikan ke dalam 3 tingkat kerahasiaan:</p>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Terbuka Terbatas</span>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">Pelanggaran kedisiplinan ringan. Dapat diakses oleh guru piket, wali kelas, dan pembina asrama.</p>
                            </div>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <span class="rounded bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-800">Rahasia</span>
                                <p class="mt-2 text-xs leading-relaxed text-amber-900">Kasus pembinaan khusus, konseling BK, dan riwayat kesehatan sensitif.</p>
                            </div>
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                                <span class="rounded bg-rose-200 px-2 py-0.5 text-[10px] font-bold text-rose-800">Sangat Rahasia</span>
                                <p class="mt-2 text-xs leading-relaxed text-rose-900">Kekerasan fisik berat, perundungan sistemik, atau pelecehan. Hanya dapat dibuka oleh Kepala Sekolah dan TPPK.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. Hak Orang Tua & Siswa -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-100 text-emerald-800"><UserCheck class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">4. Hak Orang Tua / Wali Peserta Didik</h2>
                            <p class="text-xs text-slate-500">Transparansi, persetujuan (*informed consent*), dan koreksi data</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm leading-relaxed text-slate-600">
                        <p>Orang tua atau wali sah berhak:</p>
                        <ul class="list-disc space-y-1.5 pl-5">
                            <li>Mengetahui data apa saja yang dicatat mengenai anak mereka di lingkungan asrama dan sekolah.</li>
                            <li>Memperbarui atau memperbaiki kekeliruan data kontak darurat, alamat, dan kondisi kebutuhan khusus.</li>
                            <li>Meninjau lembar persetujuan (*consent document*) pengasuhan yang tersimpan pada berkas siswa.</li>
                        </ul>
                    </div>
                </section>

                <!-- 5. Jejak Audit & Keamanan -->
                <section class="card p-6 sm:p-8">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-100 text-indigo-800"><Database class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">5. Jejak Audit Tak Terbantahkan (*Immutable Audit Trail*)</h2>
                            <p class="text-xs text-slate-500">Setiap pembacaan dan perubahan data tercatat permanen</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm leading-relaxed text-slate-600">
                        <p>
                            Demi menjamin akuntabilitas seluruh staf dan mencegah kebocoran informasi, setiap aksi pembukaan profil siswa, ekspor data excel,
                            pengubahan nilai presensi, atau penanganan insiden dicatat secara otomatis ke dalam log audit sistem yang memuat identitas pengguna, alamat IP, peramban, dan cap waktu.
                        </p>
                    </div>
                </section>
            </div>

            <!-- Footer links -->
            <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6 text-xs text-slate-500">
                <div class="flex items-center gap-3">
                    <Link href="/persyaratan-layanan" class="font-semibold text-brand-700 hover:underline">Baca Persyaratan Layanan →</Link>
                </div>
                <p>{{ branding.app_name || 'SIGAP-SR' }} · Sekolah Rakyat Berdaya &amp; Melindungi</p>
            </div>
        </main>
    </div>
</template>
