<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BedDouble,
    Building2,
    CalendarDays,
    CheckCircle2,
    GraduationCap,
    Loader2,
    MapPin,
    Pencil,
    Plus,
    Save,
    School,
    Users,
} from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Modal from '../../../Components/Modal.vue';
import EmptyState from '../../../Components/EmptyState.vue';

const props = defineProps({
    academicYears: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    dorms: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
    teachers: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({ genders: {} }) },
});

const tab = ref('academic-years');
const modalOpen = ref(false);
const editing = ref(null);
const editingType = ref(null);

const academicYearForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
    is_active: false,
});

const levelForm = useForm({
    code: '',
    name: '',
    sort_order: '',
    is_active: true,
});

const classroomForm = useForm({
    name: '',
    level_id: '',
    academic_year_id: '',
    homeroom_teacher_id: '',
    is_active: true,
});

const dormForm = useForm({
    name: '',
    gender: 'L',
    capacity: 40,
    wali_id: '',
    is_active: true,
});

const locationForm = useForm({
    name: '',
    sort_order: '',
    is_active: true,
});

const tabs = computed(() => [
    { id: 'academic-years', label: 'Tahun Ajaran', icon: CalendarDays, count: props.academicYears.length },
    { id: 'levels', label: 'Jenjang', icon: GraduationCap, count: props.levels.length },
    { id: 'classrooms', label: 'Kelas', icon: School, count: props.classrooms.length },
    { id: 'dorms', label: 'Asrama', icon: Building2, count: props.dorms.length },
    { id: 'locations', label: 'Lokasi Insiden', icon: MapPin, count: props.locations.length },
]);

const activeCounts = computed(() => ({
    classrooms: props.classrooms.filter((item) => item.is_active).length,
    dorms: props.dorms.filter((item) => item.is_active).length,
    students: props.classrooms.reduce((total, item) => total + item.students_count, 0),
}));

const activeAcademicYear = computed(() => props.academicYears.find((item) => item.is_active));

const forms = {
    'academic-years': academicYearForm,
    levels: levelForm,
    classrooms: classroomForm,
    dorms: dormForm,
    locations: locationForm,
};

const activeForm = computed(() => forms[tab.value]);
const tabLabel = computed(() => tabs.value.find((item) => item.id === tab.value)?.label ?? 'Data');

function resetForm(type) {
    if (type === 'academic-years') {
        academicYearForm.reset();
        academicYearForm.clearErrors();
        academicYearForm.is_active = false;
    }

    if (type === 'levels') {
        levelForm.reset();
        levelForm.clearErrors();
        levelForm.is_active = true;
    }

    if (type === 'classrooms') {
        classroomForm.reset();
        classroomForm.clearErrors();
        classroomForm.is_active = true;
    }

    if (type === 'dorms') {
        dormForm.reset();
        dormForm.clearErrors();
        dormForm.gender = 'L';
        dormForm.capacity = 40;
        dormForm.is_active = true;
    }

    if (type === 'locations') {
        locationForm.reset();
        locationForm.clearErrors();
        locationForm.is_active = true;
    }
}

function openCreate() {
    editing.value = null;
    editingType.value = tab.value;
    resetForm(tab.value);
    modalOpen.value = true;
}

function openEdit(type, item) {
    editing.value = item;
    editingType.value = type;
    tab.value = type;
    resetForm(type);

    if (type === 'academic-years') {
        academicYearForm.name = item.name;
        academicYearForm.start_date = item.start_date ?? '';
        academicYearForm.end_date = item.end_date ?? '';
        academicYearForm.is_active = Boolean(item.is_active);
    }

    if (type === 'levels') {
        levelForm.code = item.code;
        levelForm.name = item.name;
        levelForm.sort_order = item.sort_order ?? '';
        levelForm.is_active = Boolean(item.is_active);
    }

    if (type === 'classrooms') {
        classroomForm.name = item.name;
        classroomForm.level_id = item.level_id ?? '';
        classroomForm.academic_year_id = item.academic_year_id ?? '';
        classroomForm.homeroom_teacher_id = item.homeroom_teacher_id ?? '';
        classroomForm.is_active = Boolean(item.is_active);
    }

    if (type === 'dorms') {
        dormForm.name = item.name;
        dormForm.gender = item.gender;
        dormForm.capacity = item.capacity;
        dormForm.wali_id = item.wali_id ?? '';
        dormForm.is_active = Boolean(item.is_active);
    }

    if (type === 'locations') {
        locationForm.name = item.name;
        locationForm.sort_order = item.sort_order ?? '';
        locationForm.is_active = Boolean(item.is_active);
    }

    modalOpen.value = true;
}

