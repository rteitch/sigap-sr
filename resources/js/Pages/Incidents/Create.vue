<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertOctagon,
    AlertTriangle,
    ArrowLeft,
    CalendarClock,
    CheckCircle2,
    EyeOff,
    HeartPulse,
    Info,
    Loader2,
    MapPin,
    Plus,
    Send,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Trash2,
    UserRound,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { confidentialityClass, severityClass } from '../../lib/labels';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
    prefillStudentId: { type: Number, default: null },
});

const page = usePage();
const options = computed(() => page.props.options);

function localDateTime() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}

const initialParticipants = props.prefillStudentId && props.students.some((student) => student.id === props.prefillStudentId)
    ? [{ student_id: props.prefillStudentId, role: 'korban', notes: '' }]
    : [{ student_id: '', role: 'korban', notes: '' }];

const form = useForm({
    category_id: '',
    severity: 1,
    occurred_at: localDateTime(),
    location_id: '',
    description: '',
    initial_action: '',
    needs_medical: false,
    participants: initialParticipants,
});

const selectedCategory = computed(() => props.categories.find((category) => category.id === form.category_id));
const isPositive = computed(() => Boolean(selectedCategory.value?.is_positive));
const confidentiality = computed(() => selectedCategory.value?.default_confidentiality ?? 'normal');
const showSensitive = ref(Boolean(props.prefillStudentId));

const negativeCategories = computed(() => props.categories.filter((category) => !category.is_positive));
const positiveCategories = computed(() => props.categories.filter((category) => category.is_positive));

const availableStudents = computed(() => {
    const selected = form.participants.map((participant) => participant.student_id).filter(Boolean);
    return props.students.map((student) => ({
        ...student,
        disabled: selected.includes(student.id),
    }));
});

const dueHours = computed(() => options.value.response_due_hours?.[form.severity] ?? 72);
const descriptionCount = computed(() => form.description.trim().length);

watch(
    () => form.category_id,
    () => {
        if (isPositive.value) {
            form.severity = 1;
            form.needs_medical = false;
        }
    },
);

function addParticipant() {
    form.participants.push({ student_id: '', role: 'saksi', notes: '' });
}

function removeParticipant(index) {
    form.participants.splice(index, 1);
}

function submit() {
    form.post('/insiden', { preserveScroll: true });
}

function studentLabel(student) {
    return `${student.name} · ${student.code} · ${student.classroom ?? '—'}`;
}
</script>

