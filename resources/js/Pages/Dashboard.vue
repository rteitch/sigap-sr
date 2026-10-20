<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    CalendarCheck2,
    Clock,
    Database,
    EyeOff,
    FolderOpen,
    Plus,
    ShieldAlert,
    ShieldCheck,
    TrendingUp,
    Users,
} from '@lucide/vue';
import AppLayout from '../Layouts/AppLayout.vue';
import PageHeader from '../Components/PageHeader.vue';
import StatCard from '../Components/StatCard.vue';
import EmptyState from '../Components/EmptyState.vue';
import {
    confidentialityClass,
    formatDateTime,
    percentage,
    relativeTime,
    severityBarClass,
    severityClass,
    statusClass,
} from '../lib/labels';

defineProps({
    stats: { type: Object, required: true },
    trend: { type: Array, required: true },
    severities: { type: Array, required: true },
    categories: { type: Array, required: true },
    hidden_category_count: { type: Number, default: 0 },
    recent_incidents: { type: Array, required: true },
    quality: { type: Object, required: true },
    threshold: { type: Number, default: 5 },
    generated_at: { type: String, default: '' },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const options = computed(() => page.props.options);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 19) return 'Selamat sore';
    return 'Selamat malam';
});

const trendMax = computed(() => Math.max(1, ...(page.props.trend ?? []).map((point) => point.value)));
const severityMax = computed(() => Math.max(1, ...(page.props.severities ?? []).map((row) => row.value)));
const categoryMax = computed(() => Math.max(1, ...(page.props.categories ?? []).map((row) => row.value)));
const dormRate = computed(() => percentage(page.props.stats.dorm_present, page.props.stats.dorm_total));
</script>

