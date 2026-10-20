<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    DoorOpen,
    Home,
    Loader2,
    Moon,
    PlusCircle,
    Search,
    ShieldAlert,
    Sun,
    Sunrise,
    UserRound,
    Utensils,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Modal from '../../Components/Modal.vue';
import { attendanceClass, formatDate, formatDateTime, initials } from '../../lib/labels';

const props = defineProps({
    dorms: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    dorm: { type: Object, required: true },
    students: { type: Array, default: () => [] },
    activitySummary: { type: Array, default: () => [] },
    logs: { type: Array, default: () => [] },
    missingStudents: { type: Array, default: () => [] },
});

const page = usePage();
const options = computed(() => page.props.options);
const filters = reactive({ ...props.filters });
const showLogModal = ref(false);

const typeMeta = {
    apel_pagi: { icon: Sunrise, tone: 'bg-amber-50 text-amber-700', label: 'Pagi' },
    makan: { icon: Utensils, tone: 'bg-emerald-50 text-emerald-700', label: 'Makan' },
    apel_malam: { icon: Moon, tone: 'bg-indigo-50 text-indigo-700', label: 'Malam' },
    tidur: { icon: Moon, tone: 'bg-slate-100 text-slate-700', label: 'Tidur' },
};

const totalStudents = computed(() => props.students.length);
const recordedToday = computed(() => {
    if (!props.students.length) return 0;
    const cells = props.students.flatMap((student) => Object.values(student.statuses ?? {}).filter(Boolean));
    return cells.length;
});
const recordedPercent = computed(() =>
    totalStudents.value ? Math.round((recordedToday.value / (totalStudents.value * (options.value.dorm_attendance_types.length || 1))) * 100) : 0,
);

const logForm = useForm({
    dorm_id: props.dorm.id,
    log_date: props.filters.date,
    shift: 'malam',
    checkpoints: [],
    notes: '',
    handover_notes: '',
});

watch(
    () => props.filters,
    (value) => {
        Object.assign(filters, value);
        logForm.log_date = value.date;
    },
    { deep: true },
);

watch(
    () => props.dorm.id,
    (id) => {
        logForm.dorm_id = id;
    },
);

function applyFilters(patch = {}) {
    router.get('/asrama', { ...filters, ...patch }, { preserveState: true, preserveScroll: true, replace: true });
}

function toggleCheckpoint(checkpoint) {
    const index = logForm.checkpoints.indexOf(checkpoint);
    if (index >= 0) {
        logForm.checkpoints.splice(index, 1);
    } else {
        logForm.checkpoints.push(checkpoint);
    }
}

function submitLog() {
    logForm.post('/asrama/logbook', {
        preserveScroll: true,
        onSuccess: () => {
            showLogModal.value = false;
            logForm.reset('checkpoints', 'notes', 'handover_notes');
        },
    });
}

function statusLabel(status) {
    return status ? options.value.attendance_statuses[status] : 'Belum';
}
</script>

