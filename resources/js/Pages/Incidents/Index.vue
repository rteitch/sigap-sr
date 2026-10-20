<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertOctagon,
    AlertTriangle,
    CalendarClock,
    CalendarDays,
    ClipboardList,
    EyeOff,
    Filter,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Sparkles,
    Timer,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import Pagination from '../../Components/Pagination.vue';
import EmptyState from '../../Components/EmptyState.vue';
import {
    confidentialityClass,
    formatDateTime,
    severityClass,
    statusClass,
} from '../../lib/labels';

const props = defineProps({
    incidents: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
});

const page = usePage();
const options = computed(() => page.props.options);

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    severity: props.filters.severity ?? '',
    category_id: props.filters.category_id ?? '',
    overdue: props.filters.overdue ?? '',
});

function apply() {
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null));
    router.get('/insiden', params, { preserveState: true, preserveScroll: true, replace: true });
}

function reset() {
    Object.assign(filters, { q: '', status: '', severity: '', category_id: '', overdue: '' });
    router.get('/insiden', {}, { preserveState: false, replace: true });
}
</script>

<template>
    <Head title="Insiden" />

    <AppLayout>
        <PageHeader
            title="Insiden"
            description="Catatan kasus, protokol, dan tindak lanjut. Akses dibatasi sesuai peran dan tingkat kerahasiaan."
            eyebrow="Pengasuhan dan pembinaan"
        >
            <template #actions>
                <Link href="/insiden/buat" class="btn-primary"><Plus class="h-4 w-4" /> Laporkan insiden</Link>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Kasus terbuka" :value="stats.open" hint="Belum selesai" :icon="ClipboardList" tone="brand" />
            <StatCard label="Melewati tenggat" :value="stats.overdue" hint="Perlu respons segera" :icon="Timer" tone="rose" />
            <StatCard label="Urgent (T3–T4)" :value="stats.urgent" hint="Kasus terbuka" :icon="ShieldAlert" tone="amber" />
            <StatCard label="Bulan ini" :value="stats.this_month" hint="Total laporan masuk" :icon="CalendarDays" tone="sky" />
        </div>

        <section class="card mt-4 p-4">
            <div class="grid gap-3 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label class="label" for="search">Cari</label>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input id="search" v-model="filters.q" type="search" class="field !pl-9" placeholder="Nomor kasus atau nama siswa…" @keydown.enter="apply" />
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <label class="label">Status</label>
                    <select v-model="filters.status" class="field" @change="apply">
                        <option value="">Semua status</option>
                        <option v-for="(label, value) in options.incident_statuses" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="label">Severity</label>
                    <select v-model="filters.severity" class="field" @change="apply">
                        <option value="">Semua tingkat</option>
                        <option v-for="(label, value) in options.incident_severities" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="label">Kategori</label>
                    <select v-model="filters.category_id" class="field" @change="apply">
                        <option value="">Semua kategori</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-600">
                    <input v-model="filters.overdue" type="checkbox" true-value="1" false-value="" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" @change="apply" />
                    Tampilkan hanya yang melewati tenggat
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" @click="reset"><RotateCcw class="h-3.5 w-3.5" /> Reset</button>
                    <button type="button" class="btn-primary !px-3 !py-1.5 text-xs" @click="apply"><Filter class="h-3.5 w-3.5" /> Terapkan</button>
                </div>
            </div>
        </section>

        <section class="card mt-4 overflow-hidden">
            <div v-if="incidents.data.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Kasus</th>
                            <th class="px-4 py-2.5">Kategori</th>
                            <th class="px-4 py-2.5">Tingkat</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Kejadian</th>
                            <th class="px-4 py-2.5">Penanggung jawab</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="incident in incidents.data" :key="incident.id" class="group transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-4 py-3">
                                <Link :href="`/insiden/${incident.id}`" class="font-mono text-[11px] font-semibold text-brand-700 group-hover:underline">{{ incident.case_number }}</Link>
                                <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-400">
                                    <Users class="h-3 w-3" /> {{ incident.participants_count }} siswa
                                    <span v-if="incident.overdue" class="inline-flex items-center gap-0.5 rounded-full bg-rose-50 px-1.5 py-0.5 font-bold text-rose-700"><Timer class="h-2.5 w-2.5" /> terlambat</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ incident.category }}</p>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span v-if="incident.is_positive" class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700"><Sparkles class="h-2.5 w-2.5" /> Apresiasi</span>
                                    <span v-if="incident.confidentiality !== 'normal'" class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[9px] font-bold ring-1" :class="confidentialityClass(incident.confidentiality)">
                                        <EyeOff class="h-2.5 w-2.5" /> {{ options.confidentialities[incident.confidentiality] }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="severityClass(incident.severity)">T{{ incident.severity }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="statusClass(incident.status)">{{ options.incident_statuses[incident.status] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                <p class="inline-flex items-center gap-1"><CalendarClock class="h-3 w-3 text-slate-400" /> {{ formatDateTime(incident.occurred_at) }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ incident.location }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-700">{{ incident.assignee ?? 'Belum ditugaskan' }}</p>
                                <p class="text-[10px] text-slate-400">{{ incident.assignee_role ?? '—' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                title="Tidak ada insiden"
                description="Tidak ada kasus yang cocok dengan filter atau Anda tidak memiliki akses ke kasus yang ada."
            >
                <template #action>
                    <Link href="/insiden/buat" class="btn-primary"><Plus class="h-4 w-4" /> Laporkan insiden</Link>
                </template>
            </EmptyState>

            <Pagination :paginator="incidents" />
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><AlertOctagon class="h-3.5 w-3.5 text-rose-500" /> Tingkat 3–4</p>
                Otomatis ditugaskan kepada kepala sekolah dan memicu notifikasi. Respons maksimal 24 jam.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><EyeOff class="h-3.5 w-3.5 text-violet-500" /> Sangat rahasia</p>
                Hanya kepala sekolah, TPPK, dan guru BK. Setiap pembukaan detail dicatat dalam audit.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><AlertTriangle class="h-3.5 w-3.5 text-amber-500" /> Prinsip catatan</p>
                Tulis fakta yang teramati, hindari label. Fokus pada kebutuhan anak dan langkah pemulihan.
            </div>
        </div>
    </AppLayout>
</template>
