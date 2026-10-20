<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CalendarClock,
    CheckCircle2,
    ClipboardCheck,
    ClipboardList,
    Clock3,
    Copy,
    Eye,
    EyeOff,
    FileText,
    HeartPulse,
    History,
    Loader2,
    MapPin,
    MessageSquareText,
    Printer,
    Save,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Timer,
    UserCheck,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Modal from '../../Components/Modal.vue';
import EmptyState from '../../Components/EmptyState.vue';
import {
    confidentialityClass,
    formatDate,
    formatDateTime,
    initials,
    severityClass,
    statusClass,
} from '../../lib/labels';

const props = defineProps({
    incident: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    canManageConfidential: { type: Boolean, default: false },
    assignableUsers: { type: Array, default: () => [] },
    audit: { type: Array, default: () => [] },
});

const page = usePage();
const options = computed(() => page.props.options);
const copied = ref(false);
const showFollowupModal = ref(false);
const activeFollowup = ref(null);

function toLocalInput(iso) {
    if (!iso) return '';
    const date = new Date(iso);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
}

const updateForm = useForm({
    status: props.incident.status,
    assigned_to: props.incident.assignee?.id ?? '',
    due_at: toLocalInput(props.incident.due_at),
    sanction: props.incident.sanction ?? '',
    resolution_notes: props.incident.resolution_notes ?? '',
});

const followupForm = useForm({ notes: '' });

const tasksDone = computed(() => props.incident.tasks.filter((task) => task.is_done).length);
const taskProgress = computed(() =>
    props.incident.tasks.length ? Math.round((tasksDone.value / props.incident.tasks.length) * 100) : 0,
);

const pendingFollowups = computed(() => props.incident.followups.filter((followup) => followup.status !== 'done').length);

const isSecret = computed(() => props.incident.confidentiality === 'sangat_rahasia');
const isRestricted = computed(() => props.incident.confidentiality !== 'normal');

function printPage() {
    window.print();
}

async function copyCaseNumber() {
    try {
        await navigator.clipboard.writeText(props.incident.case_number);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1800);
    } catch {
        copied.value = false;
    }
}

function toggleTask(task) {
    router.patch(`/insiden/${props.incident.id}/tugas/${task.id}`, {}, { preserveScroll: true });
}

function openFollowup(followup) {
    activeFollowup.value = followup;
    followupForm.reset();
    showFollowupModal.value = true;
}

