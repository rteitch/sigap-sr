<script setup>
import { Inbox } from '@lucide/vue';

defineProps({
    title: { type: String, default: 'Belum ada data' },
    description: { type: String, default: 'Data akan tampil di sini setelah ditambahkan.' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
            <slot name="icon">
                <Inbox class="h-6 w-6" />
            </slot>
        </span>
        <p class="mt-4 text-sm font-semibold text-slate-800">{{ title }}</p>
        <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
