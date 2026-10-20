<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Braces,
    Building2,
    CalendarCheck2,
    ChevronDown,
    Database,
    FileChartColumn,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Menu,
    Plus,
    ScrollText,
    Settings2,
    ShieldAlert,
    ShieldCheck,
    Tags,
    UserCog,
    UserRound,
    Users,
    WifiOff,
    X,
} from '@lucide/vue';
import BrandMark from '../Components/BrandMark.vue';
import { applyBranding } from '../lib/branding';
import { formatLongDate, initials, relativeTime, roleClass } from '../lib/labels';

const page = usePage();
const user = computed(() => page.props.auth.user);
const branding = computed(() => page.props.branding || {});
const school = computed(() => page.props.school);
const notifications = computed(() => page.props.notifications ?? []);
const notificationCount = computed(() => page.props.notificationCount ?? 0);

const sidebarOpen = ref(false);
const activeMenu = ref(null);
const toasts = ref([]);
const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

const navGroups = computed(() => {
    const groups = [
        {
            label: 'Ruang kerja',
            items: [
                { label: 'Dasbor', href: '/dashboard', icon: LayoutDashboard },
                { label: 'Insiden', href: '/insiden', icon: ShieldAlert },
                { label: 'Presensi', href: '/presensi', icon: CalendarCheck2 },
                ...(user.value?.can_access_dorm
                    ? [{ label: 'Asrama & piket', href: '/asrama', icon: Building2 }]
                    : []),
                { label: 'Data siswa', href: '/siswa', icon: Users },
                { label: 'Laporan bulanan', href: '/laporan', icon: FileChartColumn },
            ],
        },
    ];

    const adminItems = [];
    if (user.value?.can_manage_users) {
        adminItems.push(
            { label: 'Data master', href: '/admin/master', icon: Database },
            { label: 'Guru & Staf', href: '/admin/users', icon: GraduationCap },
            { label: 'Kategori insiden', href: '/admin/categories', icon: Tags },
            { label: 'Pengaturan', href: '/admin/settings', icon: Settings2 },
        );
    }
    if (user.value?.can_view_audit) {
        adminItems.push({ label: 'Jejak audit', href: '/admin/audit', icon: ScrollText });
    }
    adminItems.push({ label: 'Dokumentasi API', href: '/api/docs', icon: Braces, external: true });
    if (adminItems.length) {
        groups.push({ label: 'Administrasi', items: adminItems });
    }

    return groups;
});

const currentLabel = computed(() => {
    const all = navGroups.value.flatMap((group) => group.items);
    const match = all
        .filter((item) => page.url.startsWith(item.href))
        .sort((a, b) => b.href.length - a.href.length)[0];
    return match?.label ?? 'SIGAP-SR';
});

function isActive(href) {
    if (href === '/dashboard') return page.url.startsWith('/dashboard');
    return page.url.startsWith(href);
}

function toggleNotifications() {
    activeMenu.value = activeMenu.value === 'notifications' ? null : 'notifications';
}

function toggleUserMenu() {
    activeMenu.value = activeMenu.value === 'user' ? null : 'user';
}

function openNotification(notification) {
    activeMenu.value = null;
    router.post(`/notifikasi/${notification.id}/baca`);
}

function markAllRead() {
    router.post('/notifikasi/baca-semua', {}, { preserveScroll: true });
}

function logout() {
    router.post('/logout');
}

function go(href, external = false) {
    sidebarOpen.value = false;

    if (external) {
        window.open(href, '_blank', 'noopener,noreferrer');
        return;
    }

    router.visit(href);
}

watch(
    () => page.props.branding,
    (value) => applyBranding(value),
    { deep: true, immediate: true },
);

let toastSequence = 0;
function pushToast(type, message) {
    const id = ++toastSequence;
    toasts.value.push({ id, type, message });
    window.setTimeout(() => {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }, 5500);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) pushToast('success', flash.success);
        if (flash.error) pushToast('error', flash.error);
        if (flash.info) pushToast('info', flash.info);
    },
    { deep: true, immediate: true },
);

function onOnline() {
    online.value = true;
    pushToast('success', 'Koneksi pulih. Draf di perangkat siap dikirim.');
}

function onOffline() {
    online.value = false;
}

onMounted(() => {
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
});
onBeforeUnmount(() => {
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
});
</script>

