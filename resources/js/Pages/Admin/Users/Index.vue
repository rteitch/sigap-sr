<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Search, ShieldCheck, UserCheck, UserX, Users } from '@lucide/vue';
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

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.users.data.filter((user) => {
        const matchesTerm = !term || [user.name, user.email, user.role_label].some((value) => String(value ?? '').toLowerCase().includes(term));
        const matchesRole = !roleFilter.value || user.role === roleFilter.value;
        const matchesStatus = !statusFilter.value || (statusFilter.value === 'aktif' ? user.is_active : !user.is_active);
        return matchesTerm && matchesRole && matchesStatus;
    });
});

const activeCount = computed(() => props.users.data.filter((user) => user.is_active).length);
const inactiveCount = computed(() => props.users.data.filter((user) => !user.is_active).length);
</script>

<template>
    <Head title="Pengguna" />

    <AppLayout>
        <PageHeader
            title="Pengguna"
            description="Kelola akun staf, peran, dan akses. Perubahan peran memengaruhi kewenangan pada kasus dan data siswa."
            eyebrow="Administrasi"
        >
            <template #actions>
                <Link href="/admin/users/create" class="btn-primary"><Plus class="h-4 w-4" /> Tambah pengguna</Link>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-3">
            <StatCard label="Total pengguna" :value="users.total" hint="Akun di sekolah ini" :icon="Users" tone="brand" />
            <StatCard label="Aktif" :value="activeCount" hint="Dapat masuk aplikasi" :icon="UserCheck" tone="emerald" />
            <StatCard label="Nonaktif" :value="inactiveCount" hint="Akses dicabut" :icon="UserX" tone="rose" />
        </div>

        <section class="card mt-4 p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <label class="label" for="search">Cari</label>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input id="search" v-model="search" type="search" class="field !pl-9" placeholder="Nama atau email…" />
                    </div>
                </div>
                <div>
                    <label class="label">Peran</label>
                    <select v-model="roleFilter" class="field">
                        <option value="">Semua peran</option>
                        <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="label">Status</label>
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
                            <th class="px-4 py-2.5">Pengguna</th>
                            <th class="px-4 py-2.5">Kontak</th>
                            <th class="px-4 py-2.5">Peran</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Login terakhir</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="user in filtered" :key="user.id" class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-500">{{ initials(user.name) }}</span>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ user.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ user.phone ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(user.role)">{{ user.role_label }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="user.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200'">
                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ user.last_login_at ? formatDateTime(user.last_login_at) : 'Belum pernah' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <Link :href="`/admin/users/${user.id}/edit`" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50">
                                    <Pencil class="h-3.5 w-3.5" /> Ubah
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Tidak ada pengguna" description="Tidak ada akun yang cocok dengan pencarian atau filter." />
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