function submit() {
    const form = activeForm.value;
    const type = editingType.value ?? tab.value;
    const item = editing.value;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
            editing.value = null;
        },
    };

    if (type === 'academic-years') {
        item ? form.patch(`/admin/master/academic-years/${item.id}`, options) : form.post('/admin/master/academic-years', options);
    }

    if (type === 'levels') {
        item ? form.patch(`/admin/master/levels/${item.id}`, options) : form.post('/admin/master/levels', options);
    }

    if (type === 'classrooms') {
        item ? form.patch(`/admin/master/classrooms/${item.id}`, options) : form.post('/admin/master/classrooms', options);
    }

    if (type === 'dorms') {
        item ? form.patch(`/admin/master/dorms/${item.id}`, options) : form.post('/admin/master/dorms', options);
    }

    if (type === 'locations') {
        item ? form.patch(`/admin/master/locations/${item.id}`, options) : form.post('/admin/master/locations', options);
    }
}

function activateAcademicYear(item) {
    router.patch(`/admin/master/academic-years/${item.id}/activate`, {}, { preserveScroll: true });
}

function toggleLevel(item) {
    router.patch(`/admin/master/levels/${item.id}`, {
        code: item.code,
        name: item.name,
        sort_order: item.sort_order,
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function toggleClassroom(item) {
    router.patch(`/admin/master/classrooms/${item.id}`, {
        name: item.name,
        level_id: item.level_id,
        academic_year_id: item.academic_year_id,
        homeroom_teacher_id: item.homeroom_teacher_id,
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function toggleDorm(item) {
    router.patch(`/admin/master/dorms/${item.id}`, {
        name: item.name,
        gender: item.gender,
        capacity: item.capacity,
        wali_id: item.wali_id,
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function toggleLocation(item) {
    router.patch(`/admin/master/locations/${item.id}`, {
        name: item.name,
        sort_order: item.sort_order,
        is_active: !item.is_active,
    }, { preserveScroll: true });
}

function formatDate(value) {
    if (!value) return 'â€”';
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}
</script>

<template>
    <Head title="Data master" />

    <AppLayout>
        <PageHeader
            title="Data master"
            description="Kelola struktur akademik, asrama, dan lokasi insiden yang dipakai di seluruh aplikasi."
            eyebrow="Administrasi"
        >
            <template #actions>
                <button type="button" class="btn-primary" @click="openCreate">
                    <Plus class="h-4 w-4" /> Tambah {{ tabLabel }}
                </button>
            </template>
        </PageHeader>

        <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tahun ajaran aktif</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-brand-700">
                    <CheckCircle2 class="h-4 w-4" /> {{ activeAcademicYear?.name ?? 'Belum ditetapkan' }}
                </p>
            </div>
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kelas aktif</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-slate-800"><School class="h-4 w-4 text-slate-400" /> {{ activeCounts.classrooms }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Asrama aktif</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-slate-800"><BedDouble class="h-4 w-4 text-slate-400" /> {{ activeCounts.dorms }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Siswa terdaftar</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-slate-800"><Users class="h-4 w-4 text-slate-400" /> {{ activeCounts.students }}</p>
            </div>
        </div>

        <div class="mb-4 flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1">
            <button
                v-for="item in tabs"
                :key="item.id"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition"
                :class="tab === item.id ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
                @click="tab = item.id"
            >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.label }}
                <span class="rounded-full px-1.5 py-0.5 text-[10px]" :class="tab === item.id ? 'bg-white/20' : 'bg-slate-100 text-slate-500'">{{ item.count }}</span>
            </button>
        </div>

        <section v-if="tab === 'academic-years'" class="card overflow-hidden">
            <div v-if="academicYears.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Tahun ajaran</th>
                            <th class="px-4 py-2.5">Periode</th>
                            <th class="px-4 py-2.5">Kelas</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in academicYears" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ item.name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDate(item.start_date) }} â€“ {{ formatDate(item.end_date) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.classrooms_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="item.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button v-if="!item.is_active" type="button" class="mr-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-50" @click="activateAcademicYear(item)">Aktifkan</button>
                                <button type="button" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit('academic-years', item)"><Pencil class="mr-1 inline h-3.5 w-3.5" /> Ubah</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada tahun ajaran" description="Tambahkan tahun ajaran sebelum membuat kelas." />
        </section>

        <section v-if="tab === 'levels'" class="card overflow-hidden">
            <div v-if="levels.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Kode</th>
                            <th class="px-4 py-2.5">Jenjang</th>
                            <th class="px-4 py-2.5">Urutan</th>
                            <th class="px-4 py-2.5">Kelas</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in levels" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-700">{{ item.code }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ item.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ item.sort_order }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.classrooms_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <button type="button" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'" @click="toggleLevel(item)">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit('levels', item)"><Pencil class="mr-1 inline h-3.5 w-3.5" /> Ubah</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada jenjang" description="Tambahkan jenjang seperti SD, SMP, atau SMA." />
        </section>

        <section v-if="tab === 'classrooms'" class="card overflow-hidden">
            <div v-if="classrooms.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Kelas</th>
                            <th class="px-4 py-2.5">Jenjang</th>
                            <th class="px-4 py-2.5">Tahun ajaran</th>
                            <th class="px-4 py-2.5">Wali kelas</th>
                            <th class="px-4 py-2.5">Siswa</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in classrooms" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ item.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.level_name ?? item.level ?? 'â€”' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.academic_year_name ?? item.academic_year ?? 'â€”' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.homeroom_teacher ?? 'â€”' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.students_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <button type="button" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'" @click="toggleClassroom(item)">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit('classrooms', item)"><Pencil class="mr-1 inline h-3.5 w-3.5" /> Ubah</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada kelas" description="Tambahkan kelas dan hubungkan dengan jenjang serta tahun ajaran." />
        </section>

        <section v-if="tab === 'dorms'" class="card overflow-hidden">
            <div v-if="dorms.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Asrama</th>
                            <th class="px-4 py-2.5">Gender</th>
                            <th class="px-4 py-2.5">Kapasitas</th>
                            <th class="px-4 py-2.5">Wali asrama</th>
                            <th class="px-4 py-2.5">Siswa</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in dorms" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ item.name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ options.genders[item.gender] ?? item.gender }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.students_count }} / {{ item.capacity }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.wali ?? 'â€”' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.students_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <button type="button" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'" @click="toggleDorm(item)">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit('dorms', item)"><Pencil class="mr-1 inline h-3.5 w-3.5" /> Ubah</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada asrama" description="Tambahkan asrama dan wali asrama untuk mengelola pembinaan." />
        </section>

        <section v-if="tab === 'locations'" class="card overflow-hidden">
            <div v-if="locations.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Lokasi</th>
                            <th class="px-4 py-2.5">Urutan</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in locations" :key="item.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ item.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ item.sort_order }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <button type="button" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="item.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200'" @click="toggleLocation(item)">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit('locations', item)"><Pencil class="mr-1 inline h-3.5 w-3.5" /> Ubah</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada lokasi insiden" description="Tambahkan lokasi agar laporan insiden dapat dikategorikan." />
        </section>

        <p class="mt-3 text-[11px] text-slate-400">
            Nonaktifkan data yang tidak dipakai. Data historis tetap tersimpan dan tidak dihapus.
        </p>

        <Modal
            :show="modalOpen"
            :title="editing ? `Ubah ${tabLabel}` : `Tambah ${tabLabel}`"
            description="Perubahan dicatat pada jejak audit dan langsung berlaku di seluruh aplikasi."
            @close="modalOpen = false"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <template v-if="(editingType ?? tab) === 'academic-years'">
                    <div>
                        <label class="label" for="year-name">Tahun ajaran</label>
                        <input id="year-name" v-model="academicYearForm.name" type="text" class="field" placeholder="2026/2027" required />
                        <p v-if="academicYearForm.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ academicYearForm.errors.name }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="year-start">Tanggal mulai</label>
                            <input id="year-start" v-model="academicYearForm.start_date" type="date" class="field" />
                            <p v-if="academicYearForm.errors.start_date" class="mt-1 text-xs font-medium text-rose-600">{{ academicYearForm.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="label" for="year-end">Tanggal selesai</label>
                            <input id="year-end" v-model="academicYearForm.end_date" type="date" class="field" />
                            <p v-if="academicYearForm.errors.end_date" class="mt-1 text-xs font-medium text-rose-600">{{ academicYearForm.errors.end_date }}</p>
                        </div>
                    </div>
                    <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3">
                        <input v-model="academicYearForm.is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
                        <span>
                            <span class="text-xs font-semibold text-slate-800">Jadikan tahun ajaran aktif</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">Hanya satu tahun ajaran yang dapat aktif.</span>
                        </span>
                    </label>
                </template>

                <template v-if="(editingType ?? tab) === 'levels'">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="level-code">Kode</label>
                            <input id="level-code" v-model="levelForm.code" type="text" class="field" placeholder="SMP" required />
                            <p v-if="levelForm.errors.code" class="mt-1 text-xs font-medium text-rose-600">{{ levelForm.errors.code }}</p>
                        </div>
                        <div>
                            <label class="label" for="level-name">Nama jenjang</label>
                            <input id="level-name" v-model="levelForm.name" type="text" class="field" placeholder="Sekolah Menengah Pertama" required />
                            <p v-if="levelForm.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ levelForm.errors.name }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="level-order">Urutan tampil</label>
                        <input id="level-order" v-model="levelForm.sort_order" type="number" min="0" max="999" class="field" placeholder="Otomatis di akhir" />
                        <p v-if="levelForm.errors.sort_order" class="mt-1 text-xs font-medium text-rose-600">{{ levelForm.errors.sort_order }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input v-model="levelForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" /> Aktif
                    </label>
                </template>

                <template v-if="(editingType ?? tab) === 'classrooms'">
                    <div>
                        <label class="label" for="classroom-name">Nama kelas</label>
                        <input id="classroom-name" v-model="classroomForm.name" type="text" class="field" placeholder="VII A" required />
                        <p v-if="classroomForm.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ classroomForm.errors.name }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="classroom-level">Jenjang</label>
                            <select id="classroom-level" v-model="classroomForm.level_id" class="field" required>
                                <option value="" disabled>Pilih jenjang</option>
                                <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
                            </select>
                            <p v-if="classroomForm.errors.level_id" class="mt-1 text-xs font-medium text-rose-600">{{ classroomForm.errors.level_id }}</p>
                        </div>
                        <div>
                            <label class="label" for="classroom-year">Tahun ajaran</label>
                            <select id="classroom-year" v-model="classroomForm.academic_year_id" class="field">
                                <option value="">Tidak ditentukan</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                            </select>
                            <p v-if="classroomForm.errors.academic_year_id" class="mt-1 text-xs font-medium text-rose-600">{{ classroomForm.errors.academic_year_id }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="classroom-teacher">Wali kelas</label>
                        <select id="classroom-teacher" v-model="classroomForm.homeroom_teacher_id" class="field">
                            <option value="">Belum ditentukan</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} Â· {{ teacher.role_label }}</option>
                        </select>
                        <p v-if="classroomForm.errors.homeroom_teacher_id" class="mt-1 text-xs font-medium text-rose-600">{{ classroomForm.errors.homeroom_teacher_id }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input v-model="classroomForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" /> Aktif
                    </label>
                </template>

                <template v-if="(editingType ?? tab) === 'dorms'">
                    <div>
                        <label class="label" for="dorm-name">Nama asrama</label>
                        <input id="dorm-name" v-model="dormForm.name" type="text" class="field" placeholder="Asrama Cempaka" required />
                        <p v-if="dormForm.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ dormForm.errors.name }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="dorm-gender">Gender</label>
                            <select id="dorm-gender" v-model="dormForm.gender" class="field" required>
                                <option v-for="(label, value) in options.genders" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <p v-if="dormForm.errors.gender" class="mt-1 text-xs font-medium text-rose-600">{{ dormForm.errors.gender }}</p>
                        </div>
                        <div>
                            <label class="label" for="dorm-capacity">Kapasitas</label>
                            <input id="dorm-capacity" v-model="dormForm.capacity" type="number" min="1" max="200" class="field" required />
                            <p v-if="dormForm.errors.capacity" class="mt-1 text-xs font-medium text-rose-600">{{ dormForm.errors.capacity }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="dorm-wali">Wali asrama</label>
                        <select id="dorm-wali" v-model="dormForm.wali_id" class="field">
                            <option value="">Belum ditentukan</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} Â· {{ teacher.role_label }}</option>
                        </select>
                        <p v-if="dormForm.errors.wali_id" class="mt-1 text-xs font-medium text-rose-600">{{ dormForm.errors.wali_id }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input v-model="dormForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" /> Aktif
                    </label>
                </template>

                <template v-if="(editingType ?? tab) === 'locations'">
                    <div>
                        <label class="label" for="location-name">Nama lokasi</label>
                        <input id="location-name" v-model="locationForm.name" type="text" class="field" placeholder="Kantin sekolah" required />
                        <p v-if="locationForm.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ locationForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label" for="location-order">Urutan tampil</label>
                        <input id="location-order" v-model="locationForm.sort_order" type="number" min="0" max="999" class="field" placeholder="Otomatis di akhir" />
                        <p v-if="locationForm.errors.sort_order" class="mt-1 text-xs font-medium text-rose-600">{{ locationForm.errors.sort_order }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input v-model="locationForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" /> Aktif
                    </label>
                </template>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="btn-secondary" @click="modalOpen = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="activeForm.processing">
                        <Loader2 v-if="activeForm.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ activeForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

