<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, GraduationCap, Pencil, Plus, School, Search, ShieldCheck, UserCheck, UserX, Users } from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatCard from '../../../Components/StatCard.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import { formatDateTime, initials, roleClass } from '../../../lib/labels';

const props = defineProps({
    users: { type: Object, required: true },
    roles: { type: Object, required: true },
});

const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const activeTab = ref('all'); // 'all', 'teachers', 'dorm', 'management'

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.users.data.filter((user) => {
        const matchesTerm = !term || [user.name, user.email, user.role_label].some((value) => String(value ?? '').toLowerCase().includes(term));
        const matchesRole = !roleFilter.value || user.role === roleFilter.value;
        const matchesStatus = !statusFilter.value || (statusFilter.value === 'aktif' ? user.is_active : !user.is_active);

        let matchesTab = true;
        if (activeTab.value === 'teachers') {
            matchesTab = ['guru', 'wali_kelas', 'bk'].includes(user.role);
        } else if (activeTab.value === 'dorm') {
            matchesTab = user.role === 'wali_asrama';
        } else if (activeTab.value === 'management') {
            matchesTab = ['kepala_sekolah', 'tppk', 'admin'].includes(user.role);
        }

        return matchesTerm && matchesRole && matchesStatus && matchesTab;
    });
});

const teacherCount = computed(() => props.users.data.filter((u) => ['guru', 'wali_kelas', 'bk'].includes(u.role)).length);
const dormWaliCount = computed(() => props.users.data.filter((u) => u.role === 'wali_asrama').length);
const activeCount = computed(() => props.users.data.filter((user) => user.is_active).length);
const inactiveCount = computed(() => props.users.data.filter((user) => !user.is_active).length);
</script>

<template>
    <Head title="Guru & Staf" />

    <AppLayout>
        <PageHeader
            title="Guru & Staf"
            description="Kelola akun guru, wali kelas, wali asrama, dan tenaga kependidikan beserta penugasan kelas dan asrama binaan."
            eyebrow="Administrasi & Ketenagaan"
        >
            <template #actions>
                <Link href="/admin/users/create" class="btn-primary"><Plus class="h-4 w-4" /> Tambah staf / guru</Link>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total Guru & Staf" :value="users.total" hint="Akun terdaftar" :icon="Users" tone="brand" />
            <StatCard label="Guru & Wali Kelas" :value="teacherCount" hint="Tenaga pendidik & BK" :icon="GraduationCap" tone="sky" />
            <StatCard label="Wali Asrama" :value="dormWaliCount" hint="Pengasuhan santri" :icon="Building2" tone="amber" />
            <StatCard label="Akun Aktif" :value="activeCount" hint="Akses sistem terbuka" :icon="UserCheck" tone="emerald" />
        </div>

        <!-- Quick filter tabs -->
        <div class="mt-5 flex flex-wrap items-center gap-1.5 border-b border-slate-200 pb-2">
            <button
                type="button"
                class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                :class="activeTab === 'all' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                @click="activeTab = 'all'"
            >
                Semua Staf ({{ users.total }})
            </button>
            <button
                type="button"
                class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                :class="activeTab === 'teachers' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                @click="activeTab = 'teachers'"
            >
                Guru &amp; Wali Kelas ({{ teacherCount }})
            </button>
            <button
                type="button"
                class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                :class="activeTab === 'dorm' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                @click="activeTab = 'dorm'"
            >
                Wali Asrama ({{ dormWaliCount }})
            </button>
            <button
                type="button"
                class="rounded-xl px-3.5 py-2 text-xs font-semibold transition"
                :class="activeTab === 'management' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                @click="activeTab = 'management'"
            >
                Manajemen &amp; TPPK
            </button>
        </div>

        <section class="card mt-4 p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <label class="label" for="search">Cari</label>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input id="search" v-model="search" type="search" class="field pl-10" placeholder="Nama, email, atau gelar…" />
                    </div>
                </div>
                <div>
                    <label class="label">Peran spesifik</label>
                    <select v-model="roleFilter" class="field">
                        <option value="">Semua peran</option>
                        <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">Status akun</label>
                    <select v-model="statusFilter" class="field">
                        <option value="">Semua status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="card mt-4 overflow-hidden">
            <div v-if="filtered.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Guru / Staf</th>
                            <th class="px-4 py-2.5">Peran</th>
                            <th class="px-4 py-2.5">Penugasan binaan</th>
                            <th class="px-4 py-2.5">Kontak</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Login terakhir</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in filtered" :key="user.id" class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-[10px] font-bold text-brand-700">{{ initials(user.name) }}</span>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ user.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(user.role)">{{ user.role_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="user.homeroom_classes?.length" class="flex flex-wrap gap-1">
                                    <span v-for="c in user.homeroom_classes" :key="c.id" class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-2 py-0.5 text-[10px] font-semibold text-teal-800 ring-1 ring-teal-200">
                                        <School class="h-3 w-3" /> Wali Kelas {{ c.name }}
                                    </span>
                                </div>
                                <div v-else-if="user.dorm_assignments?.length" class="flex flex-wrap gap-1">
                                    <span v-for="d in user.dorm_assignments" :key="d.id" class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-800 ring-1 ring-sky-200">
                                        <Building2 class="h-3 w-3" /> Asrama {{ d.name }}
                                    </span>
                                </div>
                                <span v-else-if="user.role === 'bk'" class="text-[11px] text-purple-700 font-medium">Bimbingan &amp; Konseling</span>
                                <span v-else-if="user.role === 'tppk'" class="text-[11px] text-amber-700 font-medium">Pencegahan Kekerasan</span>
                                <span v-else class="text-[11px] text-slate-400">—</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ user.phone ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="user.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200'">
                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ user.last_login_at ? formatDateTime(user.last_login_at) : 'Belum pernah' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <Link
                                    :href="`/admin/users/${user.id}/edit`"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50"
                                    :aria-label="'Ubah data staf ' + user.name"
                                >
                                    <Pencil class="h-3.5 w-3.5" /> Ubah
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Tidak ada staf / guru" description="Tidak ada akun yang cocok dengan pencarian atau filter yang dipilih." />
            <Pagination :paginator="users" />
        </section>

        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="(label, role) in roles" :key="role" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3">
                <ShieldCheck class="h-4 w-4 shrink-0 text-slate-400" />
                <div>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(role)">{{ label }}</span>
                    <p class="mt-1 text-[11px] text-slate-500">{{ users.data.filter((user) => user.role === role).length }} akun</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
