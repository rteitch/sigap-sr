<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '—' },
    hint: { type: String, default: '' },
    tone: { type: String, default: 'brand' },
});

const toneClass = computed(() => ({
    brand: 'bg-brand-50 text-brand-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    rose: 'bg-rose-50 text-rose-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
    violet: 'bg-violet-50 text-violet-700',
    slate: 'bg-slate-100 text-slate-700',
}[props.tone] ?? 'bg-slate-100 text-slate-700'));
</script>

<template>
    <div class="card p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ value }}</p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="toneClass">
                <slot name="icon" />
            </span>
        </div>
        <p v-if="hint" class="mt-3 text-xs leading-relaxed text-slate-500">{{ hint }}</p>
    </div>
</template>
