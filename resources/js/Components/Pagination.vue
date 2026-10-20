<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    paginator: { type: Object, required: true },
});
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex flex-col gap-3 border-t border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ paginator.from }}</span>–<span class="font-semibold text-slate-700">{{ paginator.to }}</span>
            dari <span class="font-semibold text-slate-700">{{ paginator.total }}</span> data
        </p>
        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-xs font-semibold transition"
                    :class="link.active ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2.5 text-xs font-semibold text-slate-300"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
