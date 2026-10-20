<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, ExternalLink, Inbox } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { formatDateTime } from '../../lib/labels';

const props = defineProps({
    notifications: { type: Object, required: true },
});

const page = usePage();
const unread = computed(() => props.notifications.data.filter((notification) => !notification.read_at).length);
const totalUnread = computed(() => page.props.notificationCount ?? 0);

function openNotification(notification) {
    router.post(`/notifikasi/${notification.id}/baca`, {}, { preserveScroll: true });
}

function markAllRead() {
    router.post('/notifikasi/baca-semua', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifikasi" />

    <AppLayout>
        <PageHeader
            title="Notifikasi"
            description="Pembaruan kasus, penugasan, dan pengingat tindak lanjut."
            eyebrow="Kotak masuk"
        >
            <template #actions>
                <button type="button" class="btn-secondary" :disabled="!totalUnread" @click="markAllRead">
                    <CheckCheck class="h-4 w-4" /> Tandai semua dibaca
                </button>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200">
                <Bell class="h-3.5 w-3.5 text-brand-600" /> {{ notifications.total }} notifikasi
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 font-bold text-brand-800 ring-1 ring-brand-200">
                {{ totalUnread }} belum dibaca
            </span>
            <span v-if="unread" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-slate-500 ring-1 ring-slate-200">
                {{ unread }} baru di halaman ini
            </span>
        </div>

        <section class="card overflow-hidden">
            <div v-if="notifications.data.length" class="divide-y divide-slate-100">
                <div
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="flex flex-col gap-3 px-5 py-4 transition sm:flex-row sm:items-start"
                    :class="notification.read_at ? 'bg-white' : 'bg-brand-50/40'"
                >
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl" :class="notification.read_at ? 'bg-slate-100 text-slate-400' : 'bg-brand-100 text-brand-700'">
                        <Bell class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold" :class="notification.read_at ? 'text-slate-700' : 'text-slate-900'">{{ notification.title }}</p>
                            <span v-if="!notification.read_at" class="rounded-full bg-brand-700 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">Baru</span>
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ notification.body }}</p>
                        <p class="mt-1.5 text-[10px] text-slate-400">{{ formatDateTime(notification.created_at) }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <button
                            v-if="!notification.read_at"
                            type="button"
                            class="btn-secondary !px-2.5 !py-1.5 text-[11px]"
                            @click="openNotification(notification)"
                        >
                            <ExternalLink v-if="notification.link" class="h-3.5 w-3.5" />
                            <CheckCheck v-else class="h-3.5 w-3.5" />
                            {{ notification.link ? 'Buka & tandai' : 'Tandai dibaca' }}
                        </button>
                        <Link
                            v-else-if="notification.link"
                            :href="notification.link"
                            class="btn-secondary !px-2.5 !py-1.5 text-[11px]"
                        >
                            <ExternalLink class="h-3.5 w-3.5" /> Buka
                        </Link>
                    </div>
                </div>
            </div>
            <EmptyState
                v-else
                title="Kotak masuk kosong"
                description="Notifikasi tentang penugasan kasus, pembaruan status, dan pengingat tindak lanjut akan muncul di sini."
            >
                <template #action>
                    <Inbox class="h-5 w-5 text-slate-300" />
                </template>
            </EmptyState>

            <Pagination :paginator="notifications" />
        </section>
    </AppLayout>
</template>
