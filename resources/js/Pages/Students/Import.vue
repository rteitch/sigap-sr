<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    Info,
    Loader2,
    ShieldCheck,
    UploadCloud,
    XCircle,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';

const props = defineProps({
    preview: { type: Object, default: null },
});

const page = usePage();
const fileInput = ref(null);
const dragging = ref(false);

const form = useForm({ file: null });
const confirmForm = useForm({});

const hasPreview = computed(() => Boolean(props.preview?.rows?.length));
const validRows = computed(() => props.preview?.rows?.filter((row) => row.errors.length === 0) ?? []);
const invalidRows = computed(() => props.preview?.rows?.filter((row) => row.errors.length > 0) ?? []);

function pickFile() {
    fileInput.value?.click();
}

function setFile(file) {
    if (!file) return;
    form.file = file;
}

function onFileChange(event) {
    setFile(event.target.files?.[0] ?? null);
}

function onDrop(event) {
    dragging.value = false;
    setFile(event.dataTransfer.files?.[0] ?? null);
}

function upload() {
    if (!form.file) return;
    form.post('/siswa/import/pratinjau', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function confirmImport() {
    confirmForm.post('/siswa/import/konfirmasi', { preserveScroll: true });
}
</script>

<template>
    <Head title="Impor siswa" />

    <AppLayout>
        <PageHeader
            title="Impor data siswa"
            description="Unggah berkas Excel/CSV, periksa pratinjau, lalu konfirmasi. Baris tidak valid tidak akan diimpor."
            eyebrow="Data induk"
        >
            <template #actions>
                <Link href="/siswa" class="btn-secondary"><ArrowLeft class="h-4 w-4" /> Kembali ke daftar</Link>
                <a href="/siswa/import/templat" class="btn-primary"><Download class="h-4 w-4" /> Unduh templat</a>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><UploadCloud class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Unggah berkas</p>
                            <p class="text-[11px] text-slate-500">Format .xlsx, .xls, atau .csv · maksimal 4 MB</p>
                        </div>
                    </div>

                    <div
                        class="mt-4 cursor-pointer rounded-2xl border-2 border-dashed p-8 text-center transition"
                        :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-slate-200 bg-slate-50/60 hover:border-brand-300 hover:bg-brand-50/40'"
                        role="button"
                        tabindex="0"
                        @click="pickFile"
                        @keydown.enter="pickFile"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="onDrop"
                    >
                        <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileChange" />
                        <FileSpreadsheet class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 text-sm font-semibold text-slate-700">
                            {{ form.file ? form.file.name : 'Tarik berkas ke sini atau klik untuk memilih' }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">Gunakan templat agar nama kolom sesuai.</p>
                    </div>
                    <p v-if="form.errors.file" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.file }}</p>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <a href="/siswa/import/templat" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:underline">
                            <Download class="h-3.5 w-3.5" /> Belum punya templat? Unduh di sini
                        </a>
                        <button type="button" class="btn-primary" :disabled="!form.file || form.processing" @click="upload">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <UploadCloud v-else class="h-4 w-4" />
                            {{ form.processing ? 'Membaca berkas…' : 'Pratinjau berkas' }}
                        </button>
                    </div>
                </section>

                <section v-if="hasPreview" class="card overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Pratinjau impor</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ preview.rows.length }} baris terbaca · {{ validRows.length }} valid · {{ invalidRows.length }} perlu diperbaiki
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                <CheckCircle2 class="h-3 w-3" /> {{ preview.valid_count }} siap
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200">
                                <XCircle class="h-3 w-3" /> {{ preview.invalid_count }} gagal
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-4 py-2.5">Baris</th>
                                    <th class="px-4 py-2.5">Kode</th>
                                    <th class="px-4 py-2.5">Nama</th>
                                    <th class="px-4 py-2.5">JK</th>
                                    <th class="px-4 py-2.5">Lahir</th>
                                    <th class="px-4 py-2.5">Kelas</th>
                                    <th class="px-4 py-2.5">Asrama</th>
                                    <th class="px-4 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="row in preview.rows" :key="row.row" :class="row.errors.length ? 'bg-rose-50/40' : ''">
                                    <td class="px-4 py-2.5 font-mono text-slate-500">{{ row.row }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 font-mono text-slate-700">{{ row.code || '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <p class="font-semibold text-slate-800">{{ row.full_name || '—' }}</p>
                                        <ul v-if="row.errors.length" class="mt-1 space-y-0.5">
                                            <li v-for="error in row.errors" :key="error" class="text-[10px] font-medium text-rose-600">• {{ error }}</li>
                                        </ul>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ row.gender || '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">{{ row.birth_date || '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">{{ row.classroom_name || '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">{{ row.dorm_name || '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5">
                                        <CheckCircle2 v-if="!row.errors.length" class="h-4 w-4 text-emerald-500" />
                                        <AlertTriangle v-else class="h-4 w-4 text-rose-500" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-start gap-1.5 text-[11px] leading-relaxed text-slate-500">
                            <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            Hanya {{ validRows.length }} baris valid yang akan dibuat. Persetujuan data dicatat sebagai "Impor operator" dan perlu dilengkapi kemudian.
                        </p>
                        <button type="button" class="btn-primary shrink-0" :disabled="confirmForm.processing || validRows.length === 0" @click="confirmImport">
                            <Loader2 v-if="confirmForm.processing" class="h-4 w-4 animate-spin" />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            {{ confirmForm.processing ? 'Mengimpor…' : `Impor ${validRows.length} siswa` }}
                        </button>
                    </div>
                </section>

                <section v-else class="card p-5">
                    <p class="flex items-center gap-2 text-sm font-bold text-slate-800"><FileSpreadsheet class="h-4 w-4 text-slate-400" /> Format berkas</p>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        Baris pertama harus berisi nama kolom. Kolom wajib:
                    </p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span v-for="column in ['kode', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'kelas', 'asrama', 'status']" :key="column" class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[10px] font-semibold text-slate-600">{{ column }}</span>
                    </div>
                    <ul class="mt-3 space-y-1.5 text-[11px] leading-relaxed text-slate-500">
                        <li>• <strong class="text-slate-700">jenis_kelamin</strong> diisi <span class="font-mono">L</span> atau <span class="font-mono">P</span>.</li>
                        <li>• <strong class="text-slate-700">tanggal_lahir</strong> gunakan format <span class="font-mono">YYYY-MM-DD</span>.</li>
                        <li>• Nama kelas dan asrama harus sama persis dengan data yang ada.</li>
                        <li>• Kode siswa tidak boleh duplikat dengan data yang sudah tersimpan.</li>
                    </ul>
                </section>
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Alur impor</p>
                    <ol class="mt-4 space-y-3">
                        <li v-for="(step, index) in ['Unduh templat terbaru', 'Isi data tanpa data sensitif', 'Unggah dan periksa pratinjau', 'Konfirmasi baris yang valid']" :key="step" class="flex gap-3">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-50 text-[10px] font-bold text-brand-700">{{ index + 1 }}</span>
                            <p class="pt-0.5 text-xs text-slate-600">{{ step }}</p>
                        </li>
                    </ol>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-amber-800"><AlertTriangle class="h-4 w-4" /> Jangan unggah data sensitif</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-amber-800/80">
                        Berkas impor tidak boleh memuat catatan insiden, diagnosis, atau riwayat keluarga. Data tersebut dicatat melalui alur terbatas di dalam aplikasi.
                    </p>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><ShieldCheck class="h-4 w-4" /> Jejak audit</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-brand-900/70">
                        Setiap impor dicatat pada log audit: siapa, kapan, jumlah baris berhasil, dan jumlah baris dilewati.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
