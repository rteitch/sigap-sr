<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Download,
    Eye,
    FileSpreadsheet,
    Filter,
    Plus,
    Search,
    ShieldCheck,
    Upload,
    UserRound,
    Users,
    X,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Modal from '../../Components/Modal.vue';
import { attendanceClass, initials, studentStatusClass } from '../../lib/labels';

const props = defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    classrooms: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const page = usePage();
const options = computed(() => page.props.options);

const filter = reactive({
    q: props.filters.q ?? '',
    classroom_id: props.filters.classroom_id ?? '',
    status: props.filters.status ?? '',
});

const exportModal = ref(false);
const exportReason = ref('');
const exportError = ref('');

function applyFilters() {
    router.get(
        '/siswa',
        { q: filter.q || undefined, classroom_id: filter.classroom_id || undefined, status: filter.status || undefined },
        { preserveState: true, replace: true },
    );
}

function resetFilters() {
    filter.q = '';
    filter.classroom_id = '';
    filter.status = '';
    applyFilters();
}

function runExport() {
    exportError.value = '';
    if (exportReason.value.trim().length < 5) {
        exportError.value = 'Tuliskan alasan ekspor minimal 5 karakter agar tercatat di audit.';
        return;
    }
    exportModal.value = false;
    window.location.href = `/siswa/ekspor?reason=${encodeURIComponent(exportReason.value.trim())}`;
    exportReason.value = '';
}

const hasFilters = computed(() => filter.q || filter.classroom_id || filter.status);
</script>

<template>
    <Head title="Data siswa" />

    <AppLayout>
        <PageHeader
            title="Data siswa"
            description="Identitas dipisahkan dari data analisis. Akses ke profil dan ekspor tercatat di jejak audit."
            eyebrow="Data induk"
        >
            <template #actions>
                <template v-if="canManage">
                    <Link href="/siswa/import" class="btn-secondary">
                        <Upload class="h-4 w-4" /> Impor
                    </Link>
                    <button type="button" class="btn-secondary" @click="exportModal = true">
                        <Download class="h-4 w-4" /> Ekspor
                    </button>
                    <Link href="/siswa/create" class="btn-primary">
                        <Plus class="h-4 w-4" /> Tambah siswa
                    </Link>
                </template>
            </template>
        </PageHeader>

        <div class="card mb-5 p-4">
            <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_180px_170px_auto]" @submit.prevent="applyFilters">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input v-model="filter.q" type="search" class="field pl-10" placeholder="Cari nama atau kode siswa…" />
                </div>
                <select v-model="filter.classroom_id" class="field">
                    <option value="">Semua kelas</option>
                    <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                </select>
                <select v-model="filter.status" class="field">
                    <option value="">Semua status</option>
                    <option v-for="(label, value) in options.student_statuses" :key="value" :value="value">{{ label }}</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">
                        <Filter class="h-4 w-4" /> Terapkan
                    </button>
                    <button v-if="hasFilters" type="button" class="btn-secondary !px-3" title="Reset filter" @click="resetFilters">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <Users class="h-4 w-4 text-brand-700" />
                    Daftar siswa
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ students.total }} siswa</span>
            </div>

            <div v-if="students.data.length" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 sm:px-5">Siswa</th>
                            <th class="whitespace-nowrap px-4 py-3">Kelas &amp; asrama</th>
                            <th class="whitespace-nowrap px-4 py-3">Status</th>
                            <th class="whitespace-nowrap px-4 py-3">Persetujuan</th>
                            <th class="whitespace-nowrap px-4 py-3">Kontak utama</th>
                            <th class="px-4 py-3 text-right sm:px-5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="student in students.data" :key="student.id" class="transition hover:bg-slate-50/80">
                            <td class="px-4 py-3 sm:px-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full" :class="student.gender === 'P' ? 'bg-rose-50 text-rose-600' : 'bg-sky-50 text-sky-600'">
                                        <span class="text-[11px] font-bold">{{ initials(student.name) }}</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ student.name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ student.code }} · {{ options.genders[student.gender] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-semibold text-slate-700">{{ student.classroom ?? 'Belum ditempatkan' }}</p>
                                <p class="text-[11px] text-slate-400">{{ student.dorm ?? 'Tanpa asrama' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="studentStatusClass(student.status)">
                                    {{ options.student_statuses[student.status] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="student.consent_active" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                    <ShieldCheck class="h-3.5 w-3.5" /> Aktif
                                </span>
                                <span v-else class="text-[11px] font-semibold text-amber-600">Belum/tidak aktif</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-medium text-slate-700">{{ student.primary_contact?.name ?? '—' }}</p>
                                <p class="text-[11px] text-slate-400">{{ student.primary_contact?.phone ?? 'Tanpa kontak' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right sm:px-5">
                                <Link :href="`/siswa/${student.id}`" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50">
                                    <Eye class="h-3.5 w-3.5" /> Profil
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                title="Siswa tidak ditemukan"
                description="Ubah kata kunci atau filter. Jika data belum ada, tambahkan siswa atau impor dari berkas."
            >
                <template #icon><UserRound class="h-6 w-6" /></template>
                <template v-if="canManage" #action>
                    <Link href="/siswa/create" class="btn-primary"><Plus class="h-4 w-4" /> Tambah siswa</Link>
                </template>
            </EmptyState>

            <Pagination :paginator="students" />
        </div>

        <div class="mt-4 flex items-start gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[11px] leading-relaxed text-slate-500">
            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
            Hanya staf yang berwenang yang dapat melihat identitas siswa. Data ekspor wajib memiliki alasan yang tercatat dan tidak boleh dibagikan melalui grup chat.
        </div>

        <Modal :show="exportModal" title="Ekspor data siswa" description="Ekspor memuat identitas. Alasan akan dicatat di jejak audit." @close="exportModal = false">
            <div class="space-y-4">
                <div>
                    <label class="label" for="export-reason">Alasan ekspor</label>
                    <textarea id="export-reason" v-model="exportReason" rows="3" class="field" placeholder="Contoh: Pencocokan data dengan Dapodik untuk semester ganjil."></textarea>
                    <p v-if="exportError" class="mt-1.5 text-xs font-medium text-rose-600">{{ exportError }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" @click="exportModal = false">Batal</button>
                    <button type="button" class="btn-primary" @click="runExport"><FileSpreadsheet class="h-4 w-4" /> Unduh XLSX</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
