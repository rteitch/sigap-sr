<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';

defineProps({
    size: { type: String, default: 'md' },
    showText: { type: Boolean, default: true },
});

const page = usePage();
const branding = computed(() => page.props.branding || {});
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <span
            class="grid shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 via-brand-600 to-brand-800 text-white shadow-lg shadow-brand-900/20"
            :class="size === 'lg' ? 'h-14 w-14' : 'h-10 w-10'"
        >
            <img
                v-if="branding.logo_url"
                :src="branding.logo_url"
                alt="Logo {{ branding.school_name || branding.app_name }}"
                class="h-full w-full bg-white object-contain p-0.5"
            />
            <ShieldCheck v-else :class="size === 'lg' ? 'h-8 w-8' : 'h-6 w-6'" stroke-width="2.1" />
        </span>
        <div v-if="showText" class="min-w-0 leading-tight">
            <p class="truncate text-base font-bold tracking-tight text-slate-900">
                {{ branding.app_name || 'SIGAP-SR' }}
            </p>
            <p class="truncate text-[11px] font-medium text-slate-500">{{ branding.tagline || 'Pengasuhan & Pembinaan' }}</p>
        </div>
    </div>
</template>
