<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Building2,
    CalendarDays,
    Clock3,
    Code2,
    EyeOff,
    ImagePlus,
    Loader2,
    MapPin,
    Palette,
    Phone,
    Save,
    School,
    ShieldCheck,
    Trash2,
} from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';

const props = defineProps({
    school: { type: Object, required: true },
    levels: { type: Array, default: () => [] },
    developer: { type: Object, default: () => ({}) },
    license: { type: String, default: 'MIT' },
    version: { type: String, default: '' },
});

const page = usePage();
const saved = ref(false);
const logoFile = ref(null);
const logoPreview = ref(null);

const form = useForm({
    name: props.school.name,
    npsn: props.school.npsn ?? '',
    level: props.school.level ?? 'SMP',
    address: props.school.address ?? '',
    phone: props.school.phone ?? '',
    small_group_threshold: props.school.small_group_threshold,
    missing_student_escalation_minutes: props.school.missing_student_escalation_minutes,
    app_name: props.school.app_name ?? 'SIGAP-SR',
    tagline: props.school.tagline ?? '',
    primary_color: props.school.primary_color ?? '#1d4ed8',
    logo: null,
    remove_logo: false,
});

const activeLevels = computed(() => props.levels.filter((level) => level.is_active));
const currentLogo = computed(() => logoPreview.value ?? props.school.logo_url);

function chooseLogo(event) {
    const file = event.target.files?.[0] ?? null;
    logoFile.value = file;
    form.logo = file;
    form.remove_logo = false;

    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
}

function removeLogo() {
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }

    logoFile.value = null;
    form.logo = null;
    form.remove_logo = true;
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'patch' })).post('/admin/settings', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            saved.value = true;
            if (logoPreview.value) {
                URL.revokeObjectURL(logoPreview.value);
                logoPreview.value = null;
            }
            logoFile.value = null;
            form.logo = null;
            form.remove_logo = false;
            setTimeout(() => (saved.value = false), 2500);
        },
    });
}

onBeforeUnmount(() => {
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value);
});
</script>