<template>
    <Head :title="`Asrama ${dorm.name}`" />

    <AppLayout>
        <PageHeader
            title="Asrama"
            :description="`${dorm.name} · ${options.genders[dorm.gender]} · Wali asrama: ${dorm.wali ?? 'belum ditentukan'}`"
            eyebrow="Pengasuhan harian"
        >
            <template #actions>
                <Link :href="`/presensi?type=apel_malam&dorm_id=${dorm.id}&date=${filters.date}`" class="btn-secondary">
                    <ClipboardList class="h-4 w-4" /> Presensi asrama
                </Link>
                <button type="button" class="btn-primary" @click="showLogModal = true"><PlusCircle class="h-4 w-4" /> Catat logbook</button>
            </template>
        </PageHeader>

        <section class="card p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="label">Asrama</label>
                    <select v-model="filters.dorm_id" class="field" @change="applyFilters({ dorm_id: filters.dorm_id })">
                        <option v-for="item in dorms" :key="item.id" :value="item.id">{{ item.name }} · {{ options.genders[item.gender] }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">Tanggal</label>
                    <input v-model="filters.date" type="date" class="field" @change="applyFilters({ date: filters.date })" />
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Penghuni aktif</p>
                    <p class="mt-1 flex items-center gap-1.5 text-lg font-bold text-slate-800"><UserRound class="h-4 w-4 text-slate-400" /> {{ totalStudents }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Keterisian presensi</p>
                    <div class="mt-1 flex items-center gap-2">
                        <p class="text-lg font-bold text-slate-800">{{ recordedPercent }}%</p>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-brand-600" :style="{ width: `${recordedPercent}%` }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="missingStudents.length" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700"><ShieldAlert class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-rose-800">{{ missingStudents.length }} siswa tidak ditemukan pada apel malam/tidur</p>
                    <p class="mt-1 text-xs leading-relaxed text-rose-700">
                        Segera lakukan pemeriksaan lokasi dan hubungi kepala sekolah bila tidak ditemukan dalam 120 menit.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <Link v-for="student in missingStudents" :key="student.id" :href="`/siswa/${student.id}`" class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100">
                            {{ student.name }} ({{ student.code }})
                        </Link>
                    </div>
                </div>
                <Link :href="`/presensi?type=apel_malam&dorm_id=${dorm.id}&date=${filters.date}`" class="btn-secondary shrink-0 !border-rose-200 !text-rose-700 hover:!bg-rose-100">
                    <Search class="h-4 w-4" /> Periksa
                </Link>
            </div>
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="activity in activitySummary"
                :key="activity.type"
                :href="`/presensi?type=${activity.type}&dorm_id=${dorm.id}&date=${filters.date}`"
                class="card group p-4 transition hover:border-brand-200 hover:shadow-sm"
            >
                <div class="flex items-start justify-between">
                    <span class="grid h-9 w-9 place-items-center rounded-xl" :class="typeMeta[activity.type]?.tone ?? 'bg-slate-100 text-slate-700'">
                        <component :is="typeMeta[activity.type]?.icon ?? CalendarDays" class="h-4.5 w-4.5" />
                    </span>
                    <span class="text-[10px] font-semibold text-slate-400 group-hover:text-brand-700">Buka presensi →</span>
                </div>
                <p class="mt-3 text-sm font-bold text-slate-800">{{ activity.label }}</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-xl font-bold text-emerald-600">{{ activity.present }}</span>
                    <span class="text-[11px] text-slate-400">hadir</span>
                    <span v-if="activity.absent" class="ml-auto inline-flex items-center gap-0.5 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                        <AlertTriangle class="h-2.5 w-2.5" /> {{ activity.absent }} alpa
                    </span>
                </div>
                <p class="mt-1 text-[10px] text-slate-400">{{ activity.recorded }} dari {{ totalStudents }} tercatat</p>
            </Link>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-3">
            <section class="card overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Matriks kehadiran kegiatan</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ formatDate(filters.date) }} · apel pagi, makan, apel malam, tidur</p>
                    </div>
                    <Home class="h-4 w-4 text-slate-300" />
                </div>
                <div v-if="students.length" class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="table-head">
                            <tr>
                                <th class="px-4 py-2.5">Siswa</th>
                                <th class="px-4 py-2.5">Kelas</th>
                                <th v-for="type in options.dorm_attendance_types" :key="type" class="whitespace-nowrap px-3 py-2.5 text-center">
                                    {{ options.attendance_types[type] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="student in students" :key="student.id" class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <Link :href="`/siswa/${student.id}`" class="font-semibold text-slate-800 hover:text-brand-700">{{ student.name }}</Link>
                                    <p class="font-mono text-[10px] text-slate-400">{{ student.code }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ student.classroom ?? '—' }}</td>
                                <td v-for="type in options.dorm_attendance_types" :key="type" class="px-3 py-3 text-center">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="student.statuses[type] ? attendanceClass(student.statuses[type]) : 'bg-white text-slate-300 ring-slate-200'">
                                        {{ statusLabel(student.statuses[type]) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="Asrama belum memiliki siswa" description="Tambahkan siswa ke asrama ini melalui data induk." />
            </section>

            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Logbook piket</p>
                        <p class="mt-0.5 text-xs text-slate-500">Shift, titik periksa, dan serah terima</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-brand-700 hover:bg-brand-50" aria-label="Tambah logbook" @click="showLogModal = true">
                        <PlusCircle class="h-5 w-5" />
                    </button>
                </div>
                <div v-if="logs.length" class="max-h-[520px] divide-y divide-slate-100 overflow-y-auto">
                    <div v-for="log in logs" :key="log.id" class="px-5 py-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600">
                                    <Sunrise v-if="log.shift === 'pagi'" class="h-4 w-4" />
                                    <Sun v-else-if="log.shift === 'siang'" class="h-4 w-4" />
                                    <Moon v-else class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-slate-800">{{ options.dorm_shifts[log.shift] }}</p>
                                    <p class="text-[10px] text-slate-400">{{ log.officer ?? 'Petugas' }} · {{ formatDateTime(log.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="log.checkpoints.length" class="mt-2 flex flex-wrap gap-1">
                            <span v-for="checkpoint in log.checkpoints" :key="checkpoint" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700">
                                <CheckCircle2 class="h-2.5 w-2.5" /> {{ checkpoint }}
                            </span>
                        </div>
                        <p v-if="log.notes" class="mt-2 text-[11px] leading-relaxed text-slate-600">{{ log.notes }}</p>
                        <p v-if="log.handover_notes" class="mt-2 rounded-lg bg-amber-50 p-2 text-[10px] leading-relaxed text-amber-800">
                            <strong class="font-bold">Serah terima:</strong> {{ log.handover_notes }}
                        </p>
                    </div>
                </div>
                <EmptyState v-else title="Belum ada logbook" description="Catat hasil piket asrama pada tanggal ini." />
            </section>
        </div>

        <Modal :show="showLogModal" title="Catat logbook piket" :description="`${dorm.name} · ${formatDate(filters.date)}`" max-width="xl" @close="showLogModal = false">
            <form class="space-y-4" @submit.prevent="submitLog">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="label" for="shift">Shift</label>
                        <select id="shift" v-model="logForm.shift" class="field">
                            <option v-for="(label, value) in options.dorm_shifts" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="log_date">Tanggal</label>
                        <input id="log_date" v-model="logForm.log_date" type="date" class="field" :max="page.props.today" />
                        <p v-if="logForm.errors.log_date" class="mt-1 text-xs font-medium text-rose-600">{{ logForm.errors.log_date }}</p>
                    </div>
                </div>

                <div>
                    <label class="label">Titik periksa</label>
                    <div class="grid gap-1.5 sm:grid-cols-2">
                        <label v-for="checkpoint in options.dorm_checkpoints" :key="checkpoint" class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-[11px] text-slate-600 transition hover:bg-slate-50">
                            <input type="checkbox" :checked="logForm.checkpoints.includes(checkpoint)" class="h-3.5 w-3.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500" @change="toggleCheckpoint(checkpoint)" />
                            {{ checkpoint }}
                        </label>
                    </div>
                </div>

                <div>
                    <label class="label" for="notes">Catatan piket</label>
                    <textarea id="notes" v-model="logForm.notes" rows="3" class="field" placeholder="Kondisi asrama, kejadian, atau temuan."></textarea>
                </div>
                <div>
                    <label class="label" for="handover">Serah terima</label>
                    <textarea id="handover" v-model="logForm.handover_notes" rows="2" class="field" placeholder="Informasi untuk petugas shift berikutnya."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="btn-secondary" @click="showLogModal = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="logForm.processing">
                        <Loader2 v-if="logForm.processing" class="h-4 w-4 animate-spin" />
                        <PlusCircle v-else class="h-4 w-4" />
                        {{ logForm.processing ? 'Menyimpan…' : 'Simpan logbook' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
