<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-brand-700">{{ eyebrow }}</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-[28px]">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