<template>
    <Head title="Dasbor" />

    <AppLayout>
        <PageHeader
            :title="`${greeting}, ${user?.name?.split(' ')[0] ?? 'rekan'}`"
            :description="`Ringkasan pengasuhan dan pembinaan ${page.props.school?.name}. Angka kecil disembunyikan untuk melindungi identitas anak.`"
            eyebrow="Dasbor evaluasi"
        >
            <template #actions>
                <Link href="/insiden?overdue=1" class="btn-secondary">
                    <Clock class="h-4 w-4" /> Kasus lewat tenggat
                </Link>
                <Link href="/insiden/buat" class="btn-primary">
                    <Plus class="h-4 w-4" /> Catat insiden
                </Link>
            </template>
        </PageHeader>

        <div
            v-if="stats.overdue_cases > 0"
            class="mb-5 flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700">
                    <AlertTriangle class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-bold text-rose-900">{{ stats.overdue_cases }} kasus melewati tenggat penanganan</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-rose-700">Eskalasi diperlukan. Tinjau penanggung jawab dan langkah protokol yang belum selesai.</p>
                </div>
            </div>
            <Link href="/insiden?overdue=1" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                Tinjau sekarang <ArrowRight class="h-3.5 w-3.5" />
            </Link>
        </div>

        <div
            v-if="stats.confidential_open > 0 && user?.can_view_confidential"
            class="mb-5 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800"
        >
            <EyeOff class="h-5 w-5 shrink-0" />
            <p class="text-xs leading-relaxed">
                <span class="font-bold">{{ stats.confidential_open }} kasus sangat rahasia masih terbuka.</span>
                Detail hanya dapat dibuka oleh kepala sekolah, TPPK, dan BK. Setiap akses tercatat.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Siswa aktif" :value="stats.active_students" hint="Siswa dalam lingkup akses Anda" tone="brand">
                <template #icon><Users class="h-5 w-5" /></template>
            </StatCard>
            <StatCard label="Insiden bulan ini" :value="stats.incidents_month" hint="Kenaikan bisa berarti pelaporan membaik" tone="amber">
                <template #icon><ShieldAlert class="h-5 w-5" /></template>
            </StatCard>
            <StatCard label="Kasus terbuka" :value="stats.open_cases" :hint="stats.overdue_cases ? `${stats.overdue_cases} melewati tenggat` : 'Semua dalam tenggat'" :tone="stats.overdue_cases ? 'rose' : 'sky'">
                <template #icon><FolderOpen class="h-5 w-5" /></template>
            </StatCard>
            <StatCard label="Kehadiran kelas" :value="stats.attendance_rate !== null ? `${stats.attendance_rate}%` : '—'" hint="Rata-rata 30 hari terakhir" tone="emerald">
                <template #icon><CalendarCheck2 class="h-5 w-5" /></template>
            </StatCard>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-3">
            <div class="card p-5 xl:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Tren insiden</p>
                        <p class="mt-0.5 text-xs text-slate-500">Enam bulan terakhir, tanpa catatan apresiasi</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-700">
                        <TrendingUp class="h-3.5 w-3.5" /> Pembelajaran
                    </span>
                </div>

                <div class="mt-6 flex h-40 items-end gap-3 sm:gap-5">
                    <div v-for="(point, index) in trend" :key="point.label" class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-xs font-bold text-slate-500">{{ point.value }}</span>
                        <div class="flex w-full flex-1 items-end">
                            <div
                                class="w-full rounded-t-lg transition-all duration-500"
                                :class="index === trend.length - 1 ? 'bg-brand-600' : 'bg-brand-200 group-hover:bg-brand-300'"
                                :style="{ height: `${Math.max(5, (point.value / trendMax) * 100)}%` }"
                            ></div>
                        </div>
                        <span class="text-[11px] font-semibold" :class="index === trend.length - 1 ? 'text-brand-800' : 'text-slate-500'">{{ point.label }}</span>
                    </div>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] leading-relaxed text-slate-400">
                    Catatan: kenaikan angka di awal dapat berarti pelaporan membaik, bukan hanya perilaku memburuk. Bacakan bersama data kualitas sebelum mengambil keputusan.
                </p>
            </div>

            <div class="card p-5">
                <p class="text-sm font-bold text-slate-800">Kehadiran asrama hari ini</p>
                <p class="mt-0.5 text-xs text-slate-500">Apel, makan, dan tidur</p>
                <div class="mt-5 flex items-center gap-5">
                    <div
                        class="grid h-28 w-28 shrink-0 place-items-center rounded-full"
                        :style="{ background: `conic-gradient(#0d9488 ${dormRate * 3.6}deg, #e2e8f0 0deg)` }"
                    >
                        <div class="grid h-20 w-20 place-items-center rounded-full bg-white">
                            <div class="text-center">
                                <p class="text-xl font-bold text-slate-900">{{ dormRate }}%</p>
                                <p class="text-[10px] font-medium text-slate-500">hadir</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                            <span class="text-slate-600">Hadir</span>
                            <span class="ml-auto font-bold text-slate-900">{{ stats.dorm_present }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span>
                            <span class="text-slate-600">Tercatat lain</span>
                            <span class="ml-auto font-bold text-slate-900">{{ Math.max(stats.dorm_total - stats.dorm_present, 0) }}</span>
                        </div>
                        <p class="pt-1 text-[11px] text-slate-400">Total {{ stats.dorm_total }} catatan kegiatan</p>
                        <Link v-if="user?.can_access_dorm" href="/asrama" class="inline-flex items-center gap-1 pt-1 text-[11px] font-semibold text-brand-700 hover:underline">
                            Buka piket asrama <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-3">
            <div class="card overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Insiden terbaru</p>
                        <p class="mt-0.5 text-xs text-slate-500">Tanpa menampilkan nama anak di ringkasan</p>
                    </div>
                    <Link href="/insiden" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                        Lihat semua <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-if="recent_incidents.length" class="divide-y divide-slate-100">
                    <Link
                        v-for="incident in recent_incidents"
                        :key="incident.id"
                        :href="`/insiden/${incident.id}`"
                        class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50"
                    >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="severityClass(incident.severity)">
                            <AlertTriangle class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ incident.category }}</p>
                                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1" :class="statusClass(incident.status)">
                                    {{ options.incident_statuses[incident.status] }}
                                </span>
                                <span v-if="incident.confidentiality !== 'normal'" class="rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1" :class="confidentialityClass(incident.confidentiality)">
                                    {{ options.confidentialities[incident.confidentiality] }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                {{ incident.case_number }} · {{ incident.assignee ?? 'Belum ada PIC' }} · {{ relativeTime(incident.occurred_at) }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="block text-[11px] font-bold" :class="severityClass(incident.severity).split(' ')[1]">T{{ incident.severity }}</span>
                            <span v-if="incident.overdue" class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-semibold text-rose-600">
                                <Clock class="h-3 w-3" /> lewat
                            </span>
                        </div>
                    </Link>
                </div>
                <EmptyState v-else title="Belum ada insiden" description="Catatan insiden yang sesuai akses Anda akan muncul di sini." />
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Kualitas data bulan ini</p>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-500">Langkah protokol selesai</span>
                            <span class="text-sm font-bold text-slate-900">{{ quality.task_completion !== null ? `${quality.task_completion}%` : '—' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-brand-600" :style="{ width: `${quality.task_completion ?? 0}%` }"></div>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-slate-500">Audit acak 10%</span>
                            <span class="font-semibold text-slate-800">{{ quality.audited_sample }} dari {{ quality.total_incidents }} catatan</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-slate-500">Kasus berat tanpa tindak lanjut</span>
                            <span class="font-semibold" :class="quality.without_followup ? 'text-rose-600' : 'text-emerald-700'">{{ quality.without_followup }}</span>
                        </div>
                    </div>
                    <p class="mt-4 flex items-start gap-2 rounded-lg bg-slate-50 p-2.5 text-[11px] leading-relaxed text-slate-500">
                        <Database class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        Periksa kualitas data sebelum membaca tren. Angka kelompok kurang dari {{ threshold }} disembunyikan.
                    </p>
                </div>

                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-800">Kategori dominan</p>
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">90 hari</span>
                    </div>
                    <div v-if="categories.length" class="mt-4 space-y-3">
                        <div v-for="category in categories" :key="category.name">
                            <div class="mb-1 flex items-center justify-between gap-3 text-[11px]">
                                <span class="truncate font-medium text-slate-600">{{ category.name }}</span>
                                <span class="font-bold text-slate-900">{{ category.value }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-slate-400" :style="{ width: `${Math.max(6, (category.value / categoryMax) * 100)}%` }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-xs text-slate-500">Belum cukup data untuk ditampilkan.</p>
                    <p v-if="hidden_category_count" class="mt-3 flex items-center gap-1.5 text-[10px] text-slate-400">
                        <EyeOff class="h-3 w-3" /> {{ hidden_category_count }} kategori berjumlah &lt;{{ threshold }} disembunyikan.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Sebaran tingkat keparahan</p>
                        <p class="mt-0.5 text-xs text-slate-500">Bulan berjalan</p>
                    </div>
                    <Activity class="h-5 w-5 text-slate-300" />
                </div>
                <div class="mt-4 space-y-3">
                    <div v-for="row in severities" :key="row.severity">
                        <div class="mb-1 flex items-center justify-between text-[11px]">
                            <span class="font-medium text-slate-600">{{ options.incident_severities[row.severity] }}</span>
                            <span class="font-bold text-slate-900">{{ row.value }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full" :class="severityBarClass(row.severity)" :style="{ width: `${Math.max(row.value ? 5 : 0, (row.value / severityMax) * 100)}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-white p-5">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-brand-100">
                        <ShieldCheck class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Ingat prinsip penanganan</p>
                        <ul class="mt-2 space-y-1.5 text-xs leading-relaxed text-slate-600">
                            <li class="flex gap-2"><span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>Keselamatan dan pemulihan korban lebih dulu.</li>
                            <li class="flex gap-2"><span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>Tidak ada penyelesaian damai internal untuk dugaan kekerasan seksual.</li>
                            <li class="flex gap-2"><span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>Manusia memutuskan; sistem hanya mengingatkan dan merapikan.</li>
                            <li class="flex gap-2"><span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>Notifikasi tidak memuat nama anak atau isi insiden.</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-brand-100 pt-3">
                    <Link href="/insiden" class="btn-secondary !px-3 !py-2 text-xs">Daftar kasus</Link>
                    <Link href="/laporan" class="btn-ghost text-xs">Lihat laporan bulanan</Link>
                </div>
            </div>
        </div>

        <p class="mt-5 text-right text-[10px] text-slate-400">Data dihasilkan {{ formatDateTime(generated_at) }}</p>
    </AppLayout>
</template>
