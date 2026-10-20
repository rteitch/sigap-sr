<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarCheck2,
    CalendarDays,
    CheckCheck,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Loader2,
    Save,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { attendanceClass, formatDate, initials } from '../../lib/labels';

const props = defineProps({
    classrooms: { type: Array, default: () => [] },
    dorms: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    isDormType: { type: Boolean, default: false },
    selectedClassroom: { type: Object, default: null },
    selectedDorm: { type: Object, default: null },
    students: { type: Array, default: () => [] },
    summary: { type: Array, default: () => [] },
    dormTypes: { type: Array, default: () => [] },
});

const page = usePage();
const options = computed(() => page.props.options);

const form = useForm({
    date: props.filters.date,
    type: props.filters.type,
    classroom_id: props.filters.classroom_id,
    dorm_id: props.filters.dorm_id,
    records: [],
});

const filters = reactive({ ...props.filters });
const rows = ref([]);

watch(
    () => props.filters,
    (value) => Object.assign(filters, value),
    { deep: true },
);

watch(
    () => props.students,
    (students) => {
        rows.value = students.map((student) => ({
            ...student,
            status: student.status ?? 'hadir',
            notes: student.notes ?? '',
        }));
        form.date = props.filters.date;
        form.type = props.filters.type;
        form.classroom_id = props.filters.classroom_id;
        form.dorm_id = props.filters.dorm_id;
    },
    { immediate: true, deep: true },
);

const localSummary = computed(() => {
    const counts = {};
    rows.value.forEach((row) => {
        counts[row.status] = (counts[row.status] ?? 0) + 1;
    });
    return Object.entries(options.value.attendance_statuses).map(([status, label]) => ({
        status,
        label,
        count: counts[status] ?? 0,
    }));
});

const absentCount = computed(() => rows.value.filter((row) => row.status === 'alpa').length);
const savedCount = computed(() => rows.value.filter((row) => row.saved).length);
const hasNotesMissing = computed(() =>
    rows.value.some((row) => ['sakit', 'izin', 'alpa', 'dispensasi', 'pulang_berizin'].includes(row.status) && !row.notes.trim()),
);

