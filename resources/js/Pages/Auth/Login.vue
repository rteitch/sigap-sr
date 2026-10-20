<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, KeyRound, LockKeyhole, Mail, ShieldCheck, Sparkles } from '@lucide/vue';
import BrandMark from '../../Components/BrandMark.vue';

const page = usePage();
const branding = computed(() => page.props.branding || {});
const demo = computed(() => branding.value.demo || { enabled: false, accounts: [] });
const demoAccounts = computed(() => demo.value.accounts || []);

const form = useForm({
    email: demo.value.enabled ? demoAccounts.value[0]?.email ?? '' : '',
    password: demo.value.enabled ? demo.value.password ?? '' : '',
    remember: true,
});

const showPassword = ref(false);
const selectedEmail = computed(() => form.email);

function chooseAccount(email) {
    form.email = email;
    form.password = demo.value.password || '';
}

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Masuk" />

    <div class="flex min-h-screen bg-slate-50">
        <div class="relative hidden w-[46%] overflow-hidden bg-brand-950 lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700"></div>
            <div class="absolute -right-28 -top-24 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="relative flex h-full flex-col justify-between p-12">
                <div class="flex items-center gap-3 text-white">
                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/20">
                        <img :src="branding.logo_url || '/icon.svg'" :alt="'Logo ' + (branding.school_name || branding.app_name || 'SIGAP-SR')" class="h-full w-full object-contain" :class="branding.logo_url ? 'bg-white p-0.5' : ''" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-bold tracking-tight">{{ branding.app_name || 'SIGAP-SR' }}</p>
                        <p class="truncate text-xs text-brand-100/80">{{ branding.school_name || 'Sekolah Rakyat' }}</p>
                    </div>
                </div>

                <div class="max-w-md">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold text-brand-100 ring-1 ring-white/15">
                        <Sparkles class="h-3.5 w-3.5" /> Satu tempat tepercaya
                    </p>
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-white">
                        Setiap anak terpantau, terlindungi, dan berkembang.
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-brand-100/85">
                        {{ branding.tagline || 'Pengasuhan, pembinaan, dan evaluasi Sekolah Rakyat' }}. Catat insiden, kelola presensi dan piket asrama, serta evaluasi program dengan data yang jujur dan akses yang terbatas.
                    </p>
                    <div class="mt-8 grid gap-3">
                        <div class="flex gap-3 rounded-xl bg-white/[0.07] p-3.5 ring-1 ring-white/10">
                            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                            <p class="text-xs leading-relaxed text-brand-50/90">Privasi sejak desain: identitas dipisahkan dari data analisis dan akses tercatat.</p>
                        </div>
                        <div class="flex gap-3 rounded-xl bg-white/[0.07] p-3.5 ring-1 ring-white/10">
                            <LockKeyhole class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                            <p class="text-xs leading-relaxed text-brand-50/90">Kasus sangat rahasia hanya terbuka bagi kepala sekolah, TPPK, dan BK.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-1 text-[11px] text-brand-200/60">
                    <p>{{ branding.app_name || 'SIGAP-SR' }} · {{ branding.tagline }}</p>
                    <p>Dikembangkan oleh {{ branding.developer?.name || 'Rizal Taufiq Hidayat' }} · v{{ branding.version || '1.0.0' }}</p>
                </div>
            </div>
        </div>

        <div class="flex w-full flex-col justify-center px-5 py-10 sm:px-10 lg:w-[54%] lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <BrandMark size="lg" />
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke ruang kerja</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan akun yang diberikan oleh administrator sekolah.</p>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="label" for="email">Email</label>
                        <div class="relative">
                            <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input id="email" v-model="form.email" type="email" autocomplete="username" class="field pl-10" placeholder="nama@sigap.test" required />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="label" for="password">Kata sandi</label>
                        <div class="relative">
                            <KeyRound class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                class="field pl-10 pr-10"
                                placeholder="Masukkan kata sandi"
                                required
                            />
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100"
                                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit" class="btn-primary w-full py-3" :disabled="form.processing">
                        <ShieldCheck class="h-4 w-4" />
                        {{ form.processing ? 'Memeriksa…' : 'Masuk dengan aman' }}
                    </button>
                </form>

                <div v-if="demo.enabled && demoAccounts.length" class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Akun demo (hanya mode lokal)</p>
                    <p class="mt-1 text-[11px] text-slate-400">Semua akun memakai kata sandi <span class="font-semibold text-slate-600">{{ demo.password }}</span>.</p>
                    <div class="mt-3 grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                        <button
                            v-for="account in demoAccounts"
                            :key="account.email"
                            type="button"
                            class="rounded-lg border px-2.5 py-2 text-left transition"
                            :class="selectedEmail === account.email
                                ? 'border-brand-300 bg-brand-50 text-brand-800'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                            @click="chooseAccount(account.email)"
                        >
                            <span class="block text-[11px] font-semibold">{{ account.label }}</span>
                            <span class="mt-0.5 block truncate text-[10px] text-slate-400">{{ account.email }}</span>
                        </button>
                    </div>
                </div>

                <p class="mt-6 flex items-start gap-2 text-[11px] leading-relaxed text-slate-400">
                    <ShieldCheck class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    Aplikasi ini memuat data anak. Jangan berbagi akun, jangan mengambil tangkapan layar berisi identitas, dan selalu keluar setelah selesai.
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                    <Link href="/kebijakan-privasi" class="underline hover:text-brand-700">Kebijakan Privasi</Link>
                    <span>·</span>
                    <Link href="/persyaratan-layanan" class="underline hover:text-brand-700">Persyaratan Layanan</Link>
                </div>

                <p class="mt-3 text-[11px] text-slate-400">
                    Dikembangkan oleh <span class="font-semibold text-slate-600">{{ branding.developer?.name || 'Rizal Taufiq Hidayat' }}</span>
                    · v{{ branding.version || '1.0.0' }}
                </p>
            </div>
        </div>
    </div>
</template>
