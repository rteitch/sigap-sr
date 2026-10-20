<script setup>
import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Activity, CalendarDays, Filter, History, RotateCcw, Search, ShieldCheck } from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import { formatDateTime, initials } from '../../../lib/labels';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    actions: { type: Array, default: () => [] },
});

const page = usePage();
const options = computed(() => page.props.options);

const filters = reactive({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    date: props.filters.date ?? '',
});

function apply() {
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
    router.get('/admin/audit', params, { preserveState: true, preserveScroll: true, replace: true });
}

function reset() {
    Object.assign(filters, { q: '', action: '', date: '' });
    router.get('/admin/audit', {}, { preserveState: false, replace: true });
}

const actionTone = (action) => {
    if (action?.includes('sensitive') || action?.includes('confidential')) return 'bg-rose-50 text-rose-700 ring-rose-200';
    if (action?.includes('export') || action?.includes('viewed')) return 'bg-violet-50 text-violet-700 ring-violet-200';
    if (action?.includes('created') || action?.includes('saved')) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    if (action?.includes('updated') || action?.includes('toggled')) return 'bg-amber-50 text-amber-700 ring-amber-200';
    return 'bg-slate-100 text-slate-600 ring-slate-200';
};
</script>

<template>
    <Head title="Jejak audit" />

    <AppLayout>
        <PageHeader
            title="Jejak audit"
            description="Catatan tindakan sensitif: akses data, perubahan kasus, ekspor, dan pengelolaan akun."
            eyebrow="Administrasi"
        >
            <template #actions>
                <div class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                    <History class="h-4 w-4 text-slate-400" /> {{ logs.total }} catatan
                </div>
            </template>
        </PageHeader>

        <section class="card p-4">
            <div class="grid gap-3 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label class="label" for="search">Cari deskripsi atau pengguna</label>
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input id="search" v-model="filters.q" type="search" class="field !pl-9" placeholder="Kata kunci…" @keydown.enter="apply" />
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <label class="label">Tindakan</label>
                    <select v-model="filters.action" class="field" @change="apply">
                        <option value="">Semua tindakan</option>
                        <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="label">Tanggal</label>
                    <input v-model="filters.date" type="date" class="field" @change="apply" />
                </div>
                <div class="flex items-end gap-2 lg:col-span-2">
                    <button type="button" class="btn-secondary !px-3" aria-label="Reset filter" @click="reset"><RotateCcw class="h-4 w-4" /></button>
                    <button type="button" class="btn-primary flex-1 !px-3" @click="apply"><Filter class="h-4 w-4" /> Terapkan</button>
                </div>
            </div>
        </section>

        <section class="card mt-4 overflow-hidden">
            <div v-if="logs.data.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="px-4 py-2.5">Waktu</th>
                            <th class="px-4 py-2.5">Pengguna</th>
                            <th class="px-4 py-2.5">Tindakan</th>
                            <th class="px-4 py-2.5">Deskripsi</th>
                            <th class="px-4 py-2.5">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                <p class="inline-flex items-center gap-1"><CalendarDays class="h-3 w-3 text-slate-400" /> {{ formatDateTime(log.created_at) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-6 w-6 place-items-center rounded-md bg-slate-100 text-[9px] font-bold text-slate-500">{{ initials(log.user) }}</span>
                                    <span class="font-medium text-slate-700">{{ log.user }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-mono text-[9px] font-bold ring-1" :class="actionTone(log.action)">
                                    <Activity class="h-2.5 w-2.5" /> {{ log.action }}
                                </span>
                            </td>
                            <td class="max-w-md px-4 py-3 text-slate-600">{{ log.description ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-[10px] text-slate-400">{{ log.ip_address ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Tidak ada catatan audit" description="Tidak ada log yang cocok dengan filter saat ini." />

            <Pagination :paginator="logs" />
        </section>

        <div class="mt-4 flex items-start gap-2 rounded-2xl border border-brand-100 bg-brand-50/60 p-4 text-[11px] leading-relaxed text-brand-900/75">
            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
            <p>
                Jejak audit tidak dapat diubah atau dihapus dari aplikasi. Catatan ini digunakan untuk pertanggungjawaban akses data anak dan investigasi insiden keamanan.
            </p>
        </div>
    </AppLayout>
</template>