<template>
    <div class="min-h-full bg-slate-50">
        <div v-if="activeMenu" class="fixed inset-0 z-30" @click="activeMenu = null"></div>

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="sidebarOpen" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>
        </Transition>

        <aside
            class="fixed inset-y-0 left-0 z-[60] flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:w-64 lg:translate-x-0"
            :class="{ 'translate-x-0': sidebarOpen }"
        >
            <div class="flex h-16 items-center justify-between gap-3 border-b border-slate-100 px-5">
                <Link href="/dashboard" class="min-w-0 flex-1 focus:outline-none">
                    <BrandMark />
                </Link>
                <button type="button" class="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden" aria-label="Tutup menu navigasi" @click="sidebarOpen = false">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <div class="no-scrollbar flex-1 overflow-y-auto px-3 py-4">
                <div v-for="group in navGroups" :key="group.label" class="mb-5">
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ group.label }}</p>
                    <div class="space-y-1">
                        <button
                            v-for="item in group.items"
                            :key="item.href"
                            type="button"
                            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                            :class="isActive(item.href)
                                ? 'bg-brand-50 text-brand-800 shadow-sm ring-1 ring-brand-100'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                            @click="go(item.href, item.external)"
                        >
                            <component
                                :is="item.icon"
                                class="h-[18px] w-[18px] shrink-0"
                                :class="isActive(item.href) ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-600'"
                            />
                            <span class="truncate">{{ item.label }}</span>
                            <span v-if="isActive(item.href)" class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                        </button>
                    </div>
                </div>

                <div class="px-1">
                    <Link
                        href="/insiden/buat"
                        class="flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-3 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-900/20 transition hover:bg-brand-800"
                    >
                        <Plus class="h-4 w-4" />
                        Catat insiden
                    </Link>
                </div>
            </div>

            <div class="border-t border-slate-100 p-4">
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <ShieldCheck class="h-4 w-4 text-brand-700" />
                        <span class="truncate">{{ school?.name }}</span>
                    </div>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                        Akses dibatasi sesuai peran dan tercatat di jejak audit.
                    </p>
                </div>
                <div class="mt-3 flex items-center gap-3 rounded-xl px-1 py-1">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">
                        {{ initials(user?.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-slate-800">{{ user?.name }}</p>
                        <p class="truncate text-[11px] text-slate-500">{{ user?.role_label }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-rose-600" title="Keluar" aria-label="Keluar dari akun" @click="logout">
                        <LogOut class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-md">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Buka menu navigasi" @click="sidebarOpen = true">
                        <Menu class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ currentLabel }}</p>
                        <p class="hidden text-[11px] text-slate-500 sm:block">{{ formatLongDate(page.props.today || new Date()) }}</p>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <span
                            v-if="!online"
                            class="hidden items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200 sm:inline-flex"
                        >
                            <WifiOff class="h-3.5 w-3.5" /> Mode luring
                        </span>

                        <Link
                            href="/insiden/buat"
                            class="hidden items-center gap-1.5 rounded-xl bg-brand-700 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-800 sm:inline-flex"
                        >
                            <Plus class="h-4 w-4" /> Catat insiden
                        </Link>

                        <div class="relative" data-popover>
                            <button
                                type="button"
                                class="relative rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                title="Notifikasi"
                                @click.stop="toggleNotifications"
                            >
                                <Bell class="h-5 w-5" />
                                <span
                                    v-if="notificationCount > 0"
                                    class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                                >
                                    {{ notificationCount > 9 ? '9+' : notificationCount }}
                                </span>
                            </button>

                            <Transition
                                enter-active-class="transition duration-150"
                                enter-from-class="opacity-0 translate-y-1"
                                leave-active-class="transition duration-100"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div v-if="activeMenu === 'notifications'" class="fixed inset-x-3 top-16 z-40 max-h-[85vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:absolute sm:inset-auto sm:right-0 sm:mt-2 sm:w-[22rem] sm:max-h-[32rem]">
                                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                        <p class="text-sm font-bold text-slate-800">Notifikasi</p>
                                        <button type="button" class="text-[11px] font-semibold text-brand-700 hover:underline" @click="markAllRead">Tandai semua dibaca</button>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <button
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            type="button"
                                            class="flex w-full gap-3 border-b border-slate-50 px-4 py-3 text-left transition hover:bg-slate-50"
                                            @click="openNotification(notification)"
                                        >
                                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg" :class="notification.read_at ? 'bg-slate-100 text-slate-400' : 'bg-brand-50 text-brand-700'">
                                                <Bell class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center gap-2">
                                                    <span class="truncate text-xs font-semibold" :class="notification.read_at ? 'text-slate-600' : 'text-slate-900'">{{ notification.title }}</span>
                                                    <span v-if="!notification.read_at" class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                                                </span>
                                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">{{ notification.body }}</span>
                                                <span class="mt-1 block text-[10px] font-medium text-slate-400">{{ relativeTime(notification.created_at) }}</span>
                                            </span>
                                        </button>
                                        <div v-if="!notifications.length" class="px-4 py-8 text-center text-xs text-slate-500">
                                            Tidak ada notifikasi baru.
                                        </div>
                                    </div>
                                    <Link href="/notifikasi" class="block border-t border-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-brand-700 hover:bg-brand-50" @click="activeMenu = null">
                                        Lihat semua notifikasi
                                    </Link>
                                </div>
                            </Transition>
                        </div>

                        <div class="relative" data-popover>
                            <button type="button" class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-slate-100" @click.stop="toggleUserMenu">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-800">{{ initials(user?.name) }}</span>
                                <ChevronDown class="hidden h-4 w-4 text-slate-400 sm:block" />
                            </button>
                            <Transition
                                enter-active-class="transition duration-150"
                                enter-from-class="opacity-0 translate-y-1"
                                leave-active-class="transition duration-100"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div v-if="activeMenu === 'user'" class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ user?.name }}</p>
                                        <p class="truncate mt-0.5 text-[11px] text-slate-500">{{ user?.email }}</p>
                                        <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="roleClass(user?.role)">
                                            {{ user?.role_label }}
                                        </span>
                                    </div>
                                    <div class="border-b border-slate-100 py-1">
                                        <Link
                                            href="/profil"
                                            class="flex w-full items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-brand-700"
                                            @click="activeMenu = null"
                                        >
                                            <UserRound class="h-4 w-4 text-slate-400" /> Profil &amp; keamanan
                                        </Link>
                                    </div>
                                    <button type="button" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-rose-600" @click="logout">
                                        <LogOut class="h-4 w-4" /> Keluar dari akun
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <slot />
                <footer class="mt-12 flex flex-col gap-2 border-t border-slate-200 pt-5 text-[11px] text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        {{ branding.app_name || 'SIGAP-SR' }} · {{ branding.tagline || 'Pengasuhan Sekolah Rakyat' }}
                        <span class="mx-1">·</span>
                        Dikembangkan oleh <span class="font-semibold text-slate-500">{{ branding.developer?.name || 'Rizal Taufiq Hidayat' }}</span>
                        <span class="mx-1">·</span> v{{ branding.version || '1.0.0' }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 text-slate-500 sm:gap-3">
                        <Link href="/kebijakan-privasi" class="hover:text-brand-700 hover:underline">Kebijakan Privasi</Link>
                        <span>·</span>
                        <Link href="/persyaratan-layanan" class="hover:text-brand-700 hover:underline">Persyaratan Layanan</Link>
                        <span>·</span>
                        <p class="flex items-center gap-1 text-slate-400">
                            <ShieldCheck class="h-3.5 w-3.5 text-brand-600" /> Data anak dilindungi
                        </p>
                        <Link href="/api/docs" target="_blank" class="font-semibold text-brand-700 hover:underline">API</Link>
                    </div>
                </footer>
            </main>
        </div>

        <div class="pointer-events-none fixed inset-x-0 bottom-4 z-[70] flex flex-col items-center gap-2 px-4 sm:bottom-6 sm:items-end sm:px-6">
            <TransitionGroup
                enter-active-class="transition duration-200"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition duration-150"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg"
                    :class="{
                        'border-emerald-200 bg-emerald-50 text-emerald-800': toast.type === 'success',
                        'border-rose-200 bg-rose-50 text-rose-800': toast.type === 'error',
                        'border-sky-200 bg-sky-50 text-sky-800': toast.type === 'info',
                    }"
                >
                    <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0" />
                    <p class="leading-relaxed">{{ toast.message }}</p>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