function completeFollowup() {
    if (!activeFollowup.value) return;
    followupForm.patch(`/insiden/${props.incident.id}/tindak-lanjut/${activeFollowup.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showFollowupModal.value = false;
            activeFollowup.value = null;
        },
    });
}

function saveUpdate() {
    updateForm.patch(`/insiden/${props.incident.id}`, { preserveScroll: true });
}

const followupStatusLabel = { pending: 'Menunggu', done: 'Selesai' };
</script>

<template>
    <Head :title="`Kasus ${incident.case_number}`" />

    <AppLayout>
        <PageHeader
            :title="incident.category"
            :description="`${incident.case_number} · ${formatDateTime(incident.occurred_at)} · ${incident.location}`"
            eyebrow="Detail kasus"
        >
            <template #actions>
                <Link href="/insiden" class="btn-secondary"><ArrowLeft class="h-4 w-4" /> Kembali</Link>
                <button type="button" class="btn-secondary" @click="copyCaseNumber">
                    <CheckCircle2 v-if="copied" class="h-4 w-4 text-emerald-600" />
                    <Copy v-else class="h-4 w-4" /> {{ copied ? 'Tersalin' : 'Salin nomor' }}
                </button>
                <button type="button" class="btn-secondary" @click="printPage"><Printer class="h-4 w-4" /> Cetak</button>
            </template>
        </PageHeader>

        <div v-if="isRestricted" class="mb-4 rounded-2xl border p-4" :class="isSecret ? 'border-violet-200 bg-violet-50' : 'border-amber-200 bg-amber-50'">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="isSecret ? 'bg-violet-100 text-violet-700' : 'bg-amber-100 text-amber-700'">
                    <EyeOff class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-bold" :class="isSecret ? 'text-violet-900' : 'text-amber-900'">
                        Kasus {{ options.confidentialities[incident.confidentiality].toLowerCase() }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed" :class="isSecret ? 'text-violet-900/80' : 'text-amber-900/80'">
                        <template v-if="isSecret">Akses terbatas untuk kepala sekolah, TPPK, dan guru BK. Pembukaan kasus ini dicatat dalam jejak audit.</template>
                        <template v-else>Hanya staf berkepentingan yang dapat membuka detail. Jangan membagikan isi kasus.</template>
                    </p>
                </div>
            </div>
        </div>

        <div v-if="incident.overdue" class="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
            <Timer class="mt-0.5 h-4 w-4 shrink-0" />
            <p><strong class="font-bold">Melewati tenggat respons.</strong> Kasus ini belum selesai dan telah melewati batas waktu yang ditetapkan.</p>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-4">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1" :class="severityClass(incident.severity)">
                            <Sparkles v-if="incident.is_positive" class="h-3 w-3" /> {{ incident.is_positive ? 'Apresiasi' : options.incident_severities[incident.severity] }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1" :class="statusClass(incident.status)">{{ options.incident_statuses[incident.status] }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1" :class="confidentialityClass(incident.confidentiality)">
                            <EyeOff v-if="isRestricted" class="h-3 w-3" /> {{ options.confidentialities[incident.confidentiality] }}
                        </span>
                        <span v-if="incident.needs_medical" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200">
                            <HeartPulse class="h-3 w-3" /> Perlu medis
                        </span>
                        <span class="ml-auto text-[11px] text-slate-400">Dilaporkan {{ formatDateTime(incident.occurred_at) }}</span>
                    </div>

                    <dl class="grid gap-4 border-b border-slate-100 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Pelapor</dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-800"><UserCheck class="h-3.5 w-3.5 text-slate-400" /> {{ incident.reporter?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Penanggung jawab</dt>
                            <dd class="mt-1 text-xs font-semibold text-slate-800">{{ incident.assignee?.name ?? 'Belum ditugaskan' }}</dd>
                            <dd class="text-[10px] text-slate-400">{{ incident.assignee?.role_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Tenggat</dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-xs font-semibold" :class="incident.overdue ? 'text-rose-600' : 'text-slate-800'">
                                <CalendarClock class="h-3.5 w-3.5" /> {{ formatDateTime(incident.due_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lokasi</dt>
                            <dd class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-800"><MapPin class="h-3.5 w-3.5 text-slate-400" /> {{ incident.location }}</dd>
                        </div>
                    </dl>

                    <div class="space-y-5 px-5 py-5">
                        <div>
                            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-400"><FileText class="h-3.5 w-3.5" /> Deskripsi kejadian</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ incident.description }}</p>
                        </div>
                        <div v-if="incident.initial_action">
                            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-400"><ShieldCheck class="h-3.5 w-3.5" /> Tindakan awal</p>
                            <p class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">{{ incident.initial_action }}</p>
                        </div>
                    </div>
                </section>

                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Siswa terlibat</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ incident.participants.length }} siswa tercatat dalam kasus ini</p>
                        </div>
                        <Users class="h-4 w-4 text-slate-300" />
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="participant in incident.participants" :key="participant.id" class="flex flex-col gap-2 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-xs font-bold text-slate-500">{{ initials(participant.student?.name) }}</span>
                                <div>
                                    <Link :href="`/siswa/${participant.student?.id}`" class="text-sm font-semibold text-slate-800 hover:text-brand-700">{{ participant.student?.name ?? '—' }}</Link>
                                    <p class="text-[11px] text-slate-400">{{ participant.student?.code }} · {{ participant.student?.classroom ?? '—' }} · {{ participant.student?.dorm ?? 'Tanpa asrama' }}</p>
                                    <p v-if="participant.notes" class="mt-0.5 text-[11px] text-slate-500">{{ participant.notes }}</p>
                                </div>
                            </div>
                            <span class="w-fit rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ options.incident_roles[participant.role] }}</span>
                        </div>
                    </div>
                </section>

                <section v-if="incident.tasks.length" class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="flex items-center gap-1.5 text-sm font-bold text-slate-800"><ClipboardCheck class="h-4 w-4 text-brand-600" /> Langkah protokol</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ tasksDone }} dari {{ incident.tasks.length }} langkah selesai</p>
                        </div>
                        <span class="text-xs font-bold text-brand-700">{{ taskProgress }}%</span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: `${taskProgress}%` }"></div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <button
                            v-for="task in incident.tasks"
                            :key="task.id"
                            type="button"
                            class="flex w-full items-start gap-2.5 rounded-xl border p-2.5 text-left transition"
                            :class="task.is_done ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 hover:bg-slate-50'"
                            :disabled="!canManage"
                            @click="toggleTask(task)"
                        >
                            <span class="mt-0.5 grid h-4.5 w-4.5 shrink-0 place-items-center rounded-full border" :class="task.is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white'">
                                <CheckCircle2 v-if="task.is_done" class="h-3 w-3" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-medium" :class="task.is_done ? 'text-slate-500 line-through' : 'text-slate-700'">{{ task.label }}</span>
                                <span v-if="task.done_by" class="mt-0.5 block text-[10px] text-slate-400">{{ task.done_by }} · {{ formatDateTime(task.done_at) }}</span>
                            </span>
                        </button>
                    </div>
                    <p v-if="!canManage" class="mt-2 text-[10px] text-slate-400">Hanya penanggung jawab atau staf berwenang yang dapat mengubah langkah protokol.</p>
                </section>

                <section v-if="incident.followups.length" class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="flex items-center gap-1.5 text-sm font-bold text-slate-800"><MessageSquareText class="h-4 w-4 text-brand-600" /> Tindak lanjut</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ pendingFollowups }} dari {{ incident.followups.length }} belum selesai</p>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        <div
                            v-for="followup in incident.followups"
                            :key="followup.id"
                            class="rounded-xl border p-3"
                            :class="followup.status === 'done' ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200'"
                        >
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-xs font-bold text-slate-800">Minggu {{ followup.week }}</p>
                                <span class="rounded-full px-1.5 py-0.5 text-[9px] font-bold" :class="followup.status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                    {{ followupStatusLabel[followup.status] ?? followup.status }}
                                </span>
                            </div>
                            <p class="mt-1 flex items-center gap-1 text-[10px] text-slate-500"><Clock3 class="h-3 w-3" /> {{ formatDate(followup.due_at) }}</p>
                            <p v-if="followup.notes" class="mt-2 text-[11px] leading-relaxed text-slate-600">{{ followup.notes }}</p>
                            <p v-if="followup.done_by" class="mt-1 text-[10px] text-emerald-700">{{ followup.done_by }}</p>
                            <button
                                v-if="canManage && followup.status !== 'done'"
                                type="button"
                                class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-brand-700 hover:underline"
                                @click="openFollowup(followup)"
                            >
                                <CheckCircle2 class="h-3 w-3" /> Selesaikan
                            </button>
                        </div>
                    </div>
                </section>

                <section v-if="canManage && audit.length" class="card overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                        <History class="h-4 w-4 text-slate-400" />
                        <p class="text-sm font-bold text-slate-800">Jejak audit kasus</p>
                    </div>
                    <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                        <div v-for="log in audit" :key="log.id" class="flex items-start gap-3 px-5 py-3">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-300"></span>
                            <div>
                                <p class="text-xs text-slate-600">{{ log.description ?? log.action }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ log.user ?? 'Sistem' }} · {{ formatDateTime(log.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <section v-if="canManage" class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><ClipboardList class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kelola kasus</p>
                            <p class="text-[11px] text-slate-500">Perbarui status, penanggung jawab, dan keputusan.</p>
                        </div>
                    </div>

                    <form class="mt-4 space-y-3" @submit.prevent="saveUpdate">
                        <div>
                            <label class="label" for="status">Status</label>
                            <select id="status" v-model="updateForm.status" class="field">
                                <option v-for="(label, value) in options.incident_statuses" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <p v-if="updateForm.errors.status" class="mt-1 text-xs font-medium text-rose-600">{{ updateForm.errors.status }}</p>
                        </div>
                        <div>
                            <label class="label" for="assignee">Penanggung jawab</label>
                            <select id="assignee" v-model="updateForm.assigned_to" class="field">
                                <option value="">Belum ditugaskan</option>
                                <option v-for="user in assignableUsers" :key="user.id" :value="user.id">{{ user.name }} · {{ user.role_label }}</option>
                            </select>
                            <p v-if="!canManageConfidential && isSecret" class="mt-1 text-[10px] text-amber-700">Penugasan kasus sangat rahasia terbatas pada peran berwenang.</p>
                        </div>
                        <div>
                            <label class="label" for="due">Tenggat respons</label>
                            <input id="due" v-model="updateForm.due_at" type="datetime-local" class="field" />
                        </div>
                        <div>
                            <label class="label" for="sanction">Keputusan / pembinaan</label>
                            <select id="sanction" v-model="updateForm.sanction" class="field">
                                <option value="">Belum ada keputusan</option>
                                <option v-for="(label, value) in options.sanctions" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <p class="mt-1 text-[10px] text-slate-400">Sanksi keluar/pulang tidak tersedia; gunakan wizard keluar.</p>
                            <p v-if="updateForm.errors.sanction" class="mt-1 text-xs font-medium text-rose-600">{{ updateForm.errors.sanction }}</p>
                        </div>
                        <div>
                            <label class="label" for="resolution">Catatan penyelesaian</label>
                            <textarea id="resolution" v-model="updateForm.resolution_notes" rows="3" class="field" placeholder="Wajib diisi saat menutup kasus."></textarea>
                            <p class="mt-1 text-[10px] text-slate-400">Wajib ketika status diubah menjadi Selesai.</p>
                            <p v-if="updateForm.errors.resolution_notes" class="mt-1 text-xs font-medium text-rose-600">{{ updateForm.errors.resolution_notes }}</p>
                        </div>
                        <button type="submit" class="btn-primary w-full" :disabled="updateForm.processing">
                            <Loader2 v-if="updateForm.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ updateForm.processing ? 'Menyimpan…' : 'Simpan perubahan' }}
                        </button>
                        <p v-if="updateForm.recentlySuccessful" class="text-center text-[10px] font-semibold text-emerald-600">Perubahan tersimpan.</p>
                    </form>
                </section>

                <section v-else class="card p-5">
                    <div class="flex items-center gap-2">
                        <Eye class="h-4 w-4 text-slate-400" />
                        <p class="text-sm font-bold text-slate-800">Akses baca</p>
                    </div>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                        Anda dapat melihat detail kasus ini, tetapi perubahan status dan protokol hanya dapat dilakukan penanggung jawab atau staf berwenang.
                    </p>
                </section>

                <section class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Informasi kasus</p>
                    <dl class="mt-3 space-y-2.5 text-xs">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-slate-400">Nomor kasus</dt>
                            <dd class="font-mono text-[11px] font-semibold text-slate-700">{{ incident.case_number }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-slate-400">Dibuat</dt>
                            <dd class="font-medium text-slate-700">{{ formatDateTime(incident.occurred_at) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-slate-400">Ditutup</dt>
                            <dd class="font-medium text-slate-700">{{ incident.closed_at ? formatDateTime(incident.closed_at) : 'Belum' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-slate-400">Keputusan</dt>
                            <dd class="text-right font-medium text-slate-700">{{ incident.sanction ? options.sanctions[incident.sanction] : '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <div v-if="incident.is_positive" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-emerald-900"><Sparkles class="h-4 w-4" /> Catatan apresiasi</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-emerald-900/80">
                        Bagikan apresiasi secara spesifik dan pribadi. Hindari membandingkan anak dengan temannya.
                    </p>
                </div>

                <div v-else class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><ShieldAlert class="h-4 w-4" /> Prinsip respons</p>
                    <ul class="mt-2 space-y-1.5 text-[11px] leading-relaxed text-brand-900/70">
                        <li>• Utamakan keselamatan dan pemulihan anak.</li>
                        <li>• Dengarkan tanpa menghakimi.</li>
                        <li>• Catat fakta, bukan label.</li>
                        <li>• Jaga kerahasiaan dan martabat anak.</li>
                    </ul>
                </div>

                <div v-if="incident.overdue" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-[11px] leading-relaxed text-rose-800">
                    <p class="flex items-center gap-1.5 font-bold"><AlertTriangle class="h-4 w-4" /> Respons terlambat</p>
                    <p class="mt-1">Segera koordinasikan dengan kepala sekolah. Dokumentasikan alasan keterlambatan pada catatan penyelesaian.</p>
                </div>
            </div>
        </div>

        <Modal :show="showFollowupModal" title="Selesaikan tindak lanjut" :description="activeFollowup ? `Minggu ke-${activeFollowup.week} · tenggat ${formatDate(activeFollowup.due_at)}` : ''" @close="showFollowupModal = false">
            <form class="space-y-3" @submit.prevent="completeFollowup">
                <div>
                    <label class="label" for="followup-notes">Catatan tindak lanjut</label>
                    <textarea id="followup-notes" v-model="followupForm.notes" rows="4" class="field" placeholder="Apa yang dilakukan, bagaimana kondisi anak, dan rencana berikutnya." required></textarea>
                    <p v-if="followupForm.errors.notes" class="mt-1 text-xs font-medium text-rose-600">{{ followupForm.errors.notes }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="btn-secondary" @click="showFollowupModal = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="followupForm.processing">
                        <Loader2 v-if="followupForm.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        {{ followupForm.processing ? 'Menyimpan…' : 'Tandai selesai' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
