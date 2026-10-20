<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Eye,
    EyeOff,
    Info,
    KeyRound,
    Loader2,
    Mail,
    Phone,
    Save,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { initials, roleClass } from '../../../lib/labels';

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Object, required: true },
    roleDescriptions: { type: Object, default: () => ({}) },
});

const page = usePage();
const isEdit = computed(() => Boolean(props.user));
const isSelf = computed(() => props.user?.id === page.props.auth.user.id);
const showPassword = ref(false);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    role: props.user?.role ?? 'guru',
    password: '',
    password_confirmation: '',
    is_active: props.user?.is_active ?? true,
});

function submit() {
    if (isEdit.value) {
        form.put(`/admin/users/${props.user.id}`);
    } else {
        form.post('/admin/users');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Ubah pengguna' : 'Tambah pengguna'" />

    <AppLayout>
        <PageHeader
            :title="isEdit ? 'Ubah pengguna' : 'Tambah pengguna'"
            description="Akun staf harus menggunakan identitas yang jelas dan peran sesuai tugas."
            eyebrow="Administrasi"
        >
            <template #actions>
                <Link href="/admin/users" class="btn-secondary"><ArrowLeft class="h-4 w-4" /> Kembali</Link>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ form.processing ? 'Menyimpan…' : 'Simpan pengguna' }}
                </button>
            </template>
        </PageHeader>

        <form class="grid gap-5 xl:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><UserRound class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Identitas akun</p>
                            <p class="text-[11px] text-slate-500">Nama akan tampil pada catatan insiden dan audit.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label" for="name">Nama lengkap</label>
                            <input id="name" v-model="form.name" type="text" class="field" placeholder="Nama staf" required />
                            <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="label" for="email">Email</label>
                            <div class="relative">
                                <Mail class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input id="email" v-model="form.email" type="email" class="field !pl-9" placeholder="nama@sigap.test" required />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="label" for="phone">Nomor telepon</label>
                            <div class="relative">
                                <Phone class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input id="phone" v-model="form.phone" type="tel" class="field !pl-9" placeholder="08xx" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-50 text-violet-700"><ShieldCheck class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Peran dan akses</p>
                            <p class="text-[11px] text-slate-500">Peran menentukan data yang dapat dilihat dan diubah.</p>
                        </div>
                    </div>

                    <div v-if="isSelf" class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
                        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        Anda sedang mengubah akun sendiri. Peran tidak dapat diubah dan akun tidak dapat dinonaktifkan dari halaman ini.
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="(label, role) in roles"
                            :key="role"
                            class="cursor-pointer rounded-xl border p-3 transition"
                            :class="[
                                form.role === role ? 'border-brand-400 bg-brand-50/70 ring-1 ring-brand-300' : 'border-slate-200 hover:border-slate-300',
                                isSelf && form.role !== role ? 'cursor-not-allowed opacity-50' : '',
                            ]"
                        >
                            <input v-model="form.role" type="radio" :value="role" class="sr-only" :disabled="isSelf && form.role !== role" />
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(role)">{{ label }}</span>
                            <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">{{ roleDescriptions[role] ?? '—' }}</p>
                        </label>
                    </div>
                    <p v-if="form.errors.role" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.role }}</p>

                    <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                        <span>
                            <span class="text-xs font-semibold text-slate-800">Akun aktif</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">Akun nonaktif tidak dapat masuk, tetapi riwayatnya tetap tersimpan.</span>
                        </span>
                        <input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-brand-700 focus:ring-brand-500" :disabled="isSelf" />
                    </label>
                    <p v-if="form.errors.is_active" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.is_active }}</p>
                </section>

                <section class="card p-5">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700"><KeyRound class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ isEdit ? 'Atur ulang kata sandi' : 'Kata sandi awal' }}</p>
                            <p class="text-[11px] text-slate-500">{{ isEdit ? 'Biarkan kosong jika tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter. Minta pengguna menggantinya setelah masuk.' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="password">Kata sandi</label>
                            <div class="relative">
                                <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" class="field pr-10" placeholder="••••••••" :required="!isEdit" autocomplete="new-password" />
                                <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-700" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" @click="showPassword = !showPassword">
                                    <EyeOff v-if="showPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="label" for="password_confirmation">Ulangi kata sandi</label>
                            <input id="password_confirmation" v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" class="field" placeholder="••••••••" :required="!isEdit" autocomplete="new-password" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Ringkasan</p>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-sm font-bold text-brand-700">{{ initials(form.name) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ form.name || 'Nama pengguna' }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ form.email || 'email@sigap.test' }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Peran</dt>
                            <dd><span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(form.role)">{{ roles[form.role] }}</span></dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Status</dt>
                            <dd class="font-semibold" :class="form.is_active ? 'text-emerald-600' : 'text-rose-600'">{{ form.is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-slate-400">Kata sandi</dt>
                            <dd class="font-semibold text-slate-700">{{ isEdit ? (form.password ? 'Diubah' : 'Tetap') : 'Baru' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ form.processing ? 'Menyimpan…' : 'Simpan pengguna' }}
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><ShieldCheck class="h-4 w-4" /> Praktik akun aman</p>
                    <ul class="mt-2 space-y-1.5 text-[11px] leading-relaxed text-brand-900/70">
                        <li>• Satu akun untuk satu staf, jangan berbagi.</li>
                        <li>• Gunakan kata sandi unik dan panjang.</li>
                        <li>• Nonaktifkan akun segera saat staf keluar.</li>
                        <li>• Tinjau peran secara berkala.</li>
                    </ul>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
