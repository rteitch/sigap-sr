<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    EyeOff,
    FolderCog,
    Loader2,
    Pencil,
    Plus,
    Save,
    Sparkles,
    Tags,
} from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Modal from '../../../Components/Modal.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import { confidentialityClass } from '../../../lib/labels';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const options = computed(() => page.props.options);
const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    default_confidentiality: 'normal',
    is_positive: false,
    is_active: true,
    sort_order: null,
});

const activeCount = computed(() => props.categories.filter((category) => category.is_active).length);
const positiveCount = computed(() => props.categories.filter((category) => category.is_positive).length);

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
}

function openEdit(category) {
    editing.value = category;
    form.clearErrors();
    form.name = category.name;
    form.default_confidentiality = category.default_confidentiality;
    form.is_positive = category.is_positive;
    form.is_active = category.is_active;
    form.sort_order = category.sort_order;
    showModal.value = true;
}

function submit() {
    if (editing.value) {
        form.patch(`/admin/categories/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post('/admin/categories', {
            preserveScroll: true,
            onSuccess: () => (showModal.value = false),
        });
    }
}

function toggleActive(category) {
    router.patch(
        `/admin/categories/${category.id}`,
        {
            name: category.name,
            default_confidentiality: category.default_confidentiality,
            is_positive: category.is_positive,
            is_active: !category.is_active,
            sort_order: category.sort_order,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Kategori insiden" />

    <AppLayout>
        <PageHeader
            title="Kategori insiden"
            description="Kategori menentukan kerahasiaan awal dan langkah protokol. Nonaktifkan kategori tanpa menghapus riwayat kasus."
            eyebrow="Administrasi"
        >
            <template #actions>
                <button type="button" class="btn-primary" @click="openCreate"><Plus class="h-4 w-4" /> Tambah kategori</button>
            </template>
        </PageHeader>

        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Total kategori</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-slate-800"><Tags class="h-4 w-4 text-slate-400" /> {{ categories.length }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kategori aktif</p>
                <p class="mt-1 text-xl font-bold text-emerald-600">{{ activeCount }}</p>
            </div>
            <div class="card p-4">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kategori apresiasi</p>
                <p class="mt-1 flex items-center gap-1.5 text-xl font-bold text-brand-700"><Sparkles class="h-4 w-4" /> {{ positiveCount }}</p>
            </div>
        </div>

        <section class="card overflow-hidden">
            <div v-if="categories.length" class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="table-head">
                        <tr>
                            <th class="w-16 px-4 py-2.5">Urutan</th>
                            <th class="px-4 py-2.5">Kategori</th>
                            <th class="px-4 py-2.5">Kerahasiaan awal</th>
                            <th class="px-4 py-2.5">Jenis</th>
                            <th class="px-4 py-2.5">Kasus</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="category in categories" :key="category.id" class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono text-slate-400">{{ category.sort_order ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Sparkles v-if="category.is_positive" class="h-3.5 w-3.5 text-emerald-500" />
                                    <span class="font-semibold text-slate-800">{{ category.name }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="confidentialityClass(category.default_confidentiality)">
                                    <EyeOff v-if="category.default_confidentiality !== 'normal'" class="h-2.5 w-2.5" />
                                    {{ options.confidentialities[category.default_confidentiality] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1" :class="category.is_positive ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'">
                                    {{ category.is_positive ? 'Apresiasi' : 'Insiden' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ category.incidents_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 transition"
                                    :class="category.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 ring-slate-200 hover:bg-slate-200'"
                                    @click="toggleActive(category)"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="category.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ category.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50" @click="openEdit(category)">
                                    <Pencil class="h-3.5 w-3.5" /> Ubah
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState v-else title="Belum ada kategori" description="Tambahkan kategori insiden untuk mulai mencatat kasus." />
        </section>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
                <p class="flex items-center gap-1.5 font-bold"><AlertTriangle class="h-3.5 w-3.5" /> Perubahan berdampak</p>
                Mengubah kerahasiaan awal hanya memengaruhi kasus baru. Kasus lama tetap memakai tingkat kerahasiaan saat dibuat.
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-500">
                <p class="flex items-center gap-1.5 font-bold text-slate-700"><FolderCog class="h-3.5 w-3.5" /> Nonaktifkan, jangan hapus</p>
                Kategori yang tidak aktif tidak muncul pada formulir baru, tetapi riwayat kasus tetap utuh.
            </div>
        </div>

        <Modal :show="showModal" :title="editing ? 'Ubah kategori' : 'Tambah kategori'" description="Kategori dan kerahasiaan awal untuk laporan baru." @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="label" for="category-name">Nama kategori</label>
                    <input id="category-name" v-model="form.name" type="text" class="field" placeholder="Contoh: Konflik antar siswa" required />
                    <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="label" for="confidentiality">Kerahasiaan awal</label>
                    <select id="confidentiality" v-model="form.default_confidentiality" class="field" :disabled="form.is_positive">
                        <option v-for="(label, value) in options.confidentialities" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">Kategori apresiasi selalu normal.</p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3">
                        <input v-model="form.is_positive" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @change="form.is_positive ? (form.default_confidentiality = 'normal') : null" />
                        <span>
                            <span class="text-xs font-semibold text-slate-800">Kategori apresiasi</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">Perilaku positif, bukan insiden.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3">
                        <input v-model="form.is_active" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
                        <span>
                            <span class="text-xs font-semibold text-slate-800">Aktif</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">Tampil pada formulir baru.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label class="label" for="sort-order">Urutan tampil</label>
                    <input id="sort-order" v-model="form.sort_order" type="number" min="0" max="999" class="field" placeholder="Otomatis di akhir" />
                    <p v-if="form.errors.sort_order" class="mt-1 text-xs font-medium text-rose-600">{{ form.errors.sort_order }}</p>
                </div>

                <div v-if="editing && editing.incidents_count" class="rounded-xl bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-500">
                    Kategori ini dipakai pada {{ editing.incidents_count }} kasus. Riwayat tidak berubah saat nama atau pengaturan diperbarui.
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Menyimpan…' : 'Simpan kategori' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