<template>
    <Head title="Laporkan insiden" />

    <AppLayout>
        <PageHeader
            title="Laporkan insiden"
            description="Tulis fakta yang teramati. Jangan memberi label pada anak. Kasus berat dan sangat rahasia otomatis dialihkan ke jalur terbatas."
            eyebrow="Pengasuhan dan pembinaan"
        >
            <template #actions>
                <Link href="/insiden" class="btn-secondary"><ArrowLeft class="h-4 w-4" /> Batal</Link>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Send v-else class="h-4 w-4" />
                    {{ form.processing ? 'Mengirim…' : 'Kirim laporan' }}
                </button>
            </template>
        </PageHeader>

        <form class="grid gap-5 xl:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><AlertOctagon class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kategori dan waktu</p>
                            <p class="text-[11px] text-slate-500">Pilih kategori yang paling mendekati. Kategori menentukan kerahasiaan awal.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label" for="category">Kategori</label>
                            <select id="category" v-model="form.category_id" class="field" required>
                                <option value="" disabled>Pilih kategori…</option>
                                <optgroup v-if="negativeCategories.length" label="Insiden / kekhawatiran">
                                    <option v-for="category in negativeCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </optgroup>
                                <optgroup v-if="positiveCategories.length" label="Apresiasi / perilaku positif">
                                    <option v-for="category in positiveCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.category_id }}</p>
                            <div v-if="selectedCategory" class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="confidentialityClass(confidentiality)">
                                    <EyeOff class="h-2.5 w-2.5" /> {{ options.confidentialities[confidentiality] }}
                                </span>
                                <span v-if="isPositive" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                    <Sparkles class="h-2.5 w-2.5" /> Apresiasi
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="label" for="occurred_at">Waktu kejadian</label>
                            <input id="occurred_at" v-model="form.occurred_at" type="datetime-local" class="field" required />
                            <p v-if="form.errors.occurred_at" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.occurred_at }}</p>
                        </div>
                        <div>
                            <label class="label" for="location">Lokasi</label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <select id="location" v-model="form.location_id" class="field pl-10 pr-10" required>
                                    <option value="" disabled>Pilih lokasi…</option>
                                    <option v-for="location in locations" :key="location.id" :value="location.id">{{ location.name }}</option>
                                </select>
                            </div>
                            <p v-if="form.errors.location_id" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.location_id }}</p>
                            <p v-else-if="!locations.length" class="mt-1.5 text-xs font-medium text-amber-600">
                                Belum ada lokasi aktif. Hubungi administrator untuk menambah data master.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700"><ShieldAlert class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Tingkat keparahan</p>
                            <p class="text-[11px] text-slate-500">Tingkat 3–4 langsung ditugaskan ke kepala sekolah.</p>
                        </div>
                    </div>

                    <div v-if="isPositive" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">
                        <p class="flex items-center gap-1.5 font-bold"><Sparkles class="h-4 w-4" /> Catatan apresiasi</p>
                        <p class="mt-1 leading-relaxed">Kategori positif selalu tercatat sebagai Tingkat 1 dan tidak memerlukan tindakan medis.</p>
                    </div>

                    <div v-else class="mt-4 grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="(label, level) in options.incident_severities"
                            :key="level"
                            type="button"
                            class="rounded-xl border p-3 text-left transition"
                            :class="Number(form.severity) === Number(level) ? 'border-brand-400 bg-brand-50/70 ring-1 ring-brand-300' : 'border-slate-200 bg-white hover:border-slate-300'"
                            @click="form.severity = Number(level)"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="severityClass(Number(level))">{{ label }}</span>
                                <span v-if="Number(form.severity) === Number(level)" class="grid h-4 w-4 place-items-center rounded-full bg-brand-700 text-white"><CheckCircle2 class="h-3 w-3" /></span>
                            </div>
                            <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">{{ options.severity_descriptions[level] }}</p>
                        </button>
                    </div>
                    <p v-if="form.errors.severity" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.severity }}</p>

                    <div v-if="!isPositive" class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-500">
                        <span class="inline-flex items-center gap-1.5"><CalendarClock class="h-3.5 w-3.5" /> Tenggat respons</span>
                        <span class="font-bold text-slate-700">{{ dueHours }} jam</span>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Deskripsi kejadian</p>
                            <p class="text-[11px] text-slate-500">Tulis apa yang dilihat, didengar, dan dilakukan. Hindari kesimpulan atau label.</p>
                        </div>
                        <span class="text-[10px] font-semibold" :class="descriptionCount < 10 ? 'text-rose-500' : 'text-slate-400'">{{ descriptionCount }}/5000</span>
                    </div>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        class="field mt-3"
                        placeholder="Contoh: Saat apel pagi, A terlihat menangis di barisan belakang. Saya mendekat dan A mengatakan…"
                        required
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.description }}</p>

                    <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50/50 p-3">
                        <p class="flex items-center gap-1.5 text-[11px] font-bold text-brand-800"><Info class="h-3.5 w-3.5" /> Gunakan bahasa fakta</p>
                        <div class="mt-1.5 grid gap-1 text-[10px] leading-relaxed text-brand-900/70 sm:grid-cols-2">
                            <p>✓ "A terdiam dan menolak masuk kelas."</p>
                            <p>✗ "A malas dan bandel."</p>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Tindakan awal</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Apa yang sudah dilakukan untuk menjaga keselamatan dan menenangkan anak?</p>
                    <textarea v-model="form.initial_action" rows="3" class="field mt-3" placeholder="Contoh: Anak diajak ke ruang BK, diberi air minum, dan wali kelas dihubungi."></textarea>

                    <label class="mt-4 flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 p-3 transition hover:bg-slate-50">
                        <input v-model="form.needs_medical" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500" :disabled="isPositive" />
                        <span>
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-800"><HeartPulse class="h-3.5 w-3.5 text-rose-500" /> Memerlukan pemeriksaan medis</span>
                            <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">Centang jika ada luka, keluhan fisik, atau kebutuhan layanan kesehatan segera.</span>
                        </span>
                    </label>
                </section>

                <section class="card p-5">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Siswa yang terlibat</p>
                            <p class="text-[11px] text-slate-500">Tentukan peran setiap siswa. Minimal satu siswa.</p>
                        </div>
                        <button type="button" class="btn-secondary !px-2.5 !py-1.5 text-xs" @click="addParticipant"><Plus class="h-3.5 w-3.5" /> Tambah</button>
                    </div>
                    <p v-if="form.errors.participants" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.participants }}</p>

                    <div class="mt-3 space-y-3">
                        <div v-for="(participant, index) in form.participants" :key="index" class="rounded-xl border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Siswa {{ index + 1 }}</p>
                                <button v-if="form.participants.length > 1" type="button" class="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Hapus siswa" aria-label="Hapus siswa dari daftar" @click="removeParticipant(index)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <div class="mt-2 grid gap-2 sm:grid-cols-5">
                                <div class="sm:col-span-3">
                                    <label class="label">Siswa</label>
                                    <select v-model="participant.student_id" class="field">
                                        <option value="" disabled>Pilih siswa…</option>
                                        <option v-for="student in availableStudents" :key="student.id" :value="student.id" :disabled="student.disabled && student.id !== participant.student_id">
                                            {{ studentLabel(student) }}
                                        </option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label">Peran</label>
                                    <select v-model="participant.role" class="field">
                                        <option v-for="(label, value) in options.incident_roles" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </div>
                            </div>
                            <input v-model="participant.notes" type="text" class="field mt-2 !py-1.5 text-xs" placeholder="Catatan peran (opsional)" />
                            <p v-if="form.errors[`participants.${index}.student_id`]" class="mt-1 text-xs font-medium text-rose-600">{{ form.errors[`participants.${index}.student_id`] }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Ringkasan laporan</p>
                    <dl class="mt-4 space-y-3 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kategori</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ selectedCategory?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kerahasiaan</dt>
                            <dd>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="confidentialityClass(confidentiality)">{{ options.confidentialities[confidentiality] }}</span>
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Tingkat</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ isPositive ? 'Apresiasi' : options.incident_severities[form.severity] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Siswa terlibat</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ form.participants.filter((p) => p.student_id).length }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Medis</dt>
                            <dd class="text-right font-semibold" :class="form.needs_medical ? 'text-rose-600' : 'text-slate-800'">{{ form.needs_medical ? 'Diperlukan' : 'Tidak' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            {{ form.processing ? 'Mengirim laporan…' : 'Kirim laporan' }}
                        </button>
                        <p class="mt-2 text-center text-[10px] text-slate-400">Laporan tercatat atas nama {{ page.props.auth.user.name }}</p>
                    </div>
                </div>

                <div v-if="confidentiality === 'sangat_rahasia'" class="rounded-2xl border border-violet-200 bg-violet-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-violet-900"><EyeOff class="h-4 w-4" /> Jalur sangat rahasia</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-violet-900/80">
                        Kasus ini hanya dapat diakses kepala sekolah, TPPK, dan guru BK. Jika peran Anda bukan salah satunya, detail kasus tidak akan ditampilkan setelah dikirim.
                    </p>
                </div>

                <div v-else-if="confidentiality === 'rahasia'" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-amber-900"><ShieldAlert class="h-4 w-4" /> Kasus rahasia</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-amber-900/80">
                        Hanya staf yang berkepentingan dan terlibat yang dapat membuka detail kasus. Jangan membagikan isi laporan melalui grup chat.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-slate-800"><ShieldCheck class="h-4 w-4 text-emerald-600" /> Setelah dikirim</p>
                    <ul class="mt-2 space-y-1.5 text-[11px] leading-relaxed text-slate-500">
                        <li>• Nomor kasus dibuat otomatis.</li>
                        <li>• Langkah protokol awal ditambahkan sebagai daftar periksa.</li>
                        <li>• Penanggung jawab dan notifikasi ditentukan sesuai tingkat.</li>
                        <li>• Tindak lanjut minggu 1, 2, dan 4 dijadwalkan.</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-rose-900"><AlertTriangle class="h-4 w-4" /> Situasi darurat</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-rose-900/80">
                        Jika ada ancaman nyawa atau kekerasan sedang berlangsung, utamakan keselamatan dan hubungi layanan darurat. Catat insiden setelah situasi aman.
                    </p>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