function applyFilters(patch = {}) {
    router.get(
        '/presensi',
        { ...filters, ...patch },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function shiftDate(days) {
    const date = new Date(`${filters.date}T00:00:00`);
    date.setDate(date.getDate() + days);
    const iso = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    filters.date = iso;
    applyFilters({ date: iso });
}

function markAllPresent() {
    rows.value.forEach((row) => {
        row.status = 'hadir';
    });
}

function submit() {
    form.records = rows.value.map((row) => ({
        student_id: row.id,
        status: row.status,
        notes: row.notes || null,
    }));
    form.post('/presensi', { preserveScroll: true });
}
</script>

<template>
    <Head title="Presensi" />

    <AppLayout>
        <PageHeader
            title="Presensi"
            description="Catat kehadiran kelas dan kegiatan asrama. Status alpa pada kegiatan asrama otomatis membuat insiden siswa tidak ditemukan."
            eyebrow="Pengasuhan harian"
        >
            <template #actions>
                <button type="button" class="btn-secondary" @click="markAllPresent"><CheckCheck class="h-4 w-4" /> Tandai semua hadir</button>
                <button type="button" class="btn-primary" :disabled="form.processing || !rows.length" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ form.processing ? 'Menyimpan…' : 'Simpan presensi' }}
                </button>
            </template>
        </PageHeader>

        <section class="card p-4">
            <div class="grid gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="label">Tanggal</label>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="btn-secondary shrink-0 !px-2.5" aria-label="Hari sebelumnya" @click="shiftDate(-1)"><ChevronLeft class="h-4 w-4" /></button>
                        <input v-model="filters.date" type="date" class="field min-w-[135px] flex-1" @change="applyFilters({ date: filters.date })" />
                        <button type="button" class="btn-secondary shrink-0 !px-2.5" aria-label="Hari berikutnya" @click="shiftDate(1)"><ChevronRight class="h-4 w-4" /></button>
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <label class="label">Jenis presensi</label>
                    <select v-model="filters.type" class="field" @change="applyFilters({ type: filters.type })">
                        <option v-for="(label, value) in options.attendance_types" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div v-if="!isDormType" class="lg:col-span-2">
                    <label class="label">Kelas</label>
                    <select v-model="filters.classroom_id" class="field" @change="applyFilters({ classroom_id: filters.classroom_id })">
                        <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                    </select>
                </div>
                <div v-else class="lg:col-span-2">
                    <label class="label">Asrama</label>
                    <select v-model="filters.dorm_id" class="field" @change="applyFilters({ dorm_id: filters.dorm_id })">
                        <option v-for="dorm in dorms" :key="dorm.id" :value="dorm.id">{{ dorm.name }}</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="label">Ringkasan hari ini</label>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="item in localSummary" :key="item.status" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold ring-1" :class="attendanceClass(item.status)">
                            {{ item.label }} {{ item.count }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <p class="flex items-center gap-1.5">
                <CalendarDays class="h-4 w-4 text-slate-400" />
                {{ formatDate(filters.date) }} ·
                <span class="font-semibold text-slate-700">{{ isDormType ? selectedDorm?.name : selectedClassroom?.name }}</span>
                · {{ rows.length }} siswa
            </p>
            <p>
                <span class="font-semibold text-slate-700">{{ savedCount }}</span> sudah tersimpan · {{ rows.length - savedCount }} belum
            </p>
        </div>

        <div v-if="isDormType && absentCount" class="mt-3 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs leading-relaxed text-rose-800">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            {{ absentCount }} siswa berstatus alpa. Saat disimpan, sistem akan membuat insiden "Kabur / tidak ditemukan" dan memberi tahu kepala sekolah.
        </div>

        <section class="card mt-4 overflow-hidden">
            <div v-if="rows.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="w-10 px-4 py-2.5">No</th>
                            <th class="px-4 py-2.5">Siswa</th>
                            <th class="px-4 py-2.5">Penempatan</th>
                            <th class="px-4 py-2.5">Status kehadiran</th>
                            <th class="w-64 px-4 py-2.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in rows" :key="row.id" class="align-top transition hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-slate-400">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="relative h-8 w-8 shrink-0 overflow-hidden rounded-full ring-1 ring-slate-200">
                                        <img v-if="row.photo_url" :src="row.photo_url" :alt="row.name" class="h-full w-full object-cover" />
                                        <span v-else class="grid h-full w-full place-items-center bg-teal-50 text-[10px] font-bold text-teal-700">
                                            {{ initials(row.name) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800">{{ row.name }}</p>
                                        <div class="mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono text-[10px] text-slate-400">{{ row.code }}</span>
                                            <span v-if="row.saved" class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700">
                                                Tersimpan
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                {{ isDormType ? row.dorm : row.classroom }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="(label, status) in options.attendance_statuses"
                                        :key="status"
                                        type="button"
                                        class="rounded-lg px-2 py-1 text-[10px] font-bold ring-1 transition"
                                        :class="row.status === status ? attendanceClass(status) : 'border-transparent bg-white text-slate-500 ring-slate-200 hover:bg-slate-50'"
                                        @click="row.status = status"
                                    >
                                        {{ label }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    v-model="row.notes"
                                    type="text"
                                    class="field !py-1.5 text-xs"
                                    :placeholder="row.status === 'hadir' ? 'Opsional' : 'Wajib dijelaskan singkat'"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                title="Belum ada siswa"
                description="Tidak ada siswa aktif pada kelas atau asrama ini. Periksa penempatan siswa terlebih dahulu."
            >
                <template #action>
                    <Link href="/siswa" class="btn-secondary"><Users class="h-4 w-4" /> Kelola siswa</Link>
                </template>
            </EmptyState>

            <div v-if="rows.length" class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p v-if="hasNotesMissing" class="flex items-center gap-1.5 text-[11px] text-amber-700">
                    <AlertTriangle class="h-3.5 w-3.5" /> Beberapa status belum memiliki catatan. Catatan membantu tindak lanjut.
                </p>
                <p v-else class="text-[11px] text-slate-500">Periksa kembali sebelum menyimpan. Data tersimpan dapat diperbarui pada tanggal yang sama.</p>
                <button type="button" class="btn-primary shrink-0" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ form.processing ? 'Menyimpan…' : 'Simpan presensi' }}
                </button>
            </div>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><CalendarCheck2 class="h-3.5 w-3.5" /> Presensi kelas</p>
                Dicatat oleh wali kelas atau guru yang mengajar. Status alpa menjadi perhatian wali kelas.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><ClipboardList class="h-3.5 w-3.5" /> Kegiatan asrama</p>
                Apel pagi, makan, apel malam, dan tidur dicatat oleh wali asrama sesuai jadwal piket.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><AlertTriangle class="h-3.5 w-3.5" /> Eskalasi otomatis</p>
                Alpa pada kegiatan asrama memicu insiden dan notifikasi agar pencarian tidak tertunda.
            </div>
        </div>
    </AppLayout>
</template>
