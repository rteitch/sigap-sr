<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Eye,
    EyeOff,
    KeyRound,
    Lock,
    Mail,
    Phone,
    Save,
    School,
    Shield,
    ShieldCheck,
    User,
    UserRound,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { formatDate, initials, roleClass } from '../../lib/labels';

const props = defineProps({
    user: { type: Object, required: true },
});

const page = usePage();

const profileForm = useForm({
    name: props.user.name,
    phone: props.user.phone ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

function submitProfile() {
    profileForm.patch('/profil', {
        preserveScroll: true,
    });
}

function submitPassword() {
    passwordForm.put('/profil/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <Head title="Profil & Keamanan" />

    <AppLayout>
        <PageHeader
            title="Profil & Keamanan Akun"
            description="Perbarui informasi kontak pribadi dan amankan akun ruang kerja Anda."
            eyebrow="Pengaturan Pengguna"
        />

        <div class="grid gap-6 xl:grid-cols-3">
            <!-- Left 2 columns: Forms -->
            <div class="space-y-6 xl:col-span-2">
                <!-- Section 1: Profil Pribadi -->
                <section class="card p-5 sm:p-6">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                            <UserRound class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Informasi Pribadi</h2>
                            <p class="text-xs text-slate-500">Nama dan kontak aktif yang dapat dihubungi oleh rekan pengasuh.</p>
                        </div>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitProfile">
                        <div>
                            <label class="label" for="profile-name">Nama lengkap</label>
                            <div class="relative">
                                <User class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="profile-name"
                                    v-model="profileForm.name"
                                    type="text"
                                    class="field pl-10"
                                    placeholder="Nama lengkap beserta gelar"
                                    required
                                />
                            </div>
                            <p v-if="profileForm.errors.name" class="mt-1.5 text-xs font-medium text-rose-600">
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="label" for="profile-email">Alamat email</label>
                            <div class="relative">
                                <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="profile-email"
                                    :value="user.email"
                                    type="email"
                                    class="field cursor-not-allowed bg-slate-50 pl-10 text-slate-500"
                                    disabled
                                />
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Email akun dikelola oleh administrator sekolah. Hubungi admin untuk perubahan email.
                            </p>
                        </div>

                        <div>
                            <label class="label" for="profile-phone">Nomor telepon / WhatsApp</label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="profile-phone"
                                    v-model="profileForm.phone"
                                    type="tel"
                                    class="field pl-10"
                                    placeholder="08xxxxxxxxxx"
                                />
                            </div>
                            <p v-if="profileForm.errors.phone" class="mt-1.5 text-xs font-medium text-rose-600">
                                {{ profileForm.errors.phone }}
                            </p>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button
                                type="submit"
                                class="btn-primary"
                                :disabled="profileForm.processing || !profileForm.isDirty"
                            >
                                <Save class="h-4 w-4" />
                                {{ profileForm.processing ? 'Menyimpan...' : 'Simpan profil' }}
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Section 2: Ganti Kata Sandi -->
                <section class="card p-5 sm:p-6">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">
                            <KeyRound class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Ubah Kata Sandi</h2>
                            <p class="text-xs text-slate-500">Gunakan kata sandi yang kuat dan belum pernah dipakai di platform lain.</p>
                        </div>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitPassword">
                        <div>
                            <label class="label" for="current-password">Kata sandi saat ini</label>
                            <div class="relative">
                                <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="current-password"
                                    v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    class="field pl-10 pr-10"
                                    required
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    :aria-label="showCurrentPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                    @click="showCurrentPassword = !showCurrentPassword"
                                >
                                    <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="mt-1.5 text-xs font-medium text-rose-600">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="new-password">Kata sandi baru</label>
                                <div class="relative">
                                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="new-password"
                                        v-model="passwordForm.password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        class="field pl-10 pr-10"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        :aria-label="showNewPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        @click="showNewPassword = !showNewPassword"
                                    >
                                        <EyeOff v-if="showNewPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="mt-1.5 text-xs font-medium text-rose-600">
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="label" for="confirm-password">Konfirmasi kata sandi</label>
                                <div class="relative">
                                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="confirm-password"
                                        v-model="passwordForm.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        class="field pl-10 pr-10"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        :aria-label="showConfirmPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                    >
                                        <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button
                                type="submit"
                                class="btn-primary"
                                :disabled="passwordForm.processing || !passwordForm.password"
                            >
                                <KeyRound class="h-4 w-4" />
                                {{ passwordForm.processing ? 'Memperbarui...' : 'Perbarui kata sandi' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- Right 1 column: Account Summary & Safeguards -->
            <div class="space-y-6">
                <!-- User Identity Card -->
                <div class="card p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-brand-100 text-lg font-bold text-brand-800">
                            {{ initials(user.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-base font-bold text-slate-900">{{ user.name }}</h3>
                            <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
                            <span class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(user.role)">
                                {{ user.role_label }}
                            </span>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-1.5 text-slate-500">
                                <School class="h-3.5 w-3.5 text-slate-400" /> Satuan pendidikan
                            </dt>
                            <dd class="font-semibold text-slate-800">{{ user.school_name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-1.5 text-slate-500">
                                <CalendarDays class="h-3.5 w-3.5 text-slate-400" /> Terdaftar sejak
                            </dt>
                            <dd class="font-semibold text-slate-800">{{ formatDate(user.created_at) }}</dd>
                        </div>
                        <div v-if="user.last_login_at" class="flex items-center justify-between gap-2">
                            <dt class="flex items-center gap-1.5 text-slate-500">
                                <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" /> Masuk terakhir
                            </dt>
                            <dd class="font-semibold text-slate-800">{{ formatDate(user.last_login_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Privacy & Role Scope Reminder -->
                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800">
                        <Shield class="h-4 w-4" /> Perlindungan Akun
                    </p>
                    <ul class="mt-2.5 space-y-1.5 text-[11px] leading-relaxed text-brand-900/75">
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-600" />
                            Aktivitas login dan perubahan data akun tercatat di jejak audit demi keamanan anak.
                        </li>
                        <li class="flex items-start gap-1.5">
                            <CheckCircle2 class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-600" />
                            Jangan membagikan kata sandi ruang kerja kepada pihak lain.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