<template>
    <Head title="Pengaturan" />

    <AppLayout>
        <PageHeader
            title="Pengaturan"
            description="Kelola identitas sekolah, branding aplikasi, ambang perlindungan data, dan SOP siswa tidak ditemukan."
            eyebrow="Administrasi"
        >
            <template #actions>
                <Link href="/admin/master" class="btn-secondary"><Building2 class="h-4 w-4" /> Data master</Link>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan pengaturan' }}
                </button>
            </template>
        </PageHeader>

        <form class="grid gap-5 xl:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><Building2 class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Identitas sekolah</p>
                            <p class="text-[11px] text-slate-500">Tampil pada header aplikasi, laporan, dan dokumen ekspor.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label" for="name">Nama sekolah</label>
                            <input id="name" v-model="form.name" type="text" class="field" required />
                            <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="label" for="npsn">NPSN</label>
                            <input id="npsn" v-model="form.npsn" type="text" class="field" placeholder="Nomor pokok sekolah" />
                            <p v-if="form.errors.npsn" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.npsn }}</p>
                        </div>
                        <div>
                            <label class="label" for="level">Jenjang sekolah</label>
                            <select id="level" v-model="form.level" class="field">
                                <option v-for="level in activeLevels" :key="level.id" :value="level.code">{{ level.name }}</option>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">Daftar jenjang dikelola melalui Data master.</p>
                            <p v-if="form.errors.level" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.level }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="address">Alamat</label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-400" />
                                <textarea id="address" v-model="form.address" rows="2" class="field !pl-9"></textarea>
                            </div>
                        </div>
                        <div>
                            <label class="label" for="phone">Telepon</label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input id="phone" v-model="form.phone" type="tel" class="field !pl-9" />
                            </div>
                        </div>
                        <div class="flex items-end">
                            <div class="w-full rounded-xl bg-slate-50 p-3 text-[11px] text-slate-500">
                                <p class="flex items-center gap-1.5 font-semibold text-slate-700"><CalendarDays class="h-3.5 w-3.5" /> Tahun ajaran aktif</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">{{ school.academic_year || 'Belum ditetapkan' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-50 text-violet-700"><Palette class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Branding aplikasi</p>
                            <p class="text-[11px] text-slate-500">Nama aplikasi, tagline, logo, dan warna utama dapat disesuaikan.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="app-name">Nama aplikasi</label>
                            <input id="app-name" v-model="form.app_name" type="text" class="field" maxlength="60" required />
                            <p v-if="form.errors.app_name" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.app_name }}</p>
                        </div>
                        <div>
                            <label class="label" for="tagline">Tagline</label>
                            <input id="tagline" v-model="form.tagline" type="text" class="field" maxlength="140" placeholder="Pengasuhan, pembinaan, dan evaluasi" />
                            <p v-if="form.errors.tagline" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.tagline }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="primary-color">Warna utama</label>
                            <div class="flex flex-wrap items-center gap-3">
                                <input id="primary-color" v-model="form.primary_color" type="color" class="h-11 w-16 cursor-pointer rounded-lg border border-slate-200 bg-white p-1" />
                                <input v-model="form.primary_color" type="text" class="field max-w-40 font-mono uppercase" maxlength="7" />
                                <span class="text-[11px] text-slate-400">Warna ini dipakai pada tombol, navigasi, dan grafik.</span>
                            </div>
                            <p v-if="form.errors.primary_color" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.primary_color }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 p-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                <img v-if="currentLogo" :src="currentLogo" alt="Logo sekolah" class="h-full w-full object-contain p-1" />
                                <ImagePlus v-else class="h-6 w-6 text-slate-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800">Logo sekolah</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <label class="btn-secondary cursor-pointer !px-2.5 !py-1.5 text-xs">
                                        <ImagePlus class="h-3.5 w-3.5" /> Pilih logo
                                        <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" @change="chooseLogo" />
                                    </label>
                                    <button v-if="currentLogo" type="button" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-rose-600 hover:bg-rose-50" @click="removeLogo">
                                        <Trash2 class="h-3.5 w-3.5" /> Hapus logo
                                    </button>
                                    <span v-if="logoFile" class="max-w-40 truncate text-[10px] text-slate-400">{{ logoFile.name }}</span>
                                </div>
                                <p v-if="form.errors.logo" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.logo }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-50 text-violet-700"><EyeOff class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Perlindungan data agregat</p>
                            <p class="text-[11px] text-slate-500">Mencegah identifikasi anak dari kelompok kecil.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="threshold">Ambang kelompok kecil</label>
                            <input id="threshold" v-model="form.small_group_threshold" type="number" min="2" max="20" class="field" />
                            <p class="mt-1 text-[11px] text-slate-400">Kategori dengan jumlah di bawah angka ini disembunyikan pada laporan. Rekomendasi: 5.</p>
                            <p v-if="form.errors.small_group_threshold" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.small_group_threshold }}</p>
                        </div>
                        <div class="flex items-start rounded-xl bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-500">
                            <AlertTriangle class="mr-2 mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                            Nilai terlalu rendah meningkatkan risiko identifikasi. Jangan turunkan tanpa persetujuan kepala sekolah.
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-rose-50 text-rose-700"><Clock3 class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">SOP siswa tidak ditemukan</p>
                            <p class="text-[11px] text-slate-500">Batas waktu sebelum eskalasi kepada kepala sekolah.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="escalation">Batas eskalasi (menit)</label>
                            <input id="escalation" v-model="form.missing_student_escalation_minutes" type="number" min="15" max="1440" class="field" />
                            <p class="mt-1 text-[11px] text-slate-400">Setelah batas ini, kasus harus dilaporkan kepada kepala sekolah. SOP nasional: 120 menit.</p>
                            <p v-if="form.errors.missing_student_escalation_minutes" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.missing_student_escalation_minutes }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Pratinjau branding</p>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="flex items-center gap-3 p-3 text-white" :style="{ backgroundColor: form.primary_color || '#1d4ed8' }">
                            <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-white/15">
                                <img v-if="currentLogo" :src="currentLogo" alt="Logo" class="h-full w-full object-contain p-1" />
                                <School v-else class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">{{ form.app_name || 'SIGAP-SR' }}</p>
                                <p class="truncate text-[10px] text-white/80">{{ form.tagline || 'Pengasuhan, pembinaan, dan evaluasi' }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 p-3">
                            <p class="text-xs font-semibold text-slate-800">{{ form.name || 'Nama sekolah' }}</p>
                            <p class="text-[11px] text-slate-500">{{ form.level }} · {{ form.npsn || 'NPSN belum diisi' }}</p>
                            <button type="button" class="w-full rounded-lg px-3 py-2 text-xs font-bold text-white" :style="{ backgroundColor: form.primary_color || '#1d4ed8' }">Tombol utama</button>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Ambang kelompok</dt>
                            <dd class="font-bold text-slate-800">{{ form.small_group_threshold }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Eskalasi siswa hilang</dt>
                            <dd class="font-bold text-slate-800">{{ form.missing_student_escalation_minutes }} menit</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Tahun ajaran</dt>
                            <dd class="font-bold text-slate-800">{{ school.academic_year || '—' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan pengaturan' }}
                        </button>
                        <p v-if="saved" class="mt-2 text-center text-[10px] font-semibold text-emerald-600">Pengaturan tersimpan.</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-slate-800"><ShieldCheck class="h-4 w-4 text-emerald-600" /> Akses pengaturan</p>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                        Hanya administrator yang dapat mengubah pengaturan. Setiap perubahan dicatat pada jejak audit.
                    </p>
                    <p class="mt-2 text-[10px] text-slate-400">Diubah oleh {{ page.props.auth.user.name }}.</p>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><Code2 class="h-4 w-4" /> Tentang aplikasi</p>
                    <dl class="mt-2 space-y-1.5 text-[11px] text-brand-900/70">
                        <div class="flex justify-between gap-2"><dt>Versi</dt><dd class="font-semibold">{{ version || '1.0.0' }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>Lisensi</dt><dd class="font-semibold">{{ license }}</dd></div>
                        <div class="flex justify-between gap-2"><dt>Pengembang</dt><dd class="text-right font-semibold">{{ developer.name || 'Rizal Taufiq Hidayat' }}</dd></div>
                    </dl>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
