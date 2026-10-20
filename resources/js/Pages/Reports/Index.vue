<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    BarChart3,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    EyeOff,
    Info,
    ShieldCheck,
    Timer,
    TrendingUp,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { severityClass } from '../../lib/labels';

const props = defineProps({
    month: { type: String, required: true },
    month_label: { type: String, required: true },
    report: { type: Object, required: true },
});

const page = usePage();
const options = computed(() => page.props.options);
const selectedMonth = ref(props.month);

const summary = computed(() => Object.fromEntries(props.report.summary.map((row) => [row.label, row.value])));
const totalSeverity = computed(() => props.report.severities.reduce((sum, row) => sum + row.value, 0));
const maxCategory = computed(() => Math.max(1, ...props.report.categories.map((row) => row.value)));
const exportUrl = computed(() => `/laporan/ekspor?month=${props.month}`);

function applyMonth(value) {
    router.get('/laporan', { month: value }, { preserveState: true, preserveScroll: true, replace: true });
}

function shiftMonth(offset) {
    const [year, month] = props.month.split('-').map(Number);
    const date = new Date(year, month - 1 + offset, 1);
    const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    selectedMonth.value = value;
    applyMonth(value);
}

const severityTone = {
    1: 'bg-sky-500',
    2: 'bg-amber-500',
    3: 'bg-orange-500',
    4: 'bg-rose-600',
};
</script>

<template>
    <Head title="Laporan" />

    <AppLayout>
        <PageHeader
            title="Laporan bulanan"
            description="Ringkasan agregat pengasuhan, insiden, dan kehadiran. Laporan tidak memuat identitas anak dan menyembunyikan kelompok kecil."
            eyebrow="Evaluasi"
        >
            <template #actions>
                <a :href="exportUrl" class="btn-primary"><ArrowDownToLine class="h-4 w-4" /> Ekspor CSV</a>
            </template>
        </PageHeader>

        <section class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <label class="label" for="month">Periode</label>
                <div class="flex gap-1.5">
                    <button type="button" class="btn-secondary !px-2.5" aria-label="Bulan sebelumnya" @click="shiftMonth(-1)"><ChevronLeft class="h-4 w-4" /></button>
                    <input id="month" v-model="selectedMonth" type="month" class="field" @change="applyMonth(selectedMonth)" />
                    <button type="button" class="btn-secondary !px-2.5" aria-label="Bulan berikutnya" @click="shiftMonth(1)"><ChevronRight class="h-4 w-4" /></button>
                </div>
            </div>
            <div class="flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-xs text-brand-800">
                <CalendarDays class="h-4 w-4" />
                <span class="font-bold">{{ month_label }}</span>
                <span class="text-brand-800/60">· laporan agregat</span>
            </div>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total insiden" :value="summary['Total insiden']" hint="Kasus negatif bulan ini" :icon="BarChart3" tone="brand" />
            <StatCard label="Kasus terbuka" :value="summary['Kasus terbuka']" hint="Belum selesai" :icon="Timer" tone="amber" />
            <StatCard label="Lewat tenggat" :value="summary['Kasus lewat tenggat']" hint="Perlu perhatian" :icon="Timer" tone="rose" />
            <StatCard label="Kasus selesai" :value="summary['Kasus selesai']" hint="Ditutup bulan ini" :icon="CheckCircle2" tone="emerald" />
        </div>

        <div class="mt-4 grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Distribusi tingkat keparahan</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ totalSeverity }} kasus negatif tercatat pada periode ini</p>
                        </div>
                        <TrendingUp class="h-4 w-4 text-slate-300" />
                    </div>

                    <div v-if="totalSeverity" class="mt-5 space-y-3">
                        <div v-for="row in report.severities" :key="row.severity">
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span class="font-semibold text-slate-600">{{ row.label }}</span>
                                <span class="font-bold text-slate-800">{{ row.value }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full transition-all" :class="severityTone[row.severity] ?? 'bg-slate-400'" :style="{ width: `${totalSeverity ? (row.value / totalSeverity) * 100 : 0}%` }"></div>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else title="Belum ada kasus" description="Tidak ada insiden negatif pada periode ini." />
                </section>

                <section class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kategori terbanyak</p>
                            <p class="mt-0.5 text-xs text-slate-500">Kelompok dengan kurang dari {{ report.threshold }} catatan disembunyikan</p>
                        </div>
                        <span v-if="report.hidden_category_count" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500">
                            <EyeOff class="h-3 w-3" /> {{ report.hidden_category_count }} disembunyikan
                        </span>
                    </div>

                    <div v-if="report.categories.length" class="mt-5 space-y-3">
                        <div v-for="category in report.categories" :key="category.name">
                            <div class="flex items-center justify-between gap-2 text-[11px]">
                                <span class="font-medium text-slate-700">{{ category.name }}</span>
                                <span class="font-bold text-slate-800">{{ category.value }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-600" :style="{ width: `${(category.value / maxCategory) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else title="Kategori belum dapat ditampilkan" description="Semua kategori berada di bawah ambang kelompok kecil atau belum ada data." />
                </section>
            </div>

            <div class="space-y-5">
                <section class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Indikator pengasuhan</p>
                    <dl class="mt-4 space-y-3">
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                            <dt class="flex items-center gap-2 text-xs text-slate-500"><Users class="h-4 w-4 text-slate-400" /> Kehadiran kelas</dt>
                            <dd class="text-sm font-bold text-slate-800">{{ summary['Kehadiran kelas'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                            <dt class="flex items-center gap-2 text-xs text-slate-500"><Timer class="h-4 w-4 text-slate-400" /> Rata-rata penyelesaian</dt>
                            <dd class="text-sm font-bold text-slate-800">{{ summary['Rata-rata penyelesaian'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                            <dt class="flex items-center gap-2 text-xs text-slate-500"><ShieldCheck class="h-4 w-4 text-slate-400" /> Kasus sangat rahasia</dt>
                            <dd class="text-sm font-bold text-slate-800">{{ summary['Kasus sangat rahasia'] }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><ShieldCheck class="h-4 w-4" /> Catatan interpretasi</p>
                    <ul class="mt-2 space-y-2 text-[11px] leading-relaxed text-brand-900/75">
                        <li v-for="note in report.notes" :key="note" class="flex gap-1.5">
                            <span class="mt-1 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
                            <span>{{ note }}</span>
                        </li>
                    </ul>
                </section>

                <section class="card p-5">
                    <p class="flex items-center gap-2 text-sm font-bold text-slate-800"><Info class="h-4 w-4 text-slate-400" /> Tentang laporan</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                        Data dihitung dari kasus yang dapat Anda akses. Angka dapat berbeda dengan laporan kepala sekolah bila Anda memiliki akses terbatas.
                    </p>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                        Ekspor CSV hanya memuat agregat tanpa nama atau kode siswa. Setiap ekspor dicatat pada log audit.
                    </p>
                </section>

                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-amber-900"><EyeOff class="h-4 w-4" /> Perlindungan identitas</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-amber-900/80">
                        Jangan memfilter laporan secara manual hingga menyisakan satu atau dua anak. Gunakan laporan ini hanya untuk evaluasi program, bukan labeling siswa.
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
