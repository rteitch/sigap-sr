<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarCheck2,
    CalendarDays,
    EyeOff,
    FileText,
    HeartHandshake,
    Home,
    MapPin,
    Pencil,
    Phone,
    Plus,
    School,
    ShieldCheck,
    ShieldX,
    UserRound,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import EmptyState from '../../Components/EmptyState.vue';
import {
    attendanceClass,
    confidentialityClass,
    formatDate,
    formatDateTime,
    initials,
    percentage,
    severityClass,
    statusClass,
    studentStatusClass,
} from '../../lib/labels';

const props = defineProps({
    student: { type: Object, required: true },
    attendance: { type: Object, required: true },
    incidents: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const page = usePage();
const options = computed(() => page.props.options);

const attendanceTotal = computed(() =>
    Object.values(props.attendance.summary ?? {}).reduce((sum, value) => sum + Number(value), 0),
);
const presentCount = computed(() => Number(props.attendance.summary?.hadir ?? 0));
const presentRate = computed(() => percentage(presentCount.value, attendanceTotal.value));

const age = computed(() => {
    if (!props.student.birth_date) return null;
    const birth = new Date(props.student.birth_date);
    const now = new Date();
    let years = now.getFullYear() - birth.getFullYear();
    const monthDiff = now.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) years--;
    return years;
});
</script>

<template>
    <Head :title="student.full_name" />

    <AppLayout>
        <PageHeader
            :title="student.full_name"
            :description="`${student.code} · ${student.classroom ?? 'Belum ditempatkan'} · ${student.dorm ?? 'Tanpa asrama'}`"
            eyebrow="Profil siswa"
        >
            <template #actions>
                <Link href="/siswa" class="btn-secondary"><ArrowLeft class="h-4 w-4" /> Kembali</Link>
                <Link :href="`/insiden/buat?student_id=${student.id}`" class="btn-secondary"><Plus class="h-4 w-4" /> Catat insiden</Link>
                <Link v-if="canManage" :href="`/siswa/${student.id}/edit`" class="btn-primary"><Pencil class="h-4 w-4" /> Ubah data</Link>
            </template>
        </PageHeader>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section class="card overflow-hidden">
                    <div class="bg-gradient-to-r from-brand-800 via-brand-700 to-brand-600 px-5 py-5 text-white">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl border-2 border-white/20 bg-white/10 shadow-sm">
                                    <img v-if="student.photo_url" :src="student.photo_url" :alt="student.full_name" class="h-full w-full object-cover" />
                                    <span v-else class="grid h-full w-full place-items-center text-lg font-bold text-white">
                                        {{ initials(student.full_name) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-lg font-bold">{{ student.full_name }}</p>
                                    <p class="mt-0.5 text-xs text-brand-100/80">
                                        {{ options.genders[student.gender] }} · {{ age !== null ? `${age} tahun` : 'Usia belum dicatat' }}
                                        <span v-if="student.nickname"> (panggilan: {{ student.nickname }})</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold ring-1 ring-white/20">{{ student.code }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1" :class="studentStatusClass(student.status)">
                                    {{ options.student_statuses[student.status] }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="flex gap-2.5">
                            <School class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kelas</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ student.classroom ?? '—' }}</p>
                                <p class="text-[11px] text-slate-500">Wali: {{ student.homeroom_teacher ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2.5">
                            <Home class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Asrama</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ student.dorm ?? '—' }}</p>
                                <p class="text-[11px] text-slate-500">Wali: {{ student.dorm_wali ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2.5">
                            <CalendarDays class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kelahiran &amp; Usia</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">
                                    {{ student.birth_date ? formatDate(student.birth_date) : 'Belum tercatat' }}
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    {{ student.birth_place ? `di ${student.birth_place}` : '' }}
                                    {{ age !== null ? `(${age} tahun)` : '' }}
                                </p>
                            </div>
                        </div>
                        <div v-if="student.address" class="col-span-full border-t border-slate-100 pt-3 flex gap-2.5">
                            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Alamat domisili keluarga</p>
                                <p class="mt-0.5 text-xs text-slate-700 leading-relaxed">{{ student.address }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kehadiran 30 hari</p>
                            <p class="mt-0.5 text-xs text-slate-500">Presensi kelas dan kegiatan asrama</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-800">{{ presentRate }}% hadir</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                        <div v-for="(label, status) in options.attendance_statuses" :key="status" class="rounded-xl border border-slate-200 p-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
                            <p class="mt-1 text-lg font-bold text-slate-800">{{ attendance.summary[status] ?? 0 }}</p>
                        </div>
                    </div>

                    <div v-if="attendance.recent.length" class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="table-head">
                                <tr>
                                    <th class="whitespace-nowrap px-3 py-2">Tanggal</th>
                                    <th class="whitespace-nowrap px-3 py-2">Kegiatan</th>
                                    <th class="whitespace-nowrap px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="record in attendance.recent" :key="record.id">
                                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ formatDate(record.date) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ options.attendance_types[record.type] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="attendanceClass(record.status)">
                                            {{ options.attendance_statuses[record.status] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-500">{{ record.notes ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-slate-800">Riwayat insiden</p>
                            <p class="mt-0.5 text-xs text-slate-500">Hanya catatan yang sesuai akses Anda</p>
                        </div>
                        <Link href="/insiden" class="text-xs font-semibold text-brand-700 hover:underline">Semua insiden</Link>
                    </div>
                    <div v-if="incidents.length" class="divide-y divide-slate-100">
                        <Link v-for="incident in incidents" :key="incident.id" :href="`/insiden/${incident.id}`" class="flex items-start gap-3 px-5 py-3.5 transition hover:bg-slate-50">
                            <span class="mt-0.5 rounded-lg px-2 py-1 text-[10px] font-bold ring-1" :class="severityClass(incident.severity)">T{{ incident.severity }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="text-sm font-semibold text-slate-800">{{ incident.category }}</p>
                                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1" :class="statusClass(incident.status)">{{ options.incident_statuses[incident.status] }}</span>
                                    <span v-if="incident.confidentiality !== 'normal'" class="rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1" :class="confidentialityClass(incident.confidentiality)">
                                        {{ options.confidentialities[incident.confidentiality] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    {{ incident.case_number }} · {{ formatDateTime(incident.occurred_at) }} · Peran: {{ options.incident_roles[incident.role] ?? '—' }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <EmptyState v-else title="Tidak ada riwayat insiden" description="Tidak ada catatan insiden yang dapat Anda lihat untuk siswa ini." />
                </section>
            </div>

            <div class="space-y-5">
                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg" :class="student.consent.active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                            <ShieldCheck v-if="student.consent.active" class="h-4 w-4" />
                            <ShieldX v-else class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Persetujuan data</p>
                            <p class="text-[11px]" :class="student.consent.active ? 'text-emerald-700' : 'text-amber-700'">
                                {{ student.consent.active ? 'Aktif' : 'Belum aktif atau ditarik' }}
                            </p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div>
                            <dt class="text-slate-400">Diberikan</dt>
                            <dd class="font-medium text-slate-700">{{ formatDate(student.consent.given_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Ruang lingkup</dt>
                            <dd class="font-medium text-slate-700">{{ student.consent.scope ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Dokumen</dt>
                            <dd class="font-medium text-slate-700">{{ student.consent.document ?? '—' }}</dd>
                        </div>
                        <div v-if="student.consent.withdrawn_at">
                            <dt class="text-slate-400">Ditarik</dt>
                            <dd class="font-semibold text-rose-600">{{ formatDate(student.consent.withdrawn_at) }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-50 text-sky-700"><HeartHandshake class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kontak keluarga</p>
                            <p class="text-[11px] text-slate-500">Penjemput harus terdaftar</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="(contact, index) in student.contacts" :key="index" class="rounded-xl border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-slate-800">{{ contact.name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ contact.relation ?? 'Wali' }}</p>
                                </div>
                                <span v-if="contact.is_primary" class="rounded-full bg-brand-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-700">Utama</span>
                            </div>
                            <div class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-600">
                                <Phone class="h-3 w-3 text-slate-400" /> {{ contact.phone ?? 'Tanpa nomor' }}
                            </div>
                            <p class="mt-1 text-[10px] font-medium" :class="contact.can_pickup ? 'text-emerald-600' : 'text-slate-400'">
                                {{ contact.can_pickup ? 'Berhak menjemput' : 'Tidak tercatat sebagai penjemput' }}
                            </p>
                        </div>
                        <p v-if="!student.contacts.length" class="text-xs text-slate-500">Kontak keluarga belum dicatat.</p>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700"><FileText class="h-4 w-4" /></span>
                        <p class="text-sm font-bold text-slate-800">Catatan terbatas</p>
                    </div>
                    <div class="mt-3 space-y-3 text-xs">
                        <div>
                            <p class="text-slate-400">Kebutuhan khusus / akomodasi</p>
                            <p class="mt-0.5 leading-relaxed text-slate-700">{{ student.special_needs ?? 'Tidak ada catatan.' }}</p>
                        </div>
                        <div v-if="student.socioeconomic_desil">
                            <p class="text-slate-400">Desil sosial-ekonomi</p>
                            <p class="mt-0.5 font-semibold text-slate-700">{{ student.socioeconomic_desil }}</p>
                        </div>
                    </div>
                    <p class="mt-3 flex items-start gap-1.5 rounded-lg bg-slate-50 p-2 text-[10px] leading-relaxed text-slate-500">
                        <EyeOff class="mt-0.5 h-3 w-3 shrink-0" /> Hanya untuk staf berwenang. Jangan dibagikan.
                    </p>
                </section>

                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><CalendarCheck2 class="h-4 w-4" /> Tindakan cepat</p>
                    <div class="mt-3 grid gap-2">
                        <Link href="/presensi" class="btn-secondary !justify-start text-xs"><CalendarCheck2 class="h-4 w-4" /> Buka presensi</Link>
                        <Link :href="`/insiden/buat?student_id=${student.id}`" class="btn-secondary !justify-start text-xs"><Plus class="h-4 w-4" /> Catat insiden untuk siswa ini</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
